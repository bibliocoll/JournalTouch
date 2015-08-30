<?php
/**
 * @brief   Reads input.csv and adds data
 *
 * Update metadata (once) and fetch recent issues (do it daily via cron)
 *
 * @notes   Initial time for 1000 titles (fetching metadata and recent issues)
 *          takes about 12 Minutes. Yeah, hard, but the meta data has to be
 *          fetches only once. Example stats in detail:
 * - Processed lines: 1071
 * - Hits JournalToc (meta): 637
 * - Hits JournalToc (new issues): 83
 * - Hits JournalToc (bad date): 35
 * - Hits CrossRef (meta): 29
 * - Hits CrossRef (new issues): 0
 * - Hits JournalSeek (meta only): 405
 * - This page was created in 713.84962916374 seconds.
 *          Subsequent runs (checkingfor new issues) take about 3-4 minutes.
 *
 * @notes   Interesting stuff
 * - http://zetoc.mimas.ac.uk/ (uk only...)
 * - http://amsl.technology/issn-resolver/
 *
 * @todo
 * - Implement something like journaltoc_fetch_recent_premium to avoid the
 *   queries (besides getting better results). Should be easy, but I don't
 *   know how the premium output looks, yet.
 * - Maybe add interface class and create extended classes for each service...
 *
 * @todo
 * - $amend_issn might mess with cover images. Maybe never replace the p_issn?
 *   Problem: sometimes the given issn does not "work" with JournalToc
 * - Make XXpub, XXtag, XXlegal optional for fetching meta
 * - Add some php based cron "simulation" (like wordpress?)
 * - Switch config.php > articleLink automatically if in network with fulltext
 *   access (ip_subnet); also may use JTlegal
 * - Use SFX to directly download article (or link to print)
 * - Maybe always query CrossRef (for volume/issue and doi)
 *
 * @notes
 * 2014-07-18 Merged the following files into this class
 * - ajax/getCrossRefTOC.php
 * - ajax/getJournalTOC.php
 *
 *
 * @author Tobias Zeumer <tzeumer@verweisungsform.de>
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 */
class GetJournalInfos {
    /// \brief \b FLOAT Script timing is always fun.
    private $starttime = 0;
    /// \brief \b INT Maximum script execution time. Firefox default for network.http.keep-alive.timeout is 115
    private $maxtime = 6000;

    /// \brief \b OBJ @see config.php
    protected $csv_file;
    /// \brief \b OBJ @see config.php
    protected $csv_col;
    /// \brief \b OBJ @see config.php
    protected $api_all;
    /// \brief \b OBJ @see config.php
    protected $jt;
    /// \brief \b OBJ @see config.php
    public $prefs;

    /// \brief \b STR The ISSN of the current journal. Maybe useful somewhere (currently only a warning if datediff is pretty high)
    protected $issn;
    /// \brief \b ARY Holds a complete joural row and its infos. Populated in GetJournalInfos::update_journals_csv
    protected $journal_row = array();
    /// \brief \b ARY Saves all rows. They are written to file only after everything is done (performance)
    protected $journals_buffer = array();

    /// \brief \b BOL If missing in source csv, add print reps. eissn; beware - might be a bad idea if you have already covers
    public $amend_issn = false;

    /// \brief \b BOL List journals not on journaltoc. @see jt_suggest_csv
    public $jt_suggest_create = true;
    /// \brief \b STR Temporarily save publisher here, since this information is not part of journals.csv
    protected $jt_suggest_publisher = '';
    /// \brief \b ARY Saves all rows for jt journaltoc suggestion file
    protected $jt_suggest_buffer = array();

    /// \brief \b ARY @see GetJournalInfos::set_clean_tags
    protected $tag_replace;

    /* Array with toc information */
    protected $toc = array(
        'authors'  => array(),
        'title'    => array(),
        'link'     => array(),
        'doi'      => array(),
        'abstract' => array(),
        'date'     => array(),
        'page'     => array(),

        'source'   => array(),
        'year'     => array(),
        'volume'   => array(),
        'issue'    => array(),

        'sort'     => array()
    );

    // Vars to keep track of statistics
    private $hits_jt_meta = 0;
    private $hits_jt_new = 0;
    private $hits_jt_badDate = 0;
    private $hits_cr_meta = 0;
    private $hits_cr_new = 0;
    private $hits_cs_meta = 0;
    private $processed = 0;
    private $log = '';


    /**
     * @brief   Load config, set properties
     *
     * @return \b void
     */
    public function __construct() {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit($this->maxtime);
        $this->script_timer();

        require_once('../config.php');
        $this->csv_file = $cfg->csv_file;
        $this->csv_col  = $cfg->csv_col;
        $this->api_all  = $cfg->api->all;
        $this->jt       = $cfg->api->jt;
        $this->prefs    = $cfg->prefs;
    }


    /**
     * @brief   Deconstructor. Shows script time...
     */
    function __destruct() {
        // echo $this->log;
    }


    /**
     * @brief   Simpyl takes the current input file and writes a new one with
     *          additional data.
     *
     * Saves the current input file with a timestamp.
     *
     * @note
     * - IMPORTANT: To reduce the time I require "JToc"/"CRtoc" being set for
     *              "metaGotToc". This is only (automatically) set if
     *              $fetch_meta = true
     *
     * @todo
     * - add something like "force fetch meta"
     *
     * @param $fetch_meta   \b BOL  Fetch meta info from JournalToc (anynway:
     *                              only if not already done before).
     * @param $fetch_recent \b BOL  Fetches dates of most recen issue
     * @param $clean_tags   \b BOL  Do some cleanup of the tags
     *
     * @param $outfile    \b STR  Path and name of new file
     *
     * @return \b void
     */
    public function update_journals_csv($fetch_meta = true, $fetch_recent = true, $clean_tags = false) {
        ob_start();
        if (($handle = fopen('../'.$this->csv_file->path, "r")) !== FALSE) {
            while (($journal_rows = fgetcsv($handle, 1000, $this->csv_file->separator)) !== FALSE) {
                //if ($this->processed > 3) break(1);
                $this->processed++;
                $this->log .= '<p>';

                // make row class property
                foreach ($this->csv_col AS $name => $rowid) {
                    $this->journal_row[$name] = $journal_rows[$rowid];
                }

                /* check for alternative ISSN if strlen is < 1 */
                $issn = (strlen($this->journal_row['p_issn'] < 1) ? $this->journal_row['e_issn'] : $this->journal_row['p_issn']);
                $this->issn = $issn;

                $already_checked = $this->journal_row['metaGotToc'];
                $already_checked = ($already_checked == 'JToc' || $already_checked == 'CRtoc' || $already_checked == 'Jseek' || $already_checked == 'None') ? true : false;

                // !!! Journaltoc: Update meta data if wanted AND if not done before (so JToc/CRtoc is an important information ;) !!!)
                if ($fetch_meta && !$already_checked) {
                    if ($this->jt->account) {
                        $already_checked = $this->journaltoc_fetch_meta($issn, $this->jt->account);
                    }
                    // !!! Crossref: Update meta data if wanted AND if not done before (CRtoc) AND no result from JT
                    if (!$already_checked) $already_checked = $this->crossref_fetch_meta($issn);
                    // TEMP try journalseek if still no result
                    if (!$already_checked) $already_checked = $this->journalseek_fetch_meta($issn);

                    // Want a little help in trying to suggest missing journals to journaltoc?
                    if ($this->jt_suggest_create) {
                        $this->jt_suggest_csv();
                    }
                }

                // Fetch info if journal is listed by JournalToc or CrossRef
                if ($fetch_recent) {
                    if ($this->journal_row['metaGotToc'] == 'JToc' && $this->jt->account) {
                        $new_issue = $this->journaltoc_fetch_recent($issn, $this->jt->account);
                    }
                    elseif ($this->journal_row['metaGotToc'] == 'CRtoc') {
                        $new_issue = $this->crossref_fetch_recent($issn);
                    }
                }

                // Just an idea, clean/change tags automatically in some way
                if ($clean_tags) {
                    $this->set_clean_tags();
                }

                // no matter if we got a hit or not - every line has to be written to the new csv...
                $new_row = implode(';', $this->journal_row);
                $this->journals_buffer[] = $new_row;

                $this->log .= '</p>';
            }
            fclose($handle);
            $file_date = date('Y-m-d_H\Hi');
            rename('../'.$this->csv_file->path, "../data/journals/backup/journals_$file_date.csv");

            $all_rows = implode("\n", $this->journals_buffer);
            file_put_contents ('../'.$this->csv_file->path, $all_rows);

            if ($this->jt_suggest_create && $this->jt_suggest_buffer) {
                $all_rows = implode("\n", $this->jt_suggest_buffer);
                file_put_contents ('../data/journals/journaltoc-suggest_'.$file_date.'.csv', $all_rows);
            }
        }

        echo $this->log;
        echo '<pre>';
        echo 'Processed lines: '.$this->processed;
        echo '<br>Hits JournalToc (meta): '.$this->hits_jt_meta;
        echo '<br>Hits JournalToc (new issues): '.$this->hits_jt_new;
        echo '<br>Hits JournalToc (bad date): '.$this->hits_jt_badDate;
        echo '<br>Hits CrossRef (meta): '.$this->hits_cr_meta;
        echo '<br>Hits CrossRef (new issues): '.$this->hits_cr_new;
        echo '<br>Hits JournalSeek (meta only): '.$this->hits_cs_meta;
        echo '<br>This page was created in ' . $this->script_timer() . ' seconds.';

        ob_end_flush();
    }


    /**
     * @brief   Fetch toc from journaltoc or from crossref as fallback
     *
     * @todo    Maybe add an option to return toc array as json?
     *
     * @param $issn    \b STR  Journal ISSN
     * @return \b STR Some html
     */
    public function ajax_query_toc($issn) {
        $this->issn = $issn;

        $toc = ($this->jt->account) ? $this->journaltoc_fetch_toc($issn, $this->jt->account) : false;

        if (!$toc) {
            $toc = $this->crossref_fetch_toc($issn);
        }

        // whatever we got, create html
        return $this->ajax_response_toc($this->toc);
    }


    /**
     * @brief   Build toc (html) for response
     *
     * @todo
     * - Hmm, $toc should really be a class property?
     * - This should be put into an iframe (like meta links), so the article list
     *   can be scrolled, without scrolling the whole page
     *
     * @author Daniel Zimmel <zimmel@coll.mpg.de>
     * @author Tobias Zeumer <tzeumer@verweisungsform.de>
     *
     * @param $issn    \b STR  Journal ISSN
     * @return \b STR Some html
     */
    private function ajax_response_toc($toc, $max_authors = 3) {
        if (!isset($toc['sort']) || count($toc['sort']) < 1) {
            /* write something we can read from our caller script */
            return false;
        }

        $timestring = (isset($toc['update_date'])) ? date('c', strtotime($toc['update_date'])) : __('Sorry, I could not find any information about the publishing date');
    /*
    $journal = $toc['source'][0];
    $journal .= ($toc['volume'][0])                    ? ', Vol. '.$toc['volume'][0] : '';
    $journal .= ($toc['issue'][0])                     ? ', Nr. '.$toc['issue'][0]   : '';
    $journal .= ($toc['volume'][0] && $toc['year'][0]) ? ' ('.$toc['year'][0].')'    : '';
     */
        $html  = '<h4 class="small-10 columns">'.$toc['source'][0].'</h4>';
        $html .= '<h6 class="small-10 columns"><i class="fi-asterisk"></i> '. __('last update:') .' <time class="timeago" datetime="'.$timestring.'">'.$timestring.'</time> <i class="fi-asterisk"></i></h6>';

        // sort by date newest to oldest
        asort($toc['sort']);
        array_reverse ($toc['sort'], $preserve_keys = true);

        foreach (array_keys($toc['sort']) as $id ) {
            if ($toc['title'][$id]) {
                $authors = array_slice($toc['authors'][$id], 0, $max_authors);
                $authors = implode(' & ', $authors);
                $authors .= ($authors) ? ' : ' : '';

                $link_dl = ($this->prefs->show_dl_button) ? $this->get_download_link($id) : '';

                if ($this->api_all->articleLink == true) {
                    $entry = '<span class="item_name">'.$authors.'<a href="'.$toc['link'][$id].'">'.$toc['title'][$id].'</a></span>';
                }
                else {
                    $entry = '<span class="item_name">'.$authors.$toc['title'][$id].'</a></span>';
                }

                $html .= '<div class="row tocItem" id="'.($toc['doi'][$id]?:$id).'" ';
                $html .= 'data-name="'.$toc['title'][$id].'" ';
                $html .= 'data-link="'.$toc['link'][$id].'" ';
                $html .= 'data-source="'.$toc['source'][$id].'" ';
                $html .= 'data-doi="'.$toc['doi'][$id];
                $html .= '">
                    <div class="small-6 medium-6 large-7 columns textbox">
                    <div class="toctitle">'.$entry.'</div></div>';
                // get extra options, set class to invisible (change in css)
                $html .= '<div class="small-6 medium-6 large-5 columns buttonbox">';
                // abstract button: let us assume that strlen>300 == abstract
                $html .=      (strlen($toc['abstract'][$id]) > 300) ? '<a class="button medium radius abstract">'.__('Abstract').'</a>&nbsp;' : '';
                $html .=      $link_dl.PHP_EOL;
                // add button (cart)
                $html .=      '<a class="item_add button medium radius" href="javascript:;"><i class="fi-plus"></i></a>
                </div>';
                $html .=    (($toc['abstract'][$id]) ? '<div class="abstract invisible"><span>'.$toc['abstract'][$id].'</span></div>' : '');
                $html .= '</div>';
            }
        }

        return $html;
    }


    /**
     * @brief   Fetches only journal infos from Journaltoc
     *
     * Currently putting publisher, topic and journal rights into tags with "JT..."
     * prefix; might be useful....
     *
     * @todo  Keep parameters and add $row so this method can be used "alone"?
     *
     * @param $issn    \b STR  Journal ISSN
     * @param $user    \b STR  JournalToc user
     * @return \b BOL True if journal is found, else false
     */
    public function journaltoc_fetch_meta($issn, $user) {
        $jtURL = "http://www.journaltocs.ac.uk/api/journals/$issn?user=$user";
        $xml = simplexml_load_file($jtURL);

        if (!is_object($xml)) return false;

        if ($xml->item->children('dc', TRUE)->title) {
            $jt_title     = htmlspecialchars_decode($xml->item->children('dc', TRUE)->title);
            $jt_link      = htmlspecialchars_decode($xml->item->link);
            $jt_pIssn     = $xml->item->children('prism', TRUE)->issn;
            $jt_eIssn     = $xml->item->children('prism', TRUE)->eIssn;
            $jt_publisher = htmlspecialchars_decode($xml->item->children('dc', TRUE)->publisher);
            $jt_subjects  = str_replace(" ", "_", ucwords(strtolower($xml->item->children('dc', TRUE)->subject)));
            $jt_rights    = $xml->item->children('dc', TRUE)->rights;
            $this->log .= "<b>MATCH for JT (meta)</b>: <a href=\"$jt_link\" target=\"_blank\">$jt_title (=".$this->journal_row['title'].")</a> (p: $jt_pIssn /e: $jt_eIssn) von $jt_publisher ($jt_rights). Thema: $jt_subjects (<a href=\"$jtURL\" target=\"_blank\">JT</a>).<br>";

            $csv_tags = '';
            if ($jt_publisher) $newTags[] = 'JTpub-'.str_replace(',', ' ', $jt_publisher);
            if ($jt_subjects)  $newTags[] = 'JTtag-'.$jt_subjects;
            if ($jt_rights)    $newTags[] = 'JTlegal-'.$jt_rights;
            if ($newTags) $csv_tags = implode(', ', $newTags);

            $this->journal_row['tags'] = ($this->journal_row['tags']) ? $this->journal_row['tags'].', '.$csv_tags : $csv_tags;
            if ($jt_link && !$this->journal_row['metaWebsite']) $this->journal_row['metaWebsite'] = $jt_link;
            $this->journal_row['metaGotToc'] = 'JToc';

            // Add new issns, nothing to lose, since we search via issn and found something
            if ($this->amend_issn) {
                if ($jt_pIssn) $this->journal_row['p_issn'] = $jt_pIssn;
                if ($jt_pIssn) $this->journal_row['e_issn'] = $jt_eIssn;
            }

            $this->hits_jt_meta++;
            return true;
        }
        else {
            $this->journal_row['metaGotToc'] = '';
            $this->log .= "<b>NO HIT for JT (meta)</b>: ".$this->journal_row['title']." ($issn)<br>";
            return false;
        }
    }


    /**
     * @brief   Fetches (current) issue infos from Journaltoc
     *
     * @note    The publishing date of an article is not always in order. So we
     *          fetch them all and check for the newest.
     *
     * @todo    Check for dc:date too? But I think JournalToc "guarantess" a prism date
     *
     * @param $issn    \b STR  Journal ISSN
     * @param $user    \b STR  JournalToc user
     * @return \b BOL True if journal is found, else false
     */
    public function journaltoc_fetch_recent($issn, $user, $max_age_days = 15) {
        // if called directly with a limit setting use it. Otherwise use setting from config.php
        if ($max_age_days == 15 && $this->api_all->is_new_days) $max_age_days = $this->api_all->is_new_days;

        $jtURL = "http://www.journaltocs.ac.uk/api/journals/$issn?output=articles&user=$user";
        $xml = simplexml_load_file($jtURL);

        if (!is_object($xml)) return false;

        $journal_dates = array();
        foreach ($xml->item as $item) {
            $journal_date = (string)$item->children('prism', TRUE)->publicationDate;
            $journal_date = $this->jt_clean_date($journal_date);
            if ($journal_date) {
                $journal_dates[] = $journal_date;
            }
        }
        arsort($journal_dates);

        $recent_date = (isset($journal_dates[0])) ? $journal_dates[0] : false;
        if ($recent_date !== false) {
            $is_new = $this->get_datediff($recent_date, $max_age_days);
        }
        else {
            $is_new = false;
            $this->hits_jt_badDate++;
            $this->log .= "<b>MESSY DATE for JT (recent)</b>: ".$this->journal_row['title']." ($this->issn) got a <a href=\"$jtURL\" target=\"_blank\">TOC</a> but no information about publishing date or I can't evaluate it<br>";
        }

        $this->journal_row['date'] = $recent_date;
        if ($is_new) {
            $this->journal_row['new'] = 'JTnew';
            $this->hits_jt_new++;
            $this->log .= "<b>JT (recent)</b>: ".$this->journal_row['title']." ($issn) was updated <a href=\"$jtURL\" target=\"_blank\"> within the last $max_age_days days</a><br>";
            return true;
        }
        elseif (!$is_new && $recent_date) {
            $this->journal_row['new'] = '';
            $this->log .= "<b>JT (recent)</b>: ".$this->journal_row['title']." (<a href=\"$jtURL\" target=\"_blank\">$issn</a>) has no new update within the last $max_age_days days<br>";
        }
        else {
            return false;
        }
    }


    /**
     * @brief   Fetches complete toc from JournalTOC and return array with fields
     *
     * @note    Hmm, ah. Article publishing dates differ from issue publishing
     *          dates, so the last ones might be the newest ones. Sorting is done
     *          in GetJournalInfos::ajax_response_toc().
     * @note    No volume and issue are provided for free. Obviousley
     *          automatically removed ("Vol. , No. (2014) pp. -"), but
     *          "Volume 13, Issue 1, Page 518-536, January 2014" in some Description
     *
     * @todo
     * - <content:encoded> provides a formatted reference - maybe just split()?
     * - <dc:identifier> DOIs! ("DOI 10.1002/adma.201400310") - not always that
     *   nice. Also always/often in <item rdf:about="xxx">?
     * - ignore titles like "Masthead: (Adv. Mater. 24/2014)",
     *   "Contents: (Adv. Mater. 24/2014)"?
     *
     * @todo
     * - hmm, is explicit (string) casting pointless here or would it be cleaner
     *   to do it in other methods too?
     * - avoid RegEx - usually they are much slower than inbuild string replacement
     *   functions
     * - check if volume and issue are provided for premium; add check
     * - writing source, year, volume, issue to every article is a bit useless?
     *   Leave for now - maybe nice for some manipulation...
     *
     * @note  Shortest sfx link: http://sfx.gbv.de/sfx_tuhh?svc.fulltext=yes&rft_id=info:doi/10.1002/adma.201400310
     *
     * @author Daniel Zimmel <zimmel@coll.mpg.de>
     * @author Tobias Zeumer <tzeumer@verweisungsform.de>
     *
     * @param $issn    \b STR  Journal ISSN
     * @param $user    \b STR  JournalToc user
     * @return \b BOL True if journal is found, else false
     */
    public function journaltoc_fetch_toc($issn, $user) {
        $jtURL = "http://www.journaltocs.ac.uk/api/journals/$issn?output=articles&user=$user";
        $xml = simplexml_load_file($jtURL);
        if (!is_object($xml)) {
            return false;
        }

        // Some defautl replacements to clean up strigns
        $patterns[] = '/[\x00-\x1F\x7F]/';  $replacements[] = '';
        $patterns[] = '/\s+/';              $replacements[] = ' ';

        // do some clean up (MIT journals: authors are in brackets, other?)
        $aut_patterns = $patterns;          $aut_replacements = $replacements;
        $aut_patterns[] = '/\(/';           $aut_replacements[] = '';
        $aut_patterns[] = '/\)/';           $aut_replacements[] = '';
        // Remove emails
        $aut_patterns[] = '/.*\@.*\.[A-Za-z]{2,3}/';           $aut_replacements[] = '';

        $tit_patterns = $patterns;          $tit_replacements = $replacements;
        $tit_patterns[] = '/pp\..+/';       $tit_replacements[] = '';
        $tit_patterns[] = '/\(.+\)$/';      $tit_replacements[] = '';
        $tit_patterns[] = '/,$/';           $tit_replacements[] = '';

        $prev_date = false;
        $itemcount = 0;
        $toc       = array();
        $missing_dois = array();
        foreach ($xml->item as $article) {
            $jt_title     = preg_replace($tit_patterns, $tit_replacements, $article->title);
            $jt_abstract  = strip_tags(preg_replace($patterns, $replacements, $article->description));
            $jt_link      = (string)$article->link;
            $jt_source    = preg_replace($tit_patterns, $tit_replacements, $article->children('dc', TRUE)->source);

            // DOI
            $jt_doi       = (string)$article->children('dc', TRUE)->identifier; // must be there and only one ???
            $jt_doi       = $this->get_doi($jt_doi);

            // Author(s) don't have to be set + there might be more than one
            $jt_authors = array();
            if (is_object($article->children('dc', TRUE)->creator)) {
                foreach ($article->children('dc', TRUE)->creator as $author) {
                    $jt_authors[] = ucwords(trim(preg_replace($aut_patterns, $aut_replacements, $author)));
                }
            }

            // Rarely provided vol/no/page
            $jt_volume = '';
            $jt_issue  = '';
            $jt_page   = '';
            $jt_volume  = preg_replace($patterns, $replacements, $article->children('prism', TRUE)->volume);
            $jt_issue   = preg_replace($patterns, $replacements, $article->children('prism', TRUE)->number);
            $jt_page = strip_tags(preg_replace($patterns, $replacements, $article->children('prism', TRUE)->startingPage));
            $jt_page = ($jt_page) ? '-'.strip_tags(preg_replace($patterns, $replacements, $article->children('prism', TRUE)->endingPage)) : '';

            // date
            $jt_date = (string)$article->children('prism', TRUE)->publicationDate;
            $jt_date = $this->jt_clean_date($jt_date);

            // @note: Not sure, but maybe some entries don't have a date (like imprint?)
            if ($jt_date > $prev_date) {
                $age = $this->get_datediff($jt_date, 365);
                $prev_date = $jt_date;
                $toc['update_age']  = $age;
                $toc['update_date'] = $jt_date;
            }

            // sort string for toc output
            $jt_sort = ($jt_date) ? $jt_date : date('Y-m-d', strtotime('-2 years'));
            $jt_sort = $jt_sort.$jt_page;


            $toc['authors'][]  = $jt_authors;
            $toc['title'][]    = $jt_title;
            $toc['link'][]     = $jt_link;
            $toc['doi'][]      = $jt_doi;
            $toc['abstract'][] = $jt_abstract;
            $toc['date'][]     = $jt_date;
            $toc['page'][]     = $jt_page;

            $toc['source'][]   = $jt_source;
            $toc['year'][]     = date('Y', strtotime($jt_date));
            $toc['volume'][]   = $jt_volume;
            $toc['issue'][]    = $jt_issue;

            $toc['sort'][]     = $jt_sort;

            //i really want a doi. so remember this one for crossref! ~~krug 05.08.2015
            if ($jt_doi == '') {
                //keep a reference to where the doi needs to go in $toc
                $missing_dois[$jt_title] = &$toc['doi'][$itemcount];
            }
            $itemcount++;
        }
        //we have dois to fetch
        if (count($missing_dois) > 0) {
            $jsondata = json_encode(array_keys($missing_dois));
            $ch = curl_init("http://search.crossref.org/links"); //yep, oldschool
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($jsondata)));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata );
            $cr_result = curl_exec($ch);
            $cr_return = json_decode($cr_result);
            curl_close($ch);
            if (is_object($cr_return)  && $cr_return->query_ok ){
                foreach ($cr_return->results as $result) {
                    if ($result->match) {
                        $missing_dois[$result->text] = $this->get_doi($result->doi);
                    }
                }
            }
        }
        if (isset($toc)) {
            $this->toc = $toc;
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * @brief   Fetches only journal infos from crossref
     *
     * Currently only doi... The only real use is THAT we know CrossRef provides
     * data for a specific journal.
     *
     * @todo
     * - Keep parameters and add $row so this method can be used "alone"?
     *
     * @param $issn    \b STR  Journal ISSN
     * @return \b BOL True if journal is found, else false
     */
    public function crossref_fetch_meta($issn) {
        $crURL  = "http://search.crossref.org/dois?type=Journal&q=$issn";
        $crJson = file_get_contents($crURL);

        $crJournal = json_decode($crJson, true);

        $crLink = false;
        if (isset($crJournal[0]['doi'])) $crLink = $crJournal[0]['doi'];
        if (isset($crJournal[0]['fullCitation'])) $crTitle = $crJournal[0]['fullCitation'];

        if ($crLink) {
            if (!$this->journal_row['metaWebsite']) $this->journal_row['metaWebsite'] = $crLink;
            $this->journal_row['metaGotToc'] = 'CRtoc';
            $this->log .= "<b>MATCH for CrossRef (meta)</b>: <a href=\"$crLink\" target=\"_blank\">$crTitle (=".$this->journal_row['title'].")</a>.<br>";

            $this->hits_cr_meta++;
            return true;
        }
        else {
            $this->journal_row['metaGotToc'] = '';
            $this->log .= "<b>NO HIT for CrossRef (meta)</b>: ".$this->journal_row['title']." ($issn)<br>";
            return false;
        }
    }


    /**
     * @brief   Fetches (current) issue infos from CrossRef
     *
     * Fetches only one article and tries to guess if it is new.
     *
     * @note    This only makes sense if it is done on a daily basis, because
     *          "date" is set today if "year/vol/issue" has changed.
     *
     * @note    "year/vol/issue" could be used in getCrossRefTOC.php to do an
     *          easier filtering for the toc
     *          Also: by checking for "CRnew" resp. "CTnew" the service to query
     *          could easily be identified
     *
     * @todo
     * - IMPORTANT: Umm, $this->journal_row['date'] = $current_date is a bad idea?
     *   Is this column used somewhere for checks?!?
     * - Maybe add (guess) update frequency to prevent unnecessary checks?
     * - Just comparing year/vol/issue should be sufficient, a closer look doesn't
     *   make sense?
     *
     * @param $issn    \b STR  Journal ISSN
     * @return \b BOL True if journal is found, else false
     */
    public function crossref_fetch_recent($issn, $max_age_days = 15) {
        $crURL  = "http://search.crossref.org/dois?sort=year&rows=1&q=$issn";
        $crJson = file_get_contents($crURL);

        $crArticle = json_decode($crJson, true);

        parse_str(urldecode(html_entity_decode($crArticle[0]['coins'])), $coins);

        // Make issues comparable as year/vol/issue (we don't get more)
        $current_issue = '';
        $current_issue .= (isset($coins['rft_date']))   ? $coins['rft_date'].'/' : '/';
        $current_issue .= (isset($coins['rft_volume'])) ? $coins['rft_volume'].'/' : '/';
        $current_issue .= (isset($coins['rft_issue']))  ? $coins['rft_issue'] : '';
        $current_date = date('Y-m-d');

        // Is this the first check?
        if ($this->journal_row['lastIssue']) {
            // Did anything change?
            if ($this->journal_row['lastIssue'] != $current_issue) {
                $this->journal_row['new'] = 'CRnew';
                $this->journal_row['date'] = $current_date;
                $this->journal_row['lastIssue'] = $current_issue;

                $this->log .= "<b>CrossRef (recent)</b>: ".$this->journal_row['title']." ($issn) - just found a new issue!";
                $this->hits_cr_new++;
                return true;
            }
            else {
                // is it still new?
                $is_new = $this->get_datediff($this->journal_row['date'], $max_age_days);

                $this->log .= "<b>CrossRef (recent)</b>: ".$this->journal_row['title']." ($issn) - found no new issue, but it is still new? $is_new";
                return $is_new;
            }
        }
        else {
            // Remember current data for next check
            $this->journal_row['date'] = $current_date;
            $this->journal_row['lastIssue'] = $current_issue;
            $this->log .= "<b>CrossRef (recent)</b>: ".$this->journal_row['title']." ($issn) was checked for the first time for a recent issue";
            return false;
        }
    }


    /**
     * @brief   Fetches complete toc from CrossRef and return array with fields
     *
     * @author Daniel Zimmel <zimmel@coll.mpg.de>
     * @author Tobias Zeumer <tzeumer@verweisungsform.de>
     *
     * @param $issn    \b STR  Journal ISSN
     * @return \b BOL True if journal is found, else false
     */
    public function crossref_fetch_toc($issn) {
        // results output is limited to 20 per page, so send at least two queries
        $records = array();
        for ($page = 1; $page <= 4; $page++) {
            $json = "http://search.crossref.org/dois?q=$issn&sort=year&page=$page";
            $file = file_get_contents($json);
            $records = array_merge_recursive($records, json_decode($file, true));
        }

        if (!$records) return false;

        $toc = array();
        foreach ($records as $item) {
            // parse OpenURL params (coins) into $pcoins
            $coins = html_entity_decode($item['coins']);
            parse_str($coins, $pcoins);

            // Article infos
            $cr_authors  = (is_array($pcoins['rft_au'])) ? $pcoins['rft.au'] : array(0 => $pcoins['rft_au']);
            $cr_title    = $item['title'];
            $cr_link     = $item['doi'];
            $cr_doi      = $this->get_doi($item['doi']);
            $cr_abstract = '';

            // Source infos
            $cr_source  = $pcoins['rft_jtitle'] . ", Vol. " . $pcoins['rft.volume'] . ", No. " . $pcoins['rft.issue'] . " (" . $pcoins['rft_date'] . ")";
            //$jtitle = $pcoins['rft_jtitle']; // why again?
            $cr_date    = ''; // CR does not provide a date (for free?); only year via $pcoins['rft_date'] that's equal to $item['year']
            $cr_year    = $item['year'];
            $cr_vol     = (isset($pcoins['rft_volume'])) ? $pcoins['rft_volume'] : '';
            $cr_issue   = (isset($pcoins['rft_issue']))  ? $pcoins['rft_issue'] : '';
            $cr_page    = (isset($pcoins['rft_spage']))  ? $pcoins['rft_spage'] : '';

            // sort string for toc output
            $cr_sort = $cr_year . '-' . $cr_vol . '-' . $cr_issue . '-' . $cr_page;

            // create a vol. string to mark the most recent stuff
            $cur_vol = $cr_year . '-' . $cr_vol . '-' . $cr_issue;
            if (!isset($prev_vol)) $prev_vol = $cur_vol;

            // if current volume changed, stop processing the results - we've got everything
            if ($cur_vol !== $prev_vol) break(1);

            // only move to array if year is current or before
            $curY = date("Y");
            if ($cr_year >= $curY-1) {
                $toc['authors'][]  = $cr_authors;
                $toc['title'][]    = $cr_title;
                $toc['link'][]     = $cr_link;
                $toc['doi'][]      = $cr_doi;
                $toc['abstract'][] = $cr_abstract;
                $toc['date'][]     = $cr_date;
                $toc['page'][]     = $cr_page;

                $toc['source'][]   = $cr_source;
                $toc['year'][]     = $cr_year;
                $toc['volume'][]   = $cr_vol;
                $toc['issue'][]    = $cr_issue;

                $toc['sort'][]     = $cr_sort;
            }
        }

        if ($toc) {
            $this->toc = $toc;
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * @brief   Fetches infos from JournalSeek
     *
     * Only website, publisher and (maybe) tags
     *
     * @todo  Keep parameters and add $row so this method can be used "alone"?
     *
     * @example: http://journalseek.net/cgi-bin/journalseek/journalsearch.cgi?query=0001-0782&field=title
     *
     * @param $issn    \b STR  Journal ISSN
     * @return \b BOL True if journal is found, else false
     */
    public function journalseek_fetch_meta($issn) {
        $jsURL = "http://journalseek.net/cgi-bin/journalseek/journalsearch.cgi?field=title&query=$issn";

        $js = new DOMDocument();
        $js->preserveWhiteSpace = false;
        $js->loadHTMLFile($jsURL);

        $domxpath = new DOMXPath($js);

        // Publisher
        $filtered = $domxpath->query("//p[contains(., 'Published/Hosted')]/a");
        if (is_object($filtered->item(0))) $js_publisher = 'JSpub-'.$filtered->item(0)->nodeValue;

        // Issn's
        $filtered = $domxpath->query("//p[contains(., 'Published/Hosted')]");
        $check_issn = (is_object($filtered->item(0))) ? $filtered->item(0)->nodeValue : '';

        // Usually pissn...
        $str_pissn = 'ISSN (printed): ';
        $pos_pissn = ($check_issn) ? strpos($check_issn, $str_pissn) : '';
        if ($check_issn && $pos_pissn) $pissn = substr ($check_issn, $pos_pissn + strlen($str_pissn), 9);

        // ... and eissn
        $str_eissn = 'ISSN (electronic): ';
        $pos_eissn = ($check_issn) ? strpos($check_issn, $str_eissn) : '';
        if ($check_issn && $pos_eissn) $eissn = substr ($check_issn, $pos_eissn + strlen($str_eissn), 9);

        // very seldom: only (p)issn
        $str_pissn = 'ISSN: ';
        $pos_pissn = ($check_issn) ? strpos($check_issn, $str_pissn) : '';
        if ($check_issn && $pos_pissn) $pissn = substr ($check_issn, $pos_pissn + strlen($str_pissn), 9);


        // Journal website
        $filtered = $domxpath->query("//dd[1]/ul/li/a/@href");
        $js_link = (is_object($filtered->item(0))) ? $js_link = $filtered->item(0)->nodeValue : '';

        // Tags
        $filtered = $domxpath->query("//dd[2]/ul/li/a");
        if (is_object($filtered->item(0))) $js_subjects = $filtered->item(0)->nodeValue;

        if (isset($js_subjects)) {
            $js_subjects = str_replace ('  ', ' ', $js_subjects);
            $js_subjects = str_replace (' - ', ', JStag-', $js_subjects);
            $js_subjects = 'JStag-'.$js_subjects;
        }

        // Update row
        if (isset($js_publisher)) $newTags[] = $js_publisher;
        if (isset($js_subjects))  $newTags[] = $js_subjects;
        $csv_tags = (isset($newTags)) ? implode(', ', $newTags) : '';
        $this->journal_row['tags'] = ($this->journal_row['tags']) ? $this->journal_row['tags'].', '.$csv_tags : $csv_tags;
        if (isset($js_link) && !$this->journal_row['metaWebsite']) $this->journal_row['metaWebsite'] = $js_link;
        $this->journal_row['metaGotToc'] = 'Jseek';
        // Add new issns, nothing to lose, since we search via issn and found something
        if ($this->amend_issn) {
            if (isset($pissn)) $this->journal_row['p_issn'] = $pissn;
            if (isset($eissn)) $this->journal_row['e_issn'] = $eissn;
        }

        $this->jt_suggest_publisher = '';
        if ($this->jt_suggest_create && isset($js_publisher)) $this->jt_suggest_publisher = $js_publisher;

        // Feedback
        if (isset($js_link) || $csv_tags) {
            $this->log .= "<b>MATCH for Jseek (meta)</b>: <a href=\"$js_link\" target=\"_blank\">".$this->journal_row['title']."</a><br>";
            $this->hits_cs_meta++;
            return true;
        }
        else {
            $this->journal_row['metaGotToc'] = 'None';
            $this->log .= "<b>NO HIT for Jseek (meta)</b>: ".$this->journal_row['title']." ($issn)<br>";
            return false;
        }
    }


    /**
     * @brief   Clean up tags
     *
     * Maybe you fetched the tags from JournalTocs/Journalseek or want to to some
     * normalization. You just could copy everything from the tagcloud into a text
     * file and change it the way you like.
     *
     * @todo    Maybe remove or move this. Not sure, anyone will get the idea.
     *
     * @return \b BOL True if journal is found, else false
     */
    private function set_clean_tags() {
        $tmp_tags = explode(',', $this->journal_row['tags']);

        // Read csv with format oldTag;newTag to array (only once)
        if (($handle = fopen('../data/journals/tag-remap.txt', "r")) !== false && !$this->tag_replace) {
            while (($tag_row = fgetcsv($handle, 1000, $this->csv_file->separator)) !== false) {
                $key = trim($tag_row[0]);
                if (isset($tag_row[1])) $this->tag_replace[$key] = trim($tag_row[1]);
            }
        }

        // use saved array for comparison
        if ($this->tag_replace) {
            foreach ($tmp_tags as $key => &$cur_tag) {
                $cur_tag = trim($cur_tag);

                if ($cur_tag == '') unset($tmp_tags[$key]);

                if (isset($this->tag_replace[$cur_tag])) {
                    if ($this->tag_replace[$cur_tag] !== '') $cur_tag = $this->tag_replace[$cur_tag];
                }
            }
        }

        $tmp_tags = array_unique($tmp_tags);
        sort($tmp_tags);

        $this->journal_row['tags'] = implode(', ', $tmp_tags);
    }

    /**
     * @brief   Creates a csv if metaGotToc is not "JToc"
     *
     * Creates a file that can be used to sent to journaltocs@hw.ac.uk
     * (http://www.journaltocs.ac.uk/suggest.php); you have to check for feeds
     * yourself anyway
     *
     * @return \b BOL True if journal is found, else false
     */
    private function jt_suggest_csv() {
        if ($this->journal_row['metaGotToc'] == 'JToc') return false;

        $this->jt_suggest_buffer[0] = 'Journal Title;Print ISSN;Electonic ISSN;Journal Homepage URL;Journal TOC RSS URL;Publisher;Comments';
        $row = $this->journal_row['title'].';';
        $row .= $this->journal_row['p_issn'].';';
        $row .= $this->journal_row['e_issn'].';';
        $row .= $this->journal_row['metaWebsite'].';';
        $row .= 'CheckYourself!;';
        $row .= $this->jt_suggest_publisher.';';

        $this->jt_suggest_buffer[] = $row;
        return true;
    }


    /**
     * @brief   Returns true if a given date and today are apart only by x days.
     *
     * @param $journal_date  \b STR  Date as string (format Y-m-d)
     * @param $maxdiff       \b INT  Max difference in days
     * @return \b STR Script execution time on second call
     */
    private function get_datediff($journal_date, $max_age_days) {
        //might use date_diff(d1, d2 - php >= 5.3); http://de3.php.net/manual/en/function.date-diff.php#115065
        $current_year = date('Y');
        $current_day  = date('z');

        $journal_time = strtotime($journal_date);
        $journal_year = date('Y', $journal_time);
        $journal_day  = date('z', $journal_time);

        $day_diff = $current_day - $journal_day;
        $is_new = ($current_year == $journal_year && $day_diff < $max_age_days) ? true : false;

        if ($day_diff > 365) $this->log .= "<b>$this->issn</b>: not updated for over a year!?<br>";

        return $is_new;
    }


    /**
     * @brief   Tries to return a plain doi
     *
     * @param $doi_string  \b STR  Some string with a doi
     * @return \b STR The doi
     */
    private function get_doi($doi_string) {
        $doi = '';
        $doi_string = strtolower($doi_string);

        if ((substr ($doi_string, 0, 4) == 'http')) {
            // anything like http://dx.doi.org/10.1002/adma.201400310
            preg_match('/http.*\/\/.*\/{1}(.*\..*?\/.*?)($|\/*$|\?.*|\/\?*$)/', $doi_string, $matches);
            //FIXME: i think i've seen dois with 2 forward slashes? ~~krug 06.08.2015
            if (isset($matches[1])) $doi = $matches[1];
        }
        elseif ((substr ($doi_string, 0, 3) == 'doi')) {
            // "doi " or "doi:"
            // a doi should have at least a length of 3 (x/x) -> start looking for end at 7
            // ()?: <- use min() if not 0, else strlen
            $end = (min(strpos($doi_string,' ', 7), strpos($doi_string,';', 7)))?: strlen($doi_string);
            $doi = substr($doi_string, 4, $end -4);
        }

        return $doi;
    }


    /**
     * @brief   Measure scripts execution time
     * @return \b BOL True on start
     * @return \b STR Script execution time on second call
     */
    public function script_timer() {
        $mtime = microtime(); $mtime = explode(' ', $mtime); $mtime = $mtime[1] + $mtime[0];
        if (!$this->starttime) {
            $this->starttime = $mtime;
            return true;
        }
        $endtime = $mtime;
        $totaltime = ($endtime - $this->starttime);
        return $totaltime;
    }


    /**
     * @brief   Try to normalize date (if broken)
     *
     * @note    Journal dates are not normalized. Most common is YYYY-MM-DD, but
     *          others exist. Luckily strtotime() eats nearly everything (cases I
     *          encountered:
     *          "2014-07-16", "Nov 13", "2012-12-31T10:26:39Z", "Aug.  2014",
     *          "Fri, 03 Jan 2014 08:00:00 GMT", "2013-04-16T03:58:32.902823-05:",
     *          "April 24 2014"
     *          Not working:
     *          "Apr.-June  2014", "Apr.-June.  2014", "Jan.-Feb.  2015",
     *          "Second Quarter  2014", "Summer  2014", "2014",
     *          "Sun, 01 Jun 2014 00:00:00 GMT-" (hyphen at end)
     *          Stuff like "Apr.-June  2014" can be handled, the others ones...
     *
     * @todo    Maybe always return a date?
     *
     * @param $date    \b STR  A (prism) date
     * @return \b DAT Date if found, else \b BOL false
     */
    private function jt_clean_date($date = '') {
        $journal_date= str_replace("\n", '', $date); // has line breaks sometimes?

        // weird?
        if (!$journal_date) {
            return false;
        }
        // whoa, even worse - the dates are not normalized
        // convert twice to check if it is ok...
        $chk_date = strtotime($journal_date);
        $chk_date = date('Y-m-d', $chk_date);

        // What a messy check...
        if (date('Y', strtotime($chk_date)) == 1970) {
            // Check stuff like "Apr.-June  2014", "Apr.-June.  2014", "Jan.-Feb.  2015", "March-April  2014"
            $pos = strpos($journal_date , '-');
            if ($pos > 0) {
                $fix_date = '1 '; // Day
                $fix_date .= substr($journal_date, 0, $pos).' '; // Month
                $fix_date .= substr($journal_date, -4, 0); // year

                $chk_date = date('Y-m-d', strtotime($fix_date));
                if (date('Y', strtotime($chk_date)) == 1970) $chk_date = false;
            }

            if (!$pos && !$chk_date) {
                return false;
            }
        }

        return $chk_date;
    }


    /**
     * @brief   Try to get a direct download link
     *
     * Usually you are always taken to some landing page where you have to
     * "search" for the download link. Wouldn't it be much cooler to just tap
     * a button in JournalTouch and get the pdf?
     *
     * @note
     * - Even direct links often take you away from JournalTouch (only Springer
     *   is nice)
     * - Check if some legal terms require the landing page
     * - Elsevier also offers epub and mobi...
     *
     * @todo
     * - Maybe change parameter $toc. Only $toc['doi'] and $toc['link'] needed
     * - It would be nice to do more evaluation like
     * -- Does the library have the journal as print only (col in input.csv)
     * -- If a subscription, is the user in the right IP subnet for a download?
     *
     * @param $tocID    \b INT  ID of the GetJournalInfos::toc item to create a
     *                          link for
     * @return \b STR A link if possible
     */
    protected function get_download_link($tocID) {
        $sfx_url = '';
        if ($this->prefs->sfx) {
            // see todo: print only should be "no"
            $sfx_url = $this->prefs->sfx.'?svc.fulltext=yes';
        }

        $link_dl = '';
        $icon = 'fi-page-export-pdf';
        // STUFF THAT NEEDS SPECIAL REFORMATTING (not only DOI)
        // AIP src: http://scitation.aip.org/content/aip/journal/adva/5/8/10.1063/1.4928386?TRACK=RSS
        // AIP to: http://scitation.aip.org/deliver/fulltext/aip/journal/adva/5/8/1.4928386.pdf ('deliver/fulltext[...].pdf' instead 'content')
        if (strpos($this->toc['link'][$tocID], 'scitation.aip.org')) {
            $doi_split = explode('/', $this->toc['doi'][$tocID]);
            $link_dl = preg_replace('/\?.*/', '', $this->toc['link'][$tocID]);
            $link_dl = str_replace($doi_split[0].'/', '', $link_dl);
            $link_dl = str_replace('/content', '/deliver/fulltext', $link_dl.'.pdf');
        }
        // IEEE: http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=6787017 => http://ieeexplore.ieee.org/stamp/stamp.jsp?tp=&arnumber=6787017
        elseif (strpos($this->toc['link'][$tocID], 'ieeexplore.ieee.org')) {
            $link_dl = str_replace('xpl/articleDetails.jsp?arnumber=', 'stamp/stamp.jsp?tp=&arnumber=', $this->toc['link'][$tocID]);
        }
        // STUFF THAT CAN BE HANDLED WITH DOI ONLY
        elseif ($this->toc['doi'][$tocID]) {
            // IOP: http://iopscience.iop.org/2043-6262/6/3/035008/pdf/2043-6262_6_3_035008.pdf
            if (strpos($this->toc['link'][$tocID], 'iopscience.iop.org')) {
                $doi_split = explode('/', $this->toc['doi'][$tocID]);
                unset($doi_split[0]);
                $doi_path = implode('/', $doi_split);
                $doi_file = implode('_', $doi_split).'.pdf';
                $link_dl = "http://iopscience.iop.org/$doi_path/pdf/$doi_file";
            }
            // OSA: https://www.opticsinfobase.org/boe/viewmedia.cfm?uri=boe-5-7-2023&seq=0 (DOI-SUFFIX)
            elseif (strpos($this->toc['link'][$tocID], 'www.opticsinfobase.org')) {
                $doi_split = explode('/', $this->toc['doi'][$tocID]);
                $link_dl = 'https://www.opticsinfobase.org/boe/viewmedia.cfm?seq=0&uri='.$doi_split[1];
            }
            // SIAM: http://epubs.siam.org/doi/pdf/10.1137/130916515 (DOI)
            elseif (strpos($this->toc['link'][$tocID], 'epubs.siam.org')) {
                $link_dl = 'http://epubs.siam.org/doi/pdf/'.$this->toc['doi'][$tocID];
            }
            // Springer: http://link.springer.com/content/pdf/10.1007%2Fs10010-014-0174-x.pdf
            elseif (strpos($this->toc['link'][$tocID], 'link.springer.com')) {
                $link_dl = 'http://link.springer.com/content/pdf/'.rawurlencode($this->toc['doi'][$tocID]).'.pdf';
            }
            // Wiley: http://onlinelibrary.wiley.com/doi/10.1111/fwb.12352/pdf (DOI)
            elseif (strpos($this->toc['link'][$tocID], 'onlinelibrary.wiley.com')) {
                $link_dl = 'http://onlinelibrary.wiley.com/doi/'.$this->toc['doi'][$tocID].'/pdf';
            }
            // SFX with DOI
            elseif ($sfx_url) {
                $icon = 'fi-page-search';
                $link_dl = $sfx_url.'&rft_id=info:doi/'.$this->toc['doi'][$tocID];
            }
        }
        // SFX with title, date, issn
        elseif ($sfx_url) {
            $icon = 'fi-page-search';
            $link_dl  = $sfx_url;
            $link_dl .= '&rft.atitle='.urlencode($this->toc['title'][$tocID]);
            $link_dl .= '&rft.issn='.$this->issn;
            $link_dl .= '&rft.date='.$this->toc['date'][$tocID];
            //&rft.eissn=
        }

        if ($link_dl) $link_dl = '<a class="button medium radius '.$icon.'" href="'.$link_dl.'">&nbsp;</a>&nbsp;';

        return $link_dl;
    }

}
?>
