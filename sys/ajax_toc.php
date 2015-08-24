<?php
// All hope is in vain without an issn
$issn = (isset($_GET['issn'])) ? $_GET['issn'] : false;

if (!$issn) {
  echo "Oops, got no ISSN. this should never ever happen, you know :)";
  exit;
}


// Handle the pubdate. If none is send, this means caching is disabled
if (!isset($_GET['pubdate'])) {
  $age = -1; // Since the difference might be 0 days (today), we define false as -1
} 
// Todo: This is pretty pointless. Only future use _might_ be to use the age as additional info for the toc frame
else {
  $now = new DateTime('now');
  $pubdate = DateTime::createFromFormat('Y-m-d', $_GET['pubdate']); 
  $age = $now->diff($pubdate)->format('%a');  
}


// Prepare the cache file. Use url parameters to create unique id. Date identifies issue
$query = implode('_', $_GET);
$cachefile = "../cache/toc-$query.cache.html";


// An age is available and cached file exists (-1 is the same as disabled caching)
// Issue date is same as in cache file name - load cache
if ($age > -1 && file_exists($cachefile)) {
  $toc = file_get_contents($cachefile);
}
// An age is available but no cached file exists
elseif ($age > -1) {
  $toc = get_toc($issn);
  file_put_contents($cachefile, $toc);
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
 *
 * @param $issn    \b STR  Journal ISSN
 * @return \b STR Some html
 */
function get_toc($issn) {
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

  require_once('class.getJournalInfos.php');
  $getInfos = new GetJournalInfos();
  $response = $getInfos->ajax_query_toc($issn);
  
  // Hack for non-iframe version
  if (isset($_GET['noframe'])) return $response;
  
  if (!$response) {
    $response = $html_prefix.$html_postfix_er;
  } else {
    $response = $html_prefix.$response.$html_postfix_ok;
  }
  
  return $response;
}
?>
