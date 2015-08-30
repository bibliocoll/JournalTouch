<?php
// define constants
define('PROJECT_DIR', realpath('./'));
define('LOCALE_DIR', PROJECT_DIR .'/languages');

require_once('php-gettext/gettext.inc');

$encoding = 'UTF-8';

$locale = (isset($_REQUEST['lang']))? $_REQUEST['lang'] : $cfg->prefs->current_lang;

// gettext setup
T_setlocale(LC_MESSAGES, $locale);
// Set the text domain as 'messages'
$domain = 'messages';
T_bindtextdomain($domain, LOCALE_DIR);
T_bind_textdomain_codeset($domain, $encoding);
T_textdomain($domain);

header("Content-type: text/html; charset=$encoding");
?>
