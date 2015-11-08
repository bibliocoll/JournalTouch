<?php
define("UPDATE", true);
require('../config.php');
require('update/class.JtUpdater.php');

$jtUpdater = new JtUpdater($cfg);
$status = $jtUpdater->start_update();
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
            echo $jtUpdater->status_message;
            //echo $jtUpdater->status_log; //just for debugging
        } else {
            echo $jtUpdater->status_message;
            echo $jtUpdater->status_log;
        }
    ?>
</body>
</html>