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
 
// All hope is in vain without an issn
$issn = (isset($_GET['issn'])) ? $_GET['issn'] : false;

// Make sure it is an issn (it's pretty pointless, since this script should never be called from a place without already sanitized data (like index.php)
if (strlen(preg_replace('/[\d-x]/i', '', $issn) > 0)) $issn = false;

if (!$issn) {
  echo "Oops, got no ISSN. this should never ever happen, you know :)";
  exit;
}

//TODO: to output the html head section right away so the browser
//can start loading css and js files while we work on the body

// Handle the pubdate. If none is send, this means caching is disabled
if (!isset($_GET['pubdate'])) {
  $age = -1; // Since the difference might be 0 days (today), we define false as -1
}
elseif ($_GET['pubdate'] == '1970-01-01') {
  $age = -1; // never cache if we got no real date
}
// Todo: This is pretty pointless. Only future use: _might_ be useful to use the age as additional info for the toc frame
else {
  $now = new DateTime('now');
  $pubdate = DateTime::createFromFormat('Y-m-d', $_GET['pubdate']);
  $age = $now->diff($pubdate)->format('%a');
}


// Prepare the cache file. Use url parameters to create unique id. Date identifies issue
$query          = md5(implode('', $_GET));
$cachefile = "../data/cache/toc-$query.cache.html";


// An age is available and cached file exists (-1 is the same as disabled caching)
// Issue date is same as in cache file name - load cache
if ($age > -1 && file_exists($cachefile)) {
  $toc = file_get_contents($cachefile);
}
// An age is available but no cached file exists
elseif ($age > -1) {
  // clean up and delete old toc's
  delete_expired($issn);
  
  $status = false;
  $toc = get_toc($issn, $status);
  // A toc was found, cache it
  if ($status) {
    file_put_contents($cachefile, $toc);
  }
}
// Ok, we got no age or caching is disabled by setting it to -1. Get toc the old way
else {
  $toc = get_toc($issn);
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
function get_toc($issn, &$status) {
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

  require_once('class.GetJournalToc.php');
  $getInfos = new GetJournalInfos();
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
function delete_expired($cache_id) {
  $files = glob('../cache/*'.$cache_id.'*cache*'); // get all file names by pattern
  foreach($files as $file) {
    if(is_file($file)) unlink($file);
  }
}
?>
