<?php
$issn = (isset($_GET['issn'])) ? $_GET['issn'] : false;
$age  = (isset($_GET['age']))  ? $_GET['age']  : -1;

if ($issn) {
  $query = rawurlencode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY));
  $cachefile = "../cache/toc-$issn_$query.cache.html";
  
  // An age is available and cached file exists
  // (-1 is the same as disabled caching)
  if ($age > -1 && file_exists($cachefile)) {
    // Issue age is greater than the age of the cached file - load cache
    if ($age >= date('a', strtotime(filemtime($cachefile)))) {
      $toc = file_get_contents($cachefile);
    }
    // Otherwise get fresh toc and save to file
    else {
      $toc = get_toc($issn);
      file_put_contents($cachefile, $toc);
    }
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
} else {
  echo "Oops, got no ISSN. this should never ever happen, you know :)";
}


/**
 * @brief   Get toc, pad it with some html and put it into an iframe
 *
 * @todo    Maybe use CDN for scripts
 *
 * @param $issn    \b STR  Journal ISSN
 * @return \b STR Some html
 */
function get_toc($issn) {
  require_once('class.getJournalInfos.php');
  $getInfos = new GetJournalInfos();
  echo '<!DOCTYPE html>
      <html><head>
      <link href="../css/foundation.min.css" rel="stylesheet">
      <link href="../foundation-icons/foundation-icons.css" rel="stylesheet">
      <link href="../css/local.css" rel="stylesheet">
      <script src="../js/vendor/jquery.js"></script>
      <script src="../js/vendor/jquery.timeago.js"></script>
</head><body>';
  $response = $getInfos->ajax_query_toc($issn);
  
  if (!$response) {
    $response = '<script>$(document).ready(window.parent.postMessage({"ready": false},"*"));</script></body>';
  } else {
    $response .= '<script src="../js/local/frame.js"></script></body>';
  }
  
  return $response;
}
?>
