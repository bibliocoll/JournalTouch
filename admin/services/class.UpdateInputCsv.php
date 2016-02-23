<?php
/**
 * @brief   Reads input.csv and adds data
 *
 * Update metadata (once) and fetch recent issues (do it daily via cron)
 *
 * @notes   Initial time for 1050 titles (fetching metadata and recent issues)
 *          takes about 12 Minutes. With covers fetching even 45 minutes (all
 *          sources enabled). Yeah, hard, but the meta data has to be fetches
 *          only once and covers will only be downloaded if new later on.
 *          Example stats in detail:
 * - Processed lines: 1056
 * - Hits JournalToc (meta): 701
 * - Hits JournalToc (new issues): 309
 * - Hits JournalToc (bad date): 28
 * - Hits CrossRef (meta): 8
 * - Hits CrossRef (new issues): 4
 * - Hits JournalSeek (meta only): 347
 * - This page was created in 2756.0283031464 seconds
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
 * @notes
 * 2015-08-30 Created this file from the version that also returned the ajax response (sys/class.getJournalInfos.php resp. class.GetJournalToc.php)
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
    public $log = '';


    /**
     * @brief   Load config, set properties
     *
     * @return \b void
     */
    public function __construct($cfg) {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit($this->maxtime);
        $this->script_timer();

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
     * - 2015-11-07:  Maybe save the cover url (fetch_covers) to a new column in
     *                journals.csv. Would also allow for journal specific download
     *                urls...
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
    public function update_journals_csv($fetch_meta = true, $fetch_recent = true, $clean_tags = false, $fetch_covers = false) {
        if (($handle = fopen($this->csv_file->path, "r")) !== FALSE) {
            // if fetch_covers enabled, create an instance
            if ($fetch_covers) {
                require('class.GetCover.php');
                $getCover = new GetCover();
            }

            $this->log .= '<p>opened ' .$this->csv_file->path. ' for reading, starting run now.</p>'.PHP_EOL.'<p>';
            while (($journal_rows = fgetcsv($handle, 0, $this->csv_file->separator)) !== FALSE) {
                $this->log .= '<p>';

                $this->issn = '';
                if (!empty($journal_rows[$this->csv_col->p_issn])) {
                    $this->issn = $journal_rows[$this->csv_col->p_issn];
                } elseif (!empty($journal_rows[$this->csv_col->e_issn])) {
                    $this->issn = $journal_rows[$this->csv_col->e_issn];
                }

                $issn = $this->issn;
                if ($issn !== '') {
                    ob_flush();
                    //if ($this->processed > 3) break(1);
                    $this->processed++;

                    // make row class property
                    foreach ($this->csv_col AS $name => $rowid) {
                        @$this->journal_row[$name] = $journal_rows[$rowid];
                    }

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

                    // Download cover if option is set
                    if ($fetch_covers) {
                        // force download if we found it is new
                        if ($this->journal_row['new']) $getCover->recheck_api_age = 0;

                        $getCover->get_cover($issn, $this->journal_row['publisher']);
                        $this->log .= $getCover->log;
                    }

                    // no matter if we got a hit or not - every line has to be written to the new csv...
                    $new_row = implode(';', $this->journal_row);
                } else {
                    $new_row = implode(';', $journal_rows);
                }
                $this->journals_buffer[] = $new_row;

                $this->log .= '</p>';
            }

            fclose($handle);
            $file_date = date('Y-m-d_H\Hi');
            rename($this->csv_file->path, dirname($this->csv_file->path) ."/backup/journals_$file_date.csv");

            $all_rows = implode("\n", $this->journals_buffer);
            file_put_contents ($this->csv_file->path, $all_rows);

            if ($this->jt_suggest_create && $this->jt_suggest_buffer) {
                $all_rows = implode("\n", $this->jt_suggest_buffer);
                file_put_contents (dirname($this->csv_file->path). '/journaltoc-suggest_'.$file_date.'.csv', $all_rows);
            }
        }

        // Append statistics to runtime loge from while loop
        $this->log .= '</p>'.PHP_EOL.'<p>';
        $this->log .= 'Processed lines: '.$this->processed;
        $this->log .= '<br>Hits JournalToc (meta): '.$this->hits_jt_meta;
        $this->log .= '<br>Hits JournalToc (new issues): '.$this->hits_jt_new;
        $this->log .= '<br>Hits JournalToc (bad date): '.$this->hits_jt_badDate;
        $this->log .= '<br>Hits CrossRef (meta): '.$this->hits_cr_meta;
        $this->log .= '<br>Hits CrossRef (new issues): '.$this->hits_cr_new;
        $this->log .= '<br>Hits JournalSeek (meta only): '.$this->hits_cs_meta;
        $this->log .= '<br>This page was created in ' . $this->script_timer() . ' seconds.';
        $this->log .= '</p>'.PHP_EOL;

        // Save last log to file
        file_put_contents (dirname($this->csv_file->path). '/LastUpdateLog.html', $this->log);

        // Alway return true...
        return true;
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

            // Use jt_titel if no title given in original file
            if (!$this->journal_row['title']) $this->journal_row['title'] = $jt_title;

            $csv_tags = '';
            if ($jt_publisher) $newTags[] = 'JTpub-'.str_replace(',', ' ', $jt_publisher);
            if ($jt_subjects)  $newTags[] = 'JTtag-'.$jt_subjects;
            if ($jt_rights)    $newTags[] = 'JTlegal-'.$jt_rights;
            if ($newTags) $csv_tags = implode(', ', $newTags);

            $this->journal_row['tags'] = ($this->journal_row['tags']) ? $this->journal_row['tags'].', '.$csv_tags : $csv_tags;
            if ($jt_link && !$this->journal_row['metaWebsite']) $this->journal_row['metaWebsite'] = $jt_link;
            $this->journal_row['metaGotToc'] = 'JToc';

            // Add Publisher
            $this->journal_row['publisher'] = $jt_publisher;

            // Add new issns, nothing to lose, since we search via issn and found something
            if ($this->amend_issn) {
                if ($jt_pIssn) $this->journal_row['p_issn'] = $jt_pIssn;
                if ($jt_pIssn) $this->journal_row['e_issn'] = $jt_eIssn;
            }

            $this->hits_jt_meta++;
            return true;
        }
        else {
            $this->journal_row['metaGotToc'] = 'None';
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
     * @brief   Fetches only journal infos from crossref
     *
     * Currently only doi... The only real use is THAT we know CrossRef provides
     * data for a specific journal.
     *
     * @todo
     * - Keep parameters and add $row so this method can be used "alone"?
     * - Maybe better (also publisher and many more infos): https://api.crossref.org/journals/issn
     * @see https://github.com/CrossRef/rest-api-doc/blob/master/rest_api.md
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
            $this->journal_row['metaGotToc'] = 'None';
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

                $this->log .= "<b>CrossRef (recent)</b>: ".$this->journal_row['title']." ($issn) - found no new issue, but it is still new? $is_new<br />";
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
        if (is_object($filtered->item(0))) $js_publisher = $filtered->item(0)->nodeValue;

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
        if (isset($js_publisher)) $newTags[] = 'JSpub-'.$js_publisher;
        if (isset($js_publisher)) $this->journal_row['publisher'] = $js_publisher;
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
     * @todo    2015-08-30: This is a redundant versions of the same function in sys/class.getJournalInfo.php
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
     * @todo    2015-08-30: This is a redundant versions of the same function in sys/class.getJournalInfo.php
     *
     * @param $date    \b STR  A (prism) date
     * @return \b DAT Date if found, else \b BOL false
     */
    private function jt_clean_date($date = '') {
        $journal_date= str_replace("\n", '', $date); // has line breaks sometimes?
        $journal_date= str_replace(",", '', $date); // "22 December, 2015" becomes 2016-12-22; "22 December 2015" works

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
}
?>
