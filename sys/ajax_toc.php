<?php
/**
 * Get journal toc by issn. Use cache, if nothing changed since last call.
 *
 * @todo:
 * -  Think about including config.php to use the GET sanitizing
 *    > BUT: This adds serious overhead
 *    > BUT: For now only the issn is checked - imho in a sufficient way
 *           (pubdate is checked implicitly)
 *
 * @note  2015-08-30: For now the only necessary information for deleting a file
 *        is the issn, because there only can be one toc. This would change if
 *        e.g. a (server side) language specific toc would be introduced. In this
 *        case the filename check should be adjusted.
 */

require_once('./bootstrap.php'); // sanitize requests and all that

// All hope is in vain without an issn
$issn = (isset($_GET['issn'])) ? $_GET['issn'] : false;
if (!$issn || !valid_issn($issn, TRUE)) {
  echo 'Thats no ISSN, thats a ...battle station?';
  exit;
}

$caching = (isset($_GET['cache']) && $_GET['cache'] === '1');

$age = -1; // Since the difference might be 0 days (today), we define false as -1
//TODO: handle pubdate in the future correctly
if (isset($_GET['pubdate']) && $_GET['pubdate'] !== '' && $_GET['pubdate'] !== '1970-01-01') {
  $now = new DateTime('now');
  $pubdate = DateTime::createFromFormat('Y-m-d', $_GET['pubdate']);
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
}

require_once($cfg->sys->basepath.'config.php');
require_once($cfg->sys->basepath.'sys/class.GetJournalToc.php');
$status = false;
$toc = get_toc($issn, $status, $cfg);

if ($status && $caching) {
  // clean up and delete old tocs
  delete_expired($issn, $cfg);
  // A toc was found, cache it
  file_put_contents($cachefile, $toc);
}

// And return it...
echo $toc;


/**
 * @brief   Get toc, pad it with some html and put it into an iframe
 *
 * @todo    Maybe use CDN for scripts
 * @todo    2015-08-22: Remove hack for old non-iframe version if it finally
 *          gets removed from conduit.js
 * @todo    2015-08-30: Maybe remove the $status as reference and use array instead
 *
 * @param $issn    \b STR  Journal ISSN
 * @return \b STR Result as HTML; you may pass some variable as reference to get the status too
 */
function get_toc($issn, &$status, $cfg) {
  $html_prefix = '<!DOCTYPE html>
      <html><head>
      <link href="../css/foundation.min.css" rel="stylesheet">
      <link href="../foundation-icons/foundation-icons.css" rel="stylesheet">
      <link href="../css/local.css" rel="stylesheet">
      <script src="../js/vendor/jquery.js"></script>
      <script src="../js/vendor/jquery.timeago.js"></script>
</head><body>';
  $html_postfix_ok = '<script src="../js/local/frame.js"></script></body></html>';
  $html_postfix_er = '<script>$(document).ready(window.parent.postMessage({"ready": false},"*"));</script></body></html>';

  //GetJournalInfos class requires the config to be present
  //so we only load it if we actually need to instantiate the class
  //TODO: unsure about scoping, might make sense to move the imports to the main part of this script
  $getInfos = new GetJournalInfos($cfg);
  $result = $getInfos->ajax_query_toc($issn);

  // Hack for non-iframe version
  if (isset($_GET['noframe'])) return $result;

  if (!$result) {
    $result = $html_prefix.$html_postfix_er;
    $status = false;
  } else {
    $result = $html_prefix.$result.$html_postfix_ok;
    $status = true;
  }

  return $result;
}


/**
 * @brief   Delete expired cache files
 *
 * A cached file is old, if a file with the pattern "issn_getDate" does not exist,
 * but a file with "issn" is found.
 *
 * @param $cache_id    \b STR  Should be the issn for now
 * @return \b void
 */
function delete_expired($cache_id, $cfg) {
  $files = glob($cfg->sys->basepath.'cache/*'.$cache_id.'*cache*'); // get all file names by pattern
  foreach($files as $file) {
    if(is_file($file)) unlink($file);
  }
}
?>
