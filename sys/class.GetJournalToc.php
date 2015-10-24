<?php
/**
 * @brief   Povides toc for an issn. Either from JournalToc or from CrossRef
 *
 * @notes   Interesting stuff
 * - http://zetoc.mimas.ac.uk/ (uk only...)
 * - http://amsl.technology/issn-resolver/
 *
 * @todo
 *   Problem: sometimes the given issn does not "work" with JournalToc
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
 * @notes
 * 2015-08-30 Moved the part that updates input.csv to (services/class.UpdateInputCsv.php)
 *
 * @author Tobias Zeumer <tzeumer@verweisungsform.de>
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 */
class GetJournalInfos {
    /// \brief \b FLOAT Script timing is always fun.
    private $starttime = 0;
    /// \brief \b INT Maximum script execution time. Firefox default for network.http.keep-alive.timeout is 115
    private $maxtime = 30;

    /// \brief \b OBJ @see config.php
    protected $api_all;
    /// \brief \b OBJ @see config.php
    protected $jt;
    /// \brief \b OBJ @see config.php
    public $prefs;

    /// \brief \b STR The ISSN of the current journal. Maybe useful somewhere (currently only a warning if datediff is pretty high)
    protected $issn;

    /// \brief \b DATE The publishing date of the currently queries issue - used for caching
    protected $pubdate = '1970-01-01';

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


    /**
     * @brief   Load config (also loads bootstrap), set properties
     *
     * @return \b void
     */
    public function __construct() {
        header('Content-Type: text/html; charset=utf-8');
        set_time_limit($this->maxtime);
        $this->script_timer();

        require_once('../config.php');
        $this->api_all  = $cfg->api->all;
        $this->jt       = $cfg->api->jt;
        $this->prefs    = $cfg->prefs;
        $this->sys      = $cfg->sys;

        // Check if caching should be used. Use if set and different from '1970-01-01'
        // $_GET['pubdate'] should be formatted 'Y-m-d' (checked vis sys/bootstrap.php)
        if (isset($_GET['pubdate']))          $this->pubdate = $_GET['pubdate'];
        if ($this->pubdate == '1970-01-01')   $this->prefs->cache_toc_enable = false;
    }


    /**
     * @brief   Deconstructor. Shows script time...
     */
    function __destruct() {
        // echo $this->log;
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

      // If caching enabled create a file name pattern to look for
      // (cache is automatically disbaled if no valid date is give; see constructor)
      if ($this->prefs->cache_toc_enable) {
        $query     = md5(implode('', $_GET));
        $cachefile = $this->sys->data_cache."toc-$issn+$query.cache.html";
      }

      // Is caching enabled and cached file exists? Load it
      // (Issue date is same as in cache file name)
      if ($this->prefs->cache_toc_enable && file_exists($cachefile)) {
        $this->toc = file_get_contents($cachefile);
        $toc_status = true;
      }
      // A valid pubdate is given but no cached file exists
      elseif ($this->prefs->cache_toc_enable) {
        // clean up and delete old toc's
        $this->delete_expired($issn);

        $toc_status = $this->get_toc($issn);
        // A toc was found, cache it
        if ($toc_status) {
          file_put_contents($cachefile, $this->toc);
        }
      }
      // Ok, we got no date or caching is disabled. Get toc the old way
      else {
        $toc_status = $this->get_toc($issn);
      }

      // Whatever $toc_status we got now, return the toc html
      return $this->toc;
    }


    /**
     * @brief   Get toc, pad it with some html and put it into an iframe
     *
     * @todo    Maybe use CDN for scripts
     * @todo    2015-08-22: Remove hack for old non-iframe version if it finally
     *          gets removed from conduit.js
     * @todo    Outsource the iframe html to a template file for reuse and easy
     *          modification
     *
     * @param $issn    \b STR  Journal ISSN
     * @return \b STR Result as HTML; you may pass some variable as reference to get the status too
     */
    function get_toc($issn) {
      $html_prefix = '<!DOCTYPE html>
          <html><head>
          <link href="../css/foundation.min.css" rel="stylesheet">
          <link href="../css/foundation-icons/foundation-icons.css" rel="stylesheet">
          <link href="../css/local.css" rel="stylesheet">
          <script src="../js/vendor/jquery.js"></script>
          <script src="../js/vendor/jquery.timeago.js"></script>
    </head><body>';
      $html_postfix_ok = '<script src="../js/local/frame.js"></script></body></html>';
      $html_postfix_er = '<script>$(document).ready(window.parent.postMessage({"ready": false},"*"));</script></body></html>';

      // Try primary source: JournalToc
      $toc_status = ($this->jt->account) ? $this->journaltoc_fetch_toc($issn, $this->jt->account) : false;

      // Try secondary source: CrossRef (if you know any other add them beyond)
      if (!$toc_status) {
        $toc_status = $this->crossref_fetch_toc($issn);
      }

      // Hack for non-iframe version
      if (isset($_GET['noframe'])) return $this->ajax_response_toc($this->toc);

      // Got nothing - return an error (@note with a little overHEAD...)
      if (!$toc_status || !$this->toc) {
        $this->toc = $html_prefix.$html_postfix_er;
        return false;
      }
      // Create toc html and wrap it into the iframe html
      else {
        $this->toc = $html_prefix.$this->ajax_response_toc($this->toc).$html_postfix_ok;
        return true;
      }
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
     * @todo 2015-09-05
     * - fetching DOIs is nice, but it slows things down. Make it an option...
     * - also using curl might be fatal on certain configs
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
            $cr_authors = array(0 => '');
            if (isset($pcoins['rft_au'])) {
                $cr_authors  = (is_array($pcoins['rft_au'])) ? $pcoins['rft.au'] : array(0 => $pcoins['rft_au']);
            }
            $cr_title    = $item['title'];
            $cr_link     = $item['doi'];
            $cr_doi      = $this->get_doi($item['doi']);
            $cr_abstract = '';

            // Source infos
            //$jtitle = $pcoins['rft_jtitle']; // why again?
            $cr_date    = ''; // CR does not provide a date (for free?); only year via $pcoins['rft_date'] that's equal to $item['year']
            $cr_year    = $item['year'];
            $cr_vol     = (isset($pcoins['rft_volume'])) ? $pcoins['rft_volume'] : '';
            $cr_issue   = (isset($pcoins['rft_issue']))  ? $pcoins['rft_issue'] : '';
            $cr_page    = (isset($pcoins['rft_spage']))  ? $pcoins['rft_spage'] : '';
            $cr_date    = (isset($pcoins['rft_date']))  ? $pcoins['rft_date'] : '';
            $cr_source  = $pcoins['rft_jtitle'] . ", Vol. $cr_vol, No. $cr_issue ($cr_date)";

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


    /**
     * @brief   Delete expired cache files
     *
     * A cached file is old, if a file with the pattern "issn_getDate" does not exist,
     * but a file with "issn" is found.
     *
     * @note  2015-08-30: For now the only necessary information for deleting a file
     *        is the issn, because there only can be one toc. This would change if
     *        e.g. a (server side) language specific toc would be introduced. In this
     *        case the filename check should be adjusted.
     *
     * @param $cache_id    \b STR  Should be the issn for now
     * @return \b void
     */
    function delete_expired($cache_id) {
      $files = glob('../cache/*'.$cache_id.'*cache*'); // get all file names by pattern
      foreach($files as $file) {
        if(is_file($file)) unlink($file);
      }
    }

}
?>
