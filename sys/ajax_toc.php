<?php
/**
 * Get journal toc via ajax as iframe html by issn.
 */

// All hope is in vain without an issn
$issn = (isset($_GET['issn'])) ? $_GET['issn'] : false;

// Make sure it is an issn (it's pretty pointless, since this script should never be called from a place without already sanitized data (like index.php)
if (strlen(preg_replace('/[\d-x]/i', '', $issn) > 0)) $issn = false;

if (!$issn) {
  echo "Oops, got no ISSN. this should never ever happen, you know :)";
  exit;
} else {
  require_once('class.GetJournalToc.php');
  $getInfos = new GetJournalInfos();
  $toc = $getInfos->ajax_query_toc($issn);

  // And return it...
  echo $toc;
}

?>
