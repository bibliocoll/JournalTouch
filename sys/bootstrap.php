<?php
/**
 * Bootstrap everything needed for running JournalTouch
 *
 * A central place to do everything needed after including the config file and 
 * before a distinct php page is 
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
 */
// Define the basepath of JT  
$cfg->sys = new stdClass();
$cfg->sys->basepath  = dirname(__FILE__).'/../';    // absolute path to JournalTouch directory
require_once($cfg->sys->basepath.'sys/bootstrap.functions.php');

 
//Sanitize 
sanitize_request();


# Do stuff a user should not see in the config, but that is necessary before 
# processing any script
# @todo: Add this to a function? Make it better readable

// Set language
$cfg->prefs->current_lang   = (isset($_GET['lang']) && $_GET['lang'] != '') ? $_GET['lang'] : $cfg->prefs->languages[0];
require_once($cfg->sys->basepath.'sys/jt-gettext.php');

// @deprecated 2015-08-30: Currently there is no use to force deletion of cached files, since ajax_toc.php handles it well to make sure a cache is valid
$cfg->prefs->cache_max_age = "365 days";     // files older than this are purged when getLatestJournals is run. format: http://php.net/manual/en/dateinterval.createfromdatestring.php


// Output files and paths - there is no point to bother a user with changing it
$cfg->api->jt->outfile  = $cfg->sys->basepath.'data/journals/updates.json.txt';   // Premium: The file the updates are saved to temporarily. You'll have to run admin/services/getLatestJournalTocPremium.php regularly


$cfg->csv_file = new stdClass();
/**
 * Which file with your journals information and what separator is used.
 * Usually you won't have to change anything here.
 */
$cfg->csv_file->separator  = ';';
$cfg->csv_file->path       = $cfg->sys->basepath.'data/journals/journals.csv';

?>