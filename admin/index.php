<html>
  <head><title>JournalTouch Admin (Stub)</title></head>
<body>

<h1>Update Journal Infos</h1>
<p>This updates your journals.csv with the dates for each journal issue. If you chose to update, also some infos about publishers, legal infos and tags are fetched, currently as tags for the tagcloud in JournalTouch</p>
<p>You have two options:</p>
<ol>
  <li><a href="index.php?upd=WithMeta">Get recent issues and metadata</a> (Note: this is only done ther very first time you do this; or until you delete everything in the metaGotToc column in journals.csv)</li>
  <li><a href="index.php?upd=WithMeta">Get only recent issues</a> (Fast, same as above without fetching more infos)</li>
</ol>

<?php
$update = (isset($_GET['upd'])) ? $_GET['upd'] : false;

if ($update) {
  require_once('../sys/class.getJournalInfos.php');
  $getInfos = new GetJournalInfos();
}

if ($update == 'WithMeta') {
  $getInfos->update_journals_csv(true);
}
elseif ($update == 'RecentOnly') {
  $getInfos->update_journals_csv(false);
}

?>

<body>
</html>