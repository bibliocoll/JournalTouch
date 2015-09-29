<?php
/**
 * Get journal toc via ajax as iframe html by issn. 
 * @todo:
 */

require_once('./bootstrap.php'); // sanitize requests and all that

// All hope is in vain without an issn
$issn = (isset($_GET['issn'])) ? $_GET['issn'] : false;
if (!$issn || !valid_issn($issn, TRUE)) {
  echo 'Thats no ISSN, thats a ...battle station?';
  exit;
} else {
$caching = (isset($_GET['cache']) && $_GET['cache'] === '1');
$age = -1; // Since the difference might be 0 days (today), we define false as -1
//TODO: handle pubdate in the future correctly
if (isset($_GET['pubdate']) && $_GET['pubdate'] !== '' && $_GET['pubdate'] !== '1970-01-01') {
  if ($pubdate && DateTime::getLastErrors()['error_count'] = 0) {
    //%a creates a positive integer even for a pubdate in the future
    //@see: http://php.net/manual/en/dateinterval.format.php
    $age = $now->diff($pubdate)->format('%a');
  }
}
if ($caching) {
  // Prepare the cache file. Use url parameters to create unique id. Date identifies issue
  $query = md5(implode('', $_GET));
  $cachefile = $cfg->sys->basepath."cache/toc-{$issn}+{$query}.cache.html";
  if (file_exists($cachefile)) {
    // Issue date is same as in cache file name - load cache
    echo file_get_contents($cachefile);
    exit(0); //end script before all the require statements
  }

require_once($cfg->sys->basepath.'config.php');
require_once($cfg->sys->basepath.'sys/class.GetJournalToc.php');
$status = false;
$toc = get_toc($issn, $status, $cfg);

if ($status && $caching) {
  // clean up and delete old tocs
  delete_expired($issn, $cfg);
  file_put_contents($cachefile, $toc);
}
function get_toc($issn, &$status, $cfg) {
  //GetJournalInfos class requires the config to be present
  //so we only load it if we actually need to instantiate the class
  //TODO: unsure about scoping, might make sense to move the imports to the main part of this script
  $getInfos = new GetJournalInfos($cfg);
  
  // And return it...
  echo $toc;
}

function delete_expired($cache_id, $cfg) {
  $files = glob($cfg->sys->basepath.'cache/*'.$cache_id.'*cache*'); // get all file names by pattern
?>
