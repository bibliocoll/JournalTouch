<?php
$issn = $_GET['issn'];

if ($issn) {
  require_once('class.getJournalInfos.php');
  $getInfos = new GetJournalInfos();
  echo $getInfos->ajax_query_toc($issn);
}

?>