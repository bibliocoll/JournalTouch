<?php
$issn = $_GET['issn'];

if ($issn) {
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
  echo $response;
}

?>
