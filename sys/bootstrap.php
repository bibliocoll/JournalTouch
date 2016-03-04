<?php
/**
 * Bootstrap everything needed for running JournalTouch. Always include it!
 *
 * A central place to do everything needed after including the config file and
 * before a distinct php page is loaded. Do stuff a user should not see in the
 * config, but that is necessary before processing any script
 *
 * Time-stamp: "2015-08-30 2:25:00 zeumer"
 *
 * @author Tobias Zeumer <tobias.zeumer@tuhh.de>
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 *
 * @todo (Ideas)
 * - Maybe use autoload
 *
 * @todo (Improve)
 * -  Include position in config-default.php sucks, but it has to be after the
 *    the language definitions and before the first use of gettext. Maybe the
 *    very best option would be to create a real config page in admin/
 * - Add this to a function? Make it better readable
 */
// All important basic functions in a separate file - keep the overview
require_once('bootstrap.functions.php');

// Load THE config
$cfg = cfg_load();

// Define the basepath of JT
$cfg->sys->basepath  = realpath( __DIR__ ) .'/../';    // absolute path to JournalTouch directory; DONT' CHANGE

// Set language
$cfg->prefs->current_lang   = (isset($_GET['lang']) && $_GET['lang'] != '') ? $_GET['lang'] : $cfg->prefs->language_default;
require_once($cfg->sys->basepath.'sys/jt-gettext.php');

// Set current version
$cfg->sys->current_jt_version = 0.4;

// Check if upgrade is required
if (check_upgrade_required($cfg) && !defined('UPGRADE')) {
    echo 'JournalTouch has to be upgraded. Please go to <a href="admin/upgrade.php">Admin Upgrader</a>';
    exit;
}

// Check if admin folder is secured by htaccess
$cfg->sys->adminSecured = file_exists($cfg->sys->basepath.'admin/.htaccess');


// Honor user choices for paths; else set default ones
$cfg->sys->data_cache    = ($cfg->sys->data_cache_usr)     ? $cfg->sys->data_cache_usr      : $cfg->sys->basepath.'data/cache/';
$cfg->sys->data_covers   = ($cfg->sys->data_covers_usr)    ? $cfg->sys->data_covers_usr     : 'data/covers/';
$cfg->sys->data_export   = ($cfg->sys->data_export_usr)    ? $cfg->sys->data_export_usr     : $cfg->sys->basepath.'data/export/';
$cfg->sys->data_journals = ($cfg->sys->data_journals_usr)  ? $cfg->sys->data_journals_usr   : $cfg->sys->basepath.'data/journals/';


// @deprecated 2015-08-30: Currently there is no use to force deletion of cached files, since ajax_toc.php handles it well to make sure a cache is valid
$cfg->prefs->cache_max_age = "365 days";     // files older than this are purged when getLatestJournals is run. format: http://php.net/manual/en/dateinterval.createfromdatestring.php

// Output files and paths - there is no point to bother a user with changing it
$cfg->api->jt->outfile  = $cfg->sys->data_journals.'updates.json.txt';   // Premium: The file the updates are saved to temporarily. You'll have to run services/getLatestJournals.php regularly

$cfg->csv_file = new stdClass();
/**
* Which file with your journals information and what separator is used.
* Usually you won't have to change anything here.
*/
$cfg->csv_file->separator  = ';';
$cfg->csv_file->path       = $cfg->sys->data_journals.'journals.csv';

// Sanitize GET and POST
sanitize_request();

?>