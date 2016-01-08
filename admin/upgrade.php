<?php
define("UPGRADE", true);
require('../sys/bootstrap.php');
require('upgrade/class.JtUpgrader.php');

$jtUpgrader = new JtUpgrader($cfg);
$status = $jtUpgrader->start_upgrade();
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
    </head>
<body>
    <?php include('menu.inc') ?>
    <h2>Updating JournalTouch</h2>
    <?php
        if ($status) {
            echo $jtUpgrader->status_message;
            //echo $jtUpgrader->status_log; //just for debugging
        } else {
            echo $jtUpgrader->status_message;
            echo $jtUpgrader->status_log;
        }
    ?>
</body>
</html>