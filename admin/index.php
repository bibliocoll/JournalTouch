<html>
  <head><title>JournalTouch Admin (Stub)</title></head>
<body>

<h1>Update Journal Infos</h1>
<p>This updates your journals.csv with the dates for each journal issue. If you chose to update, also some infos about publishers, legal infos and tags are fetched, currently as tags for the tagcloud in JournalTouch</p>
<p>Your options:</p>

<?php
$upd = (isset($_GET['upd'])) ? true : false;
$optMeta    = (isset($_GET['optMeta'])) ? true : false;
$optRecent  = (isset($_GET['optRecent'])) ? true : false;
$optTags    = (isset($_GET['optTags'])) ? true : false;

if ($upd) {
  require_once('../sys/class.getJournalInfos.php');
  $getInfos = new GetJournalInfos();
  $getInfos->update_journals_csv($optMeta, $optRecent, $optTags);
}

?>
<div style="border: thin solid #000000; width:400px; padding:10px;">
  <form method="get" action="index.php">
    <fieldset>
      <legend><b>Update options</b></legend>
      <label for="optMeta"><input type="checkbox" name="optMeta" checked="checked"> Fetch metadata<br>
      <label for="optRecent"><input type="checkbox" name="optRecent" checked="checked"> Fetch recent issues<br>
      <label for="optTags"><input type="checkbox" name="optTags"> Clean tags (experimental)<sup>1</sup><br>
    </fieldset>
    <sup>1</sup>You have to edit input/tag-remap.txt. Format: "oldTag;newTag"
    <button name="upd" value="true" type="submit">Start</button>
  </form>
</div>

<body>
</html>