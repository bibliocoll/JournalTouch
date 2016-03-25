<?php
/**
 * Run a regular check against JournalTocs for updates and store it to a JSON file
 * this is read by sys/class.ListJournals.php (getJournalUpdates())
 * You may also want to include the output file via AJAX queries for example
 * a JournalTOCs Premium API account is essential for this service (or write a similar service)
 * Time-stamp: "2014-07-10 12:44:40 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */
// do not show system errors, these should be handled in js or below
//error_reporting(0);

require_once(__DIR__.'/../../sys/bootstrap.php');
if (!isset($cfg)) { die('$cfg not set!'); }
require_once($cfg->sys->basepath . 'sys/class.ListJournals.php');
/* setup methods & objects */
$lister = new ListJournals($cfg);
$journals = $lister->getJournals();
$updatesURL = $cfg->api->jt->updates . $cfg->api->jt->account;

//$issn = $_GET['issn']; //where did that come from? oO

$jsonFile = $cfg->api->jt->outfile;


function myget ($query,$xpath) {
  $result=array();

  foreach ($xpath->query($query) as $item) {
    if (!empty($item->nodeValue)) { $result[]=trim($item->nodeValue);}
  }

  switch (sizeof($result)) {
  case 0: return ""; break; // if empty
  case 1: return $result[0]; break; // single
  default: return $result; // multiple
  }
}


function search_array($needle, $haystack) {
  if(in_array($needle, $haystack)) {
    return true;
  }
  foreach($haystack as $element) {
    if(is_array($element) && search_array($needle, $element))
      return true;
  }
  return false;
}

/**
 * @brief   Remove cached files by age
 *
 * @deprecated  2015-08-30: I seriously think it has no use currently.
 *              Caching is handled intelligently enough. Also, if there is a
 *              good reason to do it anyway, it shouldn't be done here only,
 *              but in class.getJournalInfos.php as well
 */
function remove_ancient_cache_files() {
  global $cfg;
  $files = glob($cfg->sys->data_cache.'*.cache*'); // get all file names by pattern
  $now = new DateTime(); //very OO
  $age = isset($cfg->prefs->cache_max_age)? DateInterval::createFromDateString($cfg->prefs->cache_max_age) : DateInterval::createFromDateString("33 days");
  $threshold = date_timestamp_get( $now->sub($age) ); //no longer very OO
  foreach($files as $file) {
    if (is_file($file) && (filemtime($file) < $threshold )) unlink($file);
  }
}

// fresh array:
$upd = array();

/* query loop for multiple update URLs from config */
if (!is_array($updatesURL)) {
  $updatesURL = array($updatesURL);
}
foreach ($updatesURL as $updateURL) {

  echo "querying ".$updateURL." ...";

  $neuDom = new DOMDocument;
  $neuDom->load($updateURL);
  $xpath = new DOMXPath( $neuDom );

  $rootNamespace = $neuDom->lookupNamespaceUri($neuDom->namespaceURI);
  $xpath->registerNamespace('x', $rootNamespace);

  // get the title second option (incl. vol/no = snatch from first item & cut pages) (beware!)
  $journalTitle = myget("//x:item[1]/dc:source",$xpath);
  $journalTitle = preg_replace('/pp\..+/','',$journalTitle);
  if (empty($journalTitle)) { $journalTitle = myget("//x:channel/x:title",$xpath); }  // more robust
  $records = $xpath->query("//x:item");
  $toc = array();

  foreach ( $records as $item ) {
    $newDom = new DOMDocument;
    $newDom->appendChild($newDom->importNode($item,true));

    $xpath = new DOMXPath( $newDom );
    $rootNamespace = $newDom->lookupNamespaceUri($newDom->namespaceURI);
    $xpath->registerNamespace('x', $rootNamespace);
    $xpath->registerNamespace("dc","http://purl.org/dc/elements/1.1/");
    $xpath->registerNamespace("prism","http://prismstandard.org/namespaces/1.2/basic/");

    $title = myget("//x:title",$xpath);
    $link = myget("//x:link",$xpath);
    $issn = myget("//prism:issn",$xpath);
    $eIssn = myget("//prism:eIssn",$xpath);
    // write only one $issn
    $issn = valid_issn($issn, TRUE) === TRUE ? $issn : valid_issn($eIssn, TRUE) === TRUE ? $eIssn : '';
    $date = myget("//dc:date",$xpath);
    if (!isset($date) || $date === '') $date = date('c');

    $abstract = myget("//x:description",$xpath);

    $toc[] = array(
      'title' => $title,
      'link' => $link,
      'issn' => $issn,
      //'eIssn' => $eIssn,
      'date' => $date
    );
  }

  if (empty($toc)) {
    echo 'ERROR!';
  } else {

    $heading = __('Most recent journal updates from the last PLACEHOLDER days');
    $heading = str_replace('PLACEHOLDER', $cfg->api->all->is_new_days, $heading);
    echo '<h5>'.$heading.'</h5>';

    foreach ( $toc as $item ) {

      if (!empty($item['title'])) {

        if ($item['issn'] !== '') {
          if (search_array($item['issn'], $journals)) {

            $date = new DateTime($item['date']);
            /* unless we use PHP 5.3 (with DateTime::sub), we need to add a timespan for comparison */
            $td = strtotime($item['date']);
            $cdate = date("Y-m-d", strtotime("+{$cfg->api->all->is_new_days} day", $td));
            /* compare with current date */
            $curDate = new DateTime(); // today
            $myDate   = new DateTime($cdate);

            /* only store current dates (not older than the given date in $cdate) */
            if ($myDate >= $curDate) {
              print $item['title'] . ", last update on ".$item['date']."<br/>";
              // save to array
              array_push($upd, array(
                'issn' => $item['issn'],
                'date' => $item['date'],
                'title' => $item['title'],
                'timestr' => date('c', strtotime($item['date']))
              ));
            }
          }
        }
      }
    }
  }


  /* new $data */
  echo ' <strong>done.</strong><br/>';
  echo '<p><hr/></p>';
}

//save updated data
file_put_contents($jsonFile,json_encode($upd));

//do some housekeeping
remove_ancient_cache_files();
?>
