<?php
error_reporting(E_ALL);

echo "<h1>Test</h1>";
$test = new GetJournalInfos();
$test->update_journals_csv();


/**
 * @brief   Reads input.csv and adds data
 *
 * Currently mainly for "single use" (update infos once).
 *
 * @notes  1000 rows in journals.csv are processed in about 8 minutes using only
 *         JournalToc and using file_put_contents for each row
 *         About 5 Minutes using buffer and file_put_contents only once (verify,
 *         test...?)
 *
 * @todo:   Add some versioning etc.
 */
class GetJournalInfos {
  protected $jt_api_key;
  protected $journals_csv;
  protected $csv_separator;

  protected $csv_col_ids = array();

  protected $journal_row = array();
  protected $issn;

  /// \brief \b FLOAT Script timing is always fun.
  private $starttime = 0;
  /// \brief \b INT Maximum script execution time. Firefox default for network.http.keep-alive.timeout is 115
  private $maxtime = 6000;

  protected $journals_csv_updated;
  protected $journals_buffer = array();

  private $hits_jt = 0;
  private $hits_cr = 0;
  private $processed = 0;


  /**
   * @brief   Load config, set properties
   *
   * @param $conf_file    \b STR  Path to config file
   * @return \b void
   */
  public function __construct($conf_file = '../config/config.ini') {
    set_time_limit($this->maxtime);
    $this->script_timer();


    $config = parse_ini_file($conf_file, TRUE);

    $this->jt_api_key    = $config['journaltocs']['apiUserKey'];
    $this->journals_csv  = $config['csv']['file'];
    $this->csv_separator = $config['csv']['separator'];

    $this->csv_col_ids['title']         = $config['csv']['title'];          // = 0
    $this->csv_col_ids['filter']        = $config['csv']['filter'];         // = 1
    $this->csv_col_ids['2']             = 2;                                //?
    $this->csv_col_ids['important']     = $config['csv']['important'];      // = 3
    $this->csv_col_ids['4']             = 4;                                // = 4
    $this->csv_col_ids['issn']          = $config['csv']['issn'];           // = 5
    $this->csv_col_ids['issn_alt']      = $config['csv']['issn_alt'];       // = 6
    $this->csv_col_ids['7']             = 7;                                //?
    $this->csv_col_ids['8']             = 8;                                //?
    $this->csv_col_ids['date']          = $config['csv']['date'];           // = 9
    $this->csv_col_ids['metaPrint']     = $config['csv']['metaPrint'];      // = 10
    $this->csv_col_ids['metaOnline']    = $config['csv']['metaOnline'];     // = 11
    $this->csv_col_ids['metaGotToc']    = $config['csv']['metaGotToc'];     // = 12
    $this->csv_col_ids['metaShelfmark'] = $config['csv']['metaShelfmark'];  // = 13
    $this->csv_col_ids['tags']          = $config['csv']['tags'];           // = 14

    // just copy, content will be overwritten
    $this->journal_row = $this->csv_col_ids;
  }


  /**
  * @brief   Deconstructor. Shows script time...
  */
  function __destruct() {
    echo '<pre>';
    echo 'Processed lines: '.$this->processed;
    echo '<br>Hits JournalToc: '.$this->hits_jt;
    echo '<br>Hits CrossRef: '.$this->hits_cr;
    echo '<br>This page was created in ' . $this->script_timer() . ' seconds.';
  }


  /**
   * @brief   Simpyl takes the current input file and writes a new one with
   *          additional data.
   *
   * @note    CrossRef is commented out
   * @todo    Make it better :)
   *
   * @param $outfile    \b STR  Path and name of new file
   * @return \b void
   */
  public function update_journals_csv($outfile = '../input/journals_new.csv') {
    if (file_exists($outfile)) unlink($outfile);
    $this->journals_csv_updated = $outfile;

    if (($handle = fopen('../'.$this->journals_csv, "r")) !== FALSE) {
      while (($journal_rows = fgetcsv($handle, 1000, $this->csv_separator)) !== FALSE) {
        $this->processed++;

        // make row class property
        foreach ($this->csv_col_ids AS $name => $rowid) {
          $this->journal_row[$name] = $journal_rows[$rowid];
        }

        /* check for alternative ISSN if strlen is < 1 */
        $this->issn = (strlen($this->journal_row['issn'] < 1) ? $this->journal_row['issn_alt'] : $this->journal_row['issn']);

        $hit = $this->fetch_from_JournalToc($this->issn, $this->jt_api_key);
        //if (!$hit) $hit = $this->fetch_from_crossref($this->issn);

        // no matter if we got a hit or not - every line has to be written to the new csv...
        $new_row = implode(';', $this->journal_row);
        $this->journals_buffer[] = $new_row;

        //if ($this->processed > 2) break(1);
      }
      $all_rows = implode("\n", $this->journals_buffer);
      file_put_contents ($this->journals_csv_updated, $all_rows);
    }
  }


  /**
   * @brief   Fetches only journal infos from Journaltoc
   *
   * Currently putting publisher, topic and journal rights into tags with "JT..."
   * prefix; might be useful....
   * Journal HP goes to "metaOnline" column - might use it in iframe. And yep,
   * this column is a bad choice; it should go into a new column...
   *
   * @todo  Keep parameters and add $row so this method can be used "alone"?
   *
   * @param $issn    \b STR  Journal ISSN
   * @param $user    \b STR  JournalToc user
   * @return \b BOL True if journal is found, else false
   */
  public function fetch_from_JournalToc($issn, $user) {
    $jtURL = "http://www.journaltocs.ac.uk/api/journals/$issn?user=$user";
    $xml = simplexml_load_file($jtURL);

    if (!is_object($xml)) return false;

    if ($xml->item->children('dc', TRUE)->title) {
      $jt_title     = $xml->item->children('dc', TRUE)->title;
      $jt_link      = $xml->item->link;
      $jt_pIssn     = $xml->item->children('prism', TRUE)->issn;
      $jt_eIssn     = $xml->item->children('prism', TRUE)->eIssn;
      $jt_publisher = $xml->item->children('dc', TRUE)->publisher;
      $jt_subjects  = str_replace(" ", "_", ucwords(strtolower($xml->item->children('dc', TRUE)->subject)));
      $jt_rights    = $xml->item->children('dc', TRUE)->rights;
      // echo "<a href=\"$jt_link\" target=\"_blank\">$jt_title (=".$this->journal_row['title'].")</a> (p: $jt_pIssn /e: $jt_eIssn) von $jt_publisher ($jt_rights). Thema: $jt_subjects (<a href=\"$jtURL\" target=\"_blank\">JT</a>).<br>";

      $csv_tags = '';
      if ($jt_publisher) $newTags[] = 'JTpub-'.$jt_publisher;
      if ($jt_subjects)  $newTags[] = 'JTtag-'.$jt_subjects;
      if ($jt_rights)    $newTags[] = 'JTlegal-'.$jt_rights;
      if ($newTags) $csv_tags = implode(', ', $newTags);

      $this->journal_row['tags'] = ($this->journal_row['tags']) ? $this->journal_row['tags'].', '.$csv_tags : $csv_tags;
      if ($jt_link) $this->journal_row['metaOnline'] = $jt_link;
      $this->journal_row['metaGotToc'] = 'JToc';

      $this->hits_jt++;
      return true;
    }
    else {
      $this->journal_row['metaGotToc'] = '';
      echo "<b>None JT</b>: ".$this->journal_row['title']." ($issn)<br>";
      return false;
    }
  }


  /**
   * @brief   Fetches only journal infos from crossref
   *
   * Currently only doi...
   * Journal HP goes to "metaOnline" column - might use it in iframe. And yep,
   * this column is a bad choice; it should go into a new column...
   *
   * @todo
   * - Check if works at all ;)
   * - Keep parameters and add $row so this method can be used "alone"?
   *
   * @param $issn    \b STR  Journal ISSN
   * @return \b BOL True if journal is found, else false
   */
  public function fetch_from_crossref($issn) {
    $crURL  = "http://search.crossref.org/dois?type=Journal&q=$issn";
    $crJson = file_get_contents($crURL);

    $crJournal = json_decode ($crJson, true);

    $crLink = false;
    if (isset($crJournal['doi'])) $crLink = $cfJournal['doi'];
    if (isset($crJournal['fullCitation'])) $crTitle = $crJournal['fullCitation'];

    if ($crLink) {
      $this->journal_row['metaOnline'] = $crLink;
      $this->journal_row['metaGotToc'] = 'toc';
      echo "<a href=\"$crLink\" target=\"_blank\">$crJournal (=".$this->journal_row['title'].")</a>.<br>";

      $this->hits_cr++;
      return true;
    }
    else {
      $this->journal_row['metaGotToc'] = '';
      echo "<b>None CrossRef</b>: ".$this->journal_row['title']." ($issn)<br>";
      return false;
    }
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

