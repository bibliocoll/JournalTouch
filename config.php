<?php
require_once 'sys/jt-gettext.php';
/**
 * Configuration settings for JournalTouch
 *
 * Make sure to chmod this file to 644. See additional infos for each config
 * "block".
 *
 * @note  Using objects to set options may be unusual, but has advantages to
 *        using arrays resp. an ini file. In regards to an ini file you can
 *        make strings translatable. In regards to arrays, especially with
 *        named keys, objects are easier to read in code (remember all those
 *        'Bla '.$ary['important'].' and so on' ;)
 *        Btw.: nice way to convert multi dimension arrays:
 *          $obj = json_decode(json_encode($array));
 *
 * @todo  Maybe create a class JournalTouch with static properties?
 */
$cfg = new stdClass();


$cfg->prefs = new stdClass();
/**
 * Preferences for your library. Look & Feel of JournalTouch
 *
 * Note: inst_service might be used to link to your own discovery system or SFX
 *       whichmay provide addiotional direct links. Add an url where only the
 *       issn has to be appended. WorldCat is just an example. Set to '' to
 *       disable.
 */
$cfg->prefs->show_tagcloud = true;      // Show the menu entry for the tagcloud?
$cfg->prefs->show_metainfo = true;      // Show the block with the meta infos rightside from the covers (Toc, Web, Shelfmark etc.)?

$cfg->prefs->lib_name      = 'MPI Collective Goods Library, Bonn';
$cfg->prefs->lib_initials  = 'MPI';
$cfg->prefs->ip_subnet     = '134.28.'; // Which IPs can access subscribed content? Use only masked IP (without subnet) NOT YET used
$cfg->prefs->inst_service  = 'http://www.worldcat.org/search?fq=x0%3Ajrnl&qt=advanced&dblist=638&q=n2%3A';  // See note im comment block



$cfg->api = new stdClass();
$cfg->api->jt = new stdClass();
/**
 * API: Settings to fetch the table of contents
 *
 * Currently most settings are for JournalToc. The comments states if the
 * setting requries are JournalToc (JT) premium account.
 *
 * Note: You can get quite a lot without a JT premium account running
 * services/getJournalInfos.php (preferrably on a daily basis). But you still need a
 * standard account.
 */
$cfg->api->articleLink = true;      // Should articles in fetched toc's be clickable links? Set false if not.

$cfg->api->jt->account  = '';       // The mail you are registered with at JournalToc
$cfg->api->jt->premium  = false;    // Premium: Set to true if you got a premium account
$cfg->api->jt->upd_show = false;    // Premium: Uses infos from outfile. Slows page loading down
$cfg->api->jt->updates  = 'http://www.journaltocs.ac.uk/api/journals/latest_updates?user='; // Premium: Update URL
$cfg->api->jt->outfile  = 'input/latest-issns.json.txt';   // Premium: The file the updates are saved to temporarily. You'll have to run services/getLatestJournals.php regularily



$cfg->mail = new stdClass();
/**
 * Mailer settings
 *
 * Note: Setting the domain is essential! Only users with institutional mails
 * can receive mails. (They only have have to input everything before
 * @my-library.net)
 *
 * Note: the  __() isn't required, but makes it translatable.
 *
 * @todo  This domain thing seriously should be changed to optional.
 */
$cfg->mail->domain         = ''; // Your mailer domain (my-library.net)
$cfg->mail->subjectFB      = __('Feedback from JournalTouch'); // Feedback button caption

// Sending article list to user
$cfg->mail->fromAddress    = ''; // Your default address (service@my-library.net)
$cfg->mail->fromName       = __('Your Library JournalTouch');
$cfg->mail->subjectToUser  = __('Your saved articles from JournalTouch');
$cfg->mail->bodyMessage    = __('You sent the following message');
$cfg->mail->bodySalutation = __('Here are your articles, enjoy!');
$cfg->mail->bodyClosing    = __('Best regards, your library team!');

// Sending order from user to library
$cfg->mail->toAddress      = ''; // Your contact address (journaltouch@my-library.net)
$cfg->mail->subjectToLib   = __('New order for the library from JournalTouch');
$cfg->mail->bodyOrder      = __('New order from JournalTouch');



$cfg->csv_file = new stdClass();
/**
 * Which file with your journals information and what separator is used.
 * Usually you won't have to change anything here.
 */
$cfg->csv_file->separator  = ';';
$cfg->csv_file->path       = 'input/journals.csv';



$cfg->csv_col = new stdClass();
/**
 * Which column (separated by the separator specified above) holds which data?
 * Usuallly you most likely will just create an Excel file with those colums and
 * export it as csv to the path specified above.
 * See the comments at the end of the line, if the column can be empty (=optional).
 * "Auto" means, there is a mechanism to automatically get that info.
 *
 * @todo  new and date are logically redundant?
 */
$cfg->csv_col->title         = 0;   // Required. The title of the journal
$cfg->csv_col->filter        = 1;   // Optional. Show value in menu to filter by "category". Also see next configuration block.
$cfg->csv_col->col2          = 2;   // Optional. Don't know, just add empty column
$cfg->csv_col->important     = 3;   // Optional. Shows up as "special" filter in filter menu, if col isn't empty (anything is ok)
$cfg->csv_col->col4          = 4;   // Optional. Don't know, just add empty column
$cfg->csv_col->issn          = 5;   // Required. Without issn's this'd be pointless :)
$cfg->csv_col->issn_alt      = 6;   // Optional. An e-issn to try if issn fails to get toc.
$cfg->csv_col->col7          = 7;   // Optional. Don't know, just add empty column
$cfg->csv_col->new           = 8;   // Optional/Auto. Marks journal (issue) as new if not empty
$cfg->csv_col->date          = 9;   // Optional/Auto. Date of last issue
$cfg->csv_col->lastIssue     = 10;  // Optional/Auto. Format "year/vol/issue". Used for Crossref check
$cfg->csv_col->metaPrint     = 11;  // Optional. Are you subscribed for the print edition?
$cfg->csv_col->metaOnline    = 12;  // Optional. Are you subscribed for the online edition?
$cfg->csv_col->metaGotToc    = 13;  // Optional/Auto. You might want to show journals you are subscribed to, even though no toc can be fetched. Leave empty to spare user the check.
$cfg->csv_col->metaShelfmark = 14;  // Optional. Where is your print edition located?
$cfg->csv_col->metaWebsite   = 15;  // Optional/Auto. The journal's website.
$cfg->csv_col->tags          = 16;  // Optional/Auto. Got some subject indexing? Separate tags with commas :)



$cfg->filter = array();
/**
 * In the colum defined by $cfg->csv_col->filter you write a shorthand code for
 * the filter. Here you map that shorthand code to some human readable name.
 *
 * Just to clarify why the value of that column is not taken directly: The
 * advantage of this mapping is that you might have set it for some hundred
 * journals and have quite a lot of categories but only want some (important
 * ones) to show up in the filter menu. This is the way.
 *
 * Add as much as you want.
 * Format: $cfg->filter->entry['YOUR_SHORTHAND'] = __('YOUR DESCRIPTION');
 *
 * Note: the  __() isn't required, but makes it translatable.
 */
$cfg->filter['psy'] = __('Psychology');
$cfg->filter['pol'] = __('Politics');
$cfg->filter['wir'] = __('Yet another filter');



$cfg->covers = new stdClass();
/**
 * Put covers into the img folder. The name must be the issn set the column
 * specified for $cfg->csv_col->issn. The extention might be jpg, gif or png.
 *
 * There are rumours that cover api's for journals exist, but no free service
 * is known. So, it's up to you how you get the covers (ask vendor, journal
 * publisher...). There might be ways to fetch covers from the big ones
 * (Springer, Wiley, Sage, IEEE, ACM), but legal terms would have to be
 * clarified. So, for now there's little to set here.
 *
 * Note: If yout set anything for api no images from the folder will be loaded
 */
$cfg->covers->placeholder = 'img/placeholder.gif';  // Just a placeholder
$cfg->covers->api         = '';                     // You might input an url where an issn can be appended ("http://myservice.net/issn=") - and mail us if you got such a thing ;)



$cfg->dbusers = new stdClass();
/**
 * Preset list of users (for mailing)
 *
 * Note: This is very specific. First you have to rewritesys/class.GetUsers.php.
 *       Second this is most likely only appropriate if you have very few
 *       patrons and make JournalTouch only available in your institutions
 *       network.
 *
 * @todo  Just *maybe* it'd be interesting to do a LDAP lookup so authorized
 *        users could receive epapers per mail. But pretty *sure* this'd be a
 *        too complicated legal matter...
 */
$cfg->dbusers->userlist = false;
$cfg->dbusers->dbuser   = '';
$cfg->dbusers->dbpass   = '';


$cfg->sys = new stdClass();
/**
 * Configuration settings for internal use; don't change
 */
$cfg->sys->abspath  = dirname(__FILE__).'/';    // absolute path to JournalTouch directory
//echo "<pre>";
//var_dump($cfg);

?>
