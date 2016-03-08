<?php
ob_start();
require(__DIR__.'/../sys/bootstrap.php');
$btn_cache  = (isset($_GET['clr_cache'])) ? true : false;
$btn_upd    = (isset($_GET['upd'])) ? true : false;
$optMeta    = (isset($_GET['optMeta'])) ? true : false;
$optRecent  = (isset($_GET['optRecent'])) ? true : false;
$optTags    = (isset($_GET['optTags'])) ? true : false;
$optCovers  = (isset($_GET['optCovers'])) ? true : false;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <title>JournalTouch Settings</title>
        <link rel="stylesheet" href="../css/foundation.min.css" />
        <link rel="stylesheet" href="../css/foundation-icons/foundation-icons.css" />
        <script src="../js/vendor/jquery.js"></script>
        <script src="../js/foundation.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                // Load foundation
                $(document).foundation();
            });
        </script>
    </head>
<body>
    <?php include('menu.inc') ?>
    <h2>Update Journal Infos</h2>

<?php
$del_message = '';
if ($btn_cache) {
    $files = glob($cfg->sys->data_cache.'*.cache*'); // get all file names by pattern
    $i = 0;
    foreach($files as $file) {
        if(is_file($file)) {
            unlink($file);
            $i++;
        }
    }
    $del_message = "<strong>Sucess: $i files deleted</strong>";
}
?>

    <form method="get" action="updateTocs.php">
    <div class="row">
        <div class="large-6 columns left">
            <fieldset>
            <!-- Quick & Dirty - Update input.csv -->
                <legend><b><?php echo __('Update options') ?></b></legend>
                <div class="panel"><?php echo __('This updates your journals.csv with the dates for each journal issue. If you chose to fetch metadata, also some infos about publishers, legal infos and tags are fetched, mostly as tags for the tagcloud in JournalTouch. Be aware: metadata is only fetched the very first time. If you really want to redo it, you have to delete everything in column 14 ("JToc", "Jseek", "CRtoc").') ?></div>
                <label for="optMeta"><input type="checkbox" name="optMeta" checked="checked"> <?php echo __('Fetch metadata') ?><br>
                <label for="optRecent"><input type="checkbox" name="optRecent" checked="checked"> <?php echo __('Fetch recent issues') ?><br>
                <label for="optCovers"><input type="checkbox" name="optCovers" checked="checked"> <?php echo __('Download/update covers') ?><br>
                <label for="optTags"><input type="checkbox" name="optTags"> <?php echo __('Clean tags (experimental)') ?><sup>1</sup><br>
                <button name="upd" value="true" type="submit">Start</button>
                <br /><br />
                <sup>1</sup><?php echo __('You have to edit data/journals/tag-remap.txt. Format: "oldTag;newTag"') ?>
            </fieldset>
        </div>
        <div class="large-6 columns right">
            <fieldset>
                <!-- Quick & Dirty - remove cached files -->
                <legend><b><?php echo __('Clear Cache') ?></b></legend>
                <div class="panel"><?php echo __('If you enabled the caching in the config, you can clear it here') ?></div>
                <button type="submit" name="clr_cache" value="true"><?php echo __('Clear Cache') ?></button> <?php echo $del_message; ?>
            </fieldset>
            <fieldset>
                <!-- Quick & Dirty - Update input.csv -->
                <legend><b><?php echo __('JournalTOC Premium Update') ?></b></legend>
                <div class="panel"><?php echo __('This updates your updates.json.txt with the dates for recent issue.') ?></div>
                <a href="services/getLatestJournals.php" class="button"><?php echo __('Update Premium') ?></a>
            </fieldset>
    </div>
    </form>

<?php
if ($btn_upd) {

    echo '<div style="clear:both"><h2>Log</h2>';
    require_once($cfg->sys->basepath.'admin/services/class.UpdateInputCsv.php');
    $getInfos = new GetJournalInfos($cfg);
    $getInfos->update_journals_csv($optMeta, $optRecent, $optTags, $optCovers);
    echo $getInfos->log;
    echo '</div>';
}
ob_end_flush();
?>

<body>
</html>
