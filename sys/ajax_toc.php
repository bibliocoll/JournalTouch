<?php
$issn = (isset($_GET['issn'])) ? $_GET['issn'] : false;
$age  = (isset($_GET['age']))  ? $_GET['age']  : -1;

function get_toc($issn) {
  require_once('class.getJournalInfos.php');
  $getInfos = new GetJournalInfos();
  return $getInfos->ajax_query_toc($issn);
}

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
  echo "Oops, got no ISSN. this should never ever happen, you know :)"
}

?>