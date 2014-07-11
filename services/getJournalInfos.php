<?php
echo "<h1>Test</h1>";
$test = new GetJournalInfos();
$test->update_journals_csv();
//$test->update_journals_csv(false);


/**
 * @brief   Reads input.csv and adds data
 *
 * Update metadata (once) and fetch recent issues (do it daily via cron)
 *
 * @notes   Initial time for 1000 titles (fetching metadata and recent issues)
 *          takes about 18 Minutes. Yeah, hard, but I think it may be much
 *          faster most of the time - and the meta data has to be fetches only
 *          once. Stats in Detail
 * - Processed lines: 1071
 * - Hits JournalToc (meta): 637
 * - Hits JournalToc (new issues): 48
 * - Hits JournalToc (bad date): 35
 * - Hits CrossRef (meta): 29
 * - Hits CrossRef (new issues): 0
 * - Hits JournalSeek (meta only): 153
 * - This page was created in 1071.0866951942 seconds.
 *          Subsequent runs (checkingfor new issues) take about 3-4 minutes.
 *
 * @notes   Interesting stuff
 * - http://zetoc.mimas.ac.uk/ (uk only...)
 *
 * @todo
 * - Implement something like journaltoc_fetch_recent_premium to avoid the
 *   queries (besides getting better results). Should be easy, but I don't
 *   know how the premium output looks, yet.
 * - Maybe implement getting toc like in /ajax/*.php
 * - Maybe add interface class and create extended classes for each service...
 *
 * @author Tobias Zeumer <tzeumer@verweisungsform.de>
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
  //public $prefs;

  /// \brief \b STR The ISSN of the current journal. Maybe useful somewhere (currently only a warning if datediff is pretty high)
  protected $issn;
  /// \brief \b ARY Holds a complete joural row and its infos. Populated in GetJournalInfos::update_journals_csv
  protected $journal_row = array();
  /// \brief \b ARY Saves all rows. They are written to file only after everything is done (performance)
  protected $journals_buffer = array();

  // Vars to keep track of statistics
  private $hits_jt_meta = 0;
  private $hits_jt_new = 0;
  private $hits_jt_badDate = 0;
  private $hits_cr_meta = 0;
  private $hits_cr_new = 0;
  private $hits_cs_meta = 0;
  private $processed = 0;
  private $log = '';

  private $tmpDateLog = '';


  /**
   * @brief   Load config, set properties
   *
   * @return \b void
   */
  public function __construct() {
    set_time_limit($this->maxtime);
    $this->script_timer();

    require_once('../config.php');
    $this->csv_file = $cfg->csv_file;
    $this->csv_col  = $cfg->csv_col;
    $this->api_all  = $cfg->api->all;
    $this->jt       = $cfg->api->jt;
    //$this->prefs    = $cfg->prefs;
  }


  /**
  * @brief   Deconstructor. Shows script time...
  */
  function __destruct() {
    header('Content-Type: text/html; charset=utf-8');
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

    echo $this->tmpDateLog;
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
   * @todo    Make it better, e.g. writing false for metaGotToc isn't nice :)
   *
   * @param $fetch_meta \b STR  Fetch meta info from JournalToc (only if not
   *                            already done before).
   * @param $outfile    \b STR  Path and name of new file
   *
   * @return \b void
   */
  public function update_journals_csv($fetch_meta = true) {
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
        $issn = (strlen($this->journal_row['issn'] < 1) ? $this->journal_row['issn_alt'] : $this->journal_row['issn']);
        $this->issn = $issn;

        $already_checked = $this->journal_row['metaGotToc'];
        $already_checked = ($already_checked == 'JToc' || $already_checked == 'CRtoc' || $already_checked == 'false') ? true : false;

        // !!! Journaltoc: Update meta data if wanted AND if not done before (so JToc/CRtoc is an important information ;) !!!)
        if ($fetch_meta && !$already_checked && $this->jt->account) {
          $already_checked = $this->journaltoc_fetch_meta($issn, $this->jt->account);
        }
        // !!! Crossref: Update meta data if wanted AND if not done before (CRtoc) AND no result from JT
        if ($fetch_meta && !$already_checked) {
          $already_checked = $this->crossref_fetch_meta($issn);
        }
        // TEMP try journalseek if still no result
        if ($fetch_meta && !$already_checked) {
          $already_checked = $this->journalseek_fetch_meta($issn);
        }

        // Fetch info if journal is listed by JournalToc or CrossRef
        if ($this->journal_row['metaGotToc'] == 'JToc' && $this->jt->account) {
          $new_issue = $this->journaltoc_fetch_recent($issn, $this->jt->account);
        }
        elseif ($this->journal_row['metaGotToc'] == 'CRtoc') {
          $new_issue = $this->crossref_fetch_recent($issn);
        }


        // no matter if we got a hit or not - every line has to be written to the new csv...
        $new_row = implode(';', $this->journal_row);
        $this->journals_buffer[] = $new_row;

        $this->log .= '</p>';
      }
      fclose($handle);
      $file_date = date('Y-m-d_H\Hi');
      rename('../'.$this->csv_file->path, "../input/journals_$file_date.csv");

      $all_rows = implode("\n", $this->journals_buffer);
      file_put_contents ('../'.$this->csv_file->path, $all_rows);
    }
    ob_end_flush();
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

    foreach ($xml->item as $item) {
        $journal_date = $item->children('prism', TRUE)->publicationDate;
        $journal_date= str_replace("\n", '', $journal_date); // has line breaks sometimes?

        // weird?
        if (!$journal_date) {
          $this->log .= "<b>MESSY DATE for JT (recent)</b>: ".$this->journal_row['title']." ($issn) got a <a href=\"$jtURL\" target=\"_blank\">TOC</a> but no information about publishing date<br>";
          $this->hits_jt_badDate++;
          return false;
        }
        // whoa, even worse - the dates are not normalized
        // convert twice to check if it is ok...
        $chk_date = strtotime($journal_date);
        $chk_date = date('Y-m-d', $chk_date);
          //$this->tmpDateLog .= "$journal_date\$chk_date<br>";

        // What a messy check...
        if (date('Y', strtotime($chk_date)) == 1970) {
          // Check stuff like "Apr.-June  2014", "Apr.-June.  2014", "Jan.-Feb.  2015"
          $pos = strpos($journal_date , '.-');
          if ($pos > 0) {
            $fix_date = substr($journal_date , 0,  $pos);
            $fix_date .= substr($journal_date , -4);

            $chk_date = strtotime($fix_date);
            $chk_date = date('Y-m-d', $chk_date);
            if (date('Y', $chk_date) == 1970) $chk_date = false;
          }

          if (!$pos && !$chk_date) {
            $this->log .= "<b>MESSY DATE for JT (recent)</b>: ".$this->journal_row['title']." ($issn) got a <a href=\"$jtURL\" target=\"_blank\">TOC</a> but I can't handle this date: $journal_date<br>";
            $this->hits_jt_badDate++;
            return false;
          }
        }
        $journal_date = $chk_date;

        $is_new = $this->get_datediff($journal_date, $max_age_days);

        // Usually all dates should be the same, but see https://github.com/bibliocoll/JournalTouch/issues/5#issuecomment-48341283
        // Anyway, for now break if date was found
        if ($journal_date) break(1);
    }

    $this->journal_row['date'] = $journal_date;
    if ($is_new) {
      $this->journal_row['new'] = 'JTnew';
      $this->hits_jt_new++;
      $this->log .= "<b>JT (recent)</b>: ".$this->journal_row['title']." ($issn) was updated <a href=\"$jtURL\" target=\"_blank\"> within the last $max_age_days days</a><br>";
      return true;
    }
    else {
      $this->journal_row['new'] = '';
      $this->log .= "<b>JT (recent)</b>: ".$this->journal_row['title']." (<a href=\"$jtURL\" target=\"_blank\">$issn</a>) has no new update within the last $max_age_days days<br>";
      return false;
    }
  }


  /**
   * @brief   Fetches only journal infos from crossref
   *
   * Currently only doi...
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
   * - Maybe add (guess) update frequency to prevent unecessary checks?
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
    $current_issue = $coins['rft_date'].'/'.$coins['rft_volume'].'/'.$coins['rft_issue'];
    $current_date = date('Y-m-d');

    // Is this the first check?
    if ($this->journal_row['lastIssue']) {
      //$this->tmpDateLog .= $coins['rft_date'].'<br>';

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

    // Journal website
    $filtered = $domxpath->query("//dd[1]/ul/li/a/@href");
    if (is_object($filtered->item(0))) $js_link = $filtered->item(0)->nodeValue;

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
    $this->journal_row['metaGotToc'] = 'false';

    // Feedback
    if (isset($js_link) || $csv_tags) {
      $this->log .= "<b>MATCH for Jseek (meta)</b>: <a href=\"$js_link\" target=\"_blank\">".$this->journal_row['title']."</a><br>";
      $this->hits_cs_meta++;
      return true;
    }
    else {
      $this->log .= "<b>NO HIT for Jseek (meta)</b>: ".$this->journal_row['title']." ($issn)<br>";
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
}
?>
