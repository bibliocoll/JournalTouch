<?php
define("UPDATE", true);
require('../config.php');
require('update/class.JtUpdater.php');

$jtUpdater = new JtUpdater($cfg);
$status = $jtUpdater->start_update();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title><?php echo __('Journal Touch Upgrade') ?></title>
    <link rel="stylesheet" href="css/local.css" />
  </head>
<body>
    <h1>Updating JournalTouch</h1>
    <?php
        if ($status) {
            echo $jtUpdater->status_message;
            //echo $jtUpdater->status_log; //just for debugging
        } else {
            echo $jtUpdater->status_message;
            echo $jtUpdater->status_log;
        }
    ?>
</body>
</html>