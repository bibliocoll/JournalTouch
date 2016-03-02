<?php
/**
 * Default configuration settings for JournalTouch
 *
 * Don't edit this file since it
 * a) will be overwritten on updates
 * b) You create your own config via mydomain.net/admin
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

/**
 * Translate site elements
 */
$cfg->translations['main_tagline']['en_US'] = 'JournalTouch <em><strong>beta</strong></em> - a library service';
$cfg->translations['main_tagline']['de_DE'] = 'JournalTouch <em><strong>beta</strong></em> - ein Bibliothekservice';
$cfg->translations['main_float_sendArticle']['en_US'] = 'Send articles';
$cfg->translations['main_float_sendArticle']['de_DE'] = 'Artikelliste schicken';

//general menues
$cfg->translations['menu_tag']['en_US'] = 'tags';
$cfg->translations['menu_tag']['de_DE'] = 'Tags';
$cfg->translations['menu_filter']['en_US'] = 'filter';
$cfg->translations['menu_filter']['de_DE'] = 'Portfolio';
$cfg->translations['menu_filter_special']['en_US'] = 'MPI favorites';
$cfg->translations['menu_filter_special']['de_DE'] = 'MPI Favoriten';
$cfg->translations['menu_basket']['en_US'] = 'my basket';
$cfg->translations['menu_basket']['de_DE'] = 'Merkliste';

// Toggle menues
$cfg->translations['menu_sort_date']['en_US'] = 'sort date';
$cfg->translations['menu_sort_date']['de_DE'] = 'Ordne Datum';
$cfg->translations['menu_sort_az']['en_US'] = 'sort a-z';
$cfg->translations['menu_sort_az']['de_DE'] = 'Ordne A-Z';
$cfg->translations['menu_list']['en_US'] = 'list view';
$cfg->translations['menu_list']['de_DE'] = 'Liste';
$cfg->translations['menu_grid']['en_US'] = 'grid view';
$cfg->translations['menu_grid']['de_DE'] = 'Raster';

$cfg->translations['meta_toc']['en_US'] = 'TOC';
$cfg->translations['meta_toc']['de_DE'] = 'TOC';
$cfg->translations['meta_inst_service']['en_US'] = 'Library';
$cfg->translations['meta_inst_service']['de_DE'] = 'Bibliothek';
$cfg->translations['meta_journalHP']['en_US'] = 'Journal';
$cfg->translations['meta_journalHP']['de_DE'] = 'Journal';

// About and screensaver text
$cfg->translations['other_about']['en_US'] = "[p][em]JournalTouch[/em] is the [strong] University Library (TUB HH) of the Hamburg University of Technology's[/strong] alerting service for newly published journal issues.[/p][p]It's easy - select a journal and add interesting articles to your shopping basket. If there is an abstract available, it will be indicated with an extra button. When you are finished, click on your basket to check out. You can now [ul][li]send the article information to your e-mail address[/li][li]send a request for the PDF files to the library[/li][li]or view/save it as a list. Export for citation management systems like Endnote is also available.[/li][/ul][/p][p]The list of journals is a selection of the journals licensed to the library.If a journal is missing, please let us know and we will add it to the list.[br][br] [strong][em]JournalTouch[/em] is actively being developed by the library team[/strong].[br]Tables of contents are provided by [strong]CrossRef[/strong] and [strong]JournalTocs[/strong].[/p]";
$cfg->translations['other_about']['de_DE'] = "[p][em]JournalTouch[/em] is der Alerting Service der[strong] Universitätsbibliothek (TUB HH) der Technischen Universität Hamburg[/strong] für aktuelle Zeitschriftenausgaben.[/p][p]Alles ganz einfach. Wählen Sie ein Journal, wählen Sie die Artikel, die Sie interessieren und fügen Sie diese der Merkliste hinzu. Sofern ein Abstract verfügbar ist, wird Ihnen das mit einem zusätzlichen Button angezeigt. Wenn Sie fertig, klicken Sie auf die Merkliste um[ul][li]sich die Artikelinformation an Ihre E-Mail schicken zu lassen[/li][li]den Artikel bei der Bibliothek anzufordern[/li][li]oder als bibliografische Liste zu exportieren, z.B. für Endote[/li][/ul][/p][p]Die Liste ist eine Auswahl der von der Bibliothek lizensierten Journals. Lassen Sie es uns wissen, wenn eine Zeitschrift fehlt und wir fügen sie hinzu![br][br] [strong][em]JournalTouch[/em] wird aktiv von der Bibliothek entwickelt[/strong].[br]Inhaltsverzeichnise werden von [strong]CrossRef[/strong] und [strong]JournalTocs[/strong] bezogen.[/p]";


$cfg->prefs = new stdClass();
/**
 * Preferences for your library. Look & Feel of JournalTouch
 *
 * Note: inst_service might be used to link to your own discovery system or SFX
 *       which may provide additional direct links. Add an url where only the
 *       issn has to be appended. WorldCat is just an example. Set to '' to
 *       disable.
 */
// set available languages; to add a new language, update \languages accordingly
// Also set default to the same name as one of the available languages
$cfg->prefs->language_default = 'de_DE';
$cfg->prefs->languages[0]   = 'de_DE';
$cfg->prefs->languages[1]   = 'en_US';

// Institution settings
$cfg->translations['prefs_lib_name']['en_US'] = 'MPI Collective Goods Library';
$cfg->translations['prefs_lib_name']['de_DE'] = 'MPI für Gemeinschaftsgüter Bibliothek';

$cfg->prefs->show_dl_button     = false;    // Tries to create a direct download link (pdf) for a toc entry

// Institution settings - discovery and stuff
$cfg->prefs->inst_service  = 'http://www.worldcat.org/search?fq=x0%3Ajrnl&qt=advanced&dblist=638&q=n2%3A';  // See note im comment block
$cfg->prefs->proxy         = ''; // If you got a proxy (e.g. EZproxy) to allow patrons outside of you ip range access: set the base url Here (e.g. http://www.umm.uni-heidelberg.de/ezproxy/login.auth?url=)
$cfg->prefs->sfx           = ''; // If you got sfx, something like http://sfx.gbv.de/sfx_tuhh ; currently used as alternative for show_dl_button

// Menu display options
$cfg->prefs->menu_show_listview = true; // Show option to switch to list view (otherwise it's always grid view)
$cfg->prefs->menu_show_sort     = true; // Show the menu entry to switch between alphabetical and date sorting (otherwise it's the default sort below)
$cfg->prefs->menu_show_tagcloud = true; // Show the menu entry for the tagcloud?
$cfg->prefs->min_tag_freq       = 1;     // How often must a tag be used at least to show up in the tagcloud (if enabled)?

// Other display settings
$cfg->prefs->default_sort_date  = false; // If set to true, default sort is by date. Otherwise alphabetical
$cfg->prefs->show_metainfo_list = false; // Show the block with the meta infos rightside from the covers (Toc, Web, Shelfmark etc.)?
$cfg->prefs->show_metainfo_toc  = true;  // Show the block with the meta infos above the toc. Note: If show_metainfo_list is true, it will show above the toc, even if set to false here
$cfg->prefs->rss                = true;  // Show an RSS-Feed link. Be aware that this uses and displays the E-Mail you registered with at JournalTocs

$cfg->prefs->show_orbit       = false;  // Do you want to see the slide of the newest issues?
$cfg->prefs->screensaver_secs = 240;    // Idle time in seconds before screensaver is displayed. Set to zero to disable

// Caching: only activate it if you do
// a) a (daily) cron to http://my.journaltouch.local/admin/index.php?optRecent=on&upd=true
// b) if you got a premium Jtoc account: a cron too for: http://my.journaltouch.local/admin/services/getLatestJournals.php
$cfg->prefs->cache_toc_enable  = true;      // Caches fetched tocs so they only are processed once there is a new issue
$cfg->prefs->cache_main_enable = false;     // Basically JT only serves a static page, so it makes sense not to generate it all the time. Anyway, best is, you only activate this, if run your cron often



$cfg->api = new stdClass();
$cfg->api->all = new stdClass();
$cfg->api->jt = new stdClass();
/**
 * API: Settings to fetch the table of contents
 *
 * Currently most settings are for JournalToc. The comments states if the
 * setting requries are JournalToc (JT) premium account.
 *
 * Note: You can get quite a lot without a JT premium account running
 * services/getJournalInfos.php (preferably on a daily basis). But you still need a
 * standard account.
 */
$cfg->api->all->articleLink = true; // Should articles in fetched toc's be clickable links? Set false if not.
$cfg->api->all->is_new_days = 30;   // On update: for how many days mark an issue as new after publishing date?

$cfg->api->jt->account  = '';       // The mail you are registered with at JournalToc
$cfg->api->jt->premium  = false;    // Premium: Set to true if you got a premium account
$cfg->api->jt->upd_show = false;    // Premium: Uses infos from outfile. Slows page loading down
$cfg->api->jt->updates  = 'http://www.journaltocs.ac.uk/api/journals/latest_updates?user='; // Premium: Update URL



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
$cfg->mail->subjectFB->en_US      = 'Feedback from JournalTouch'; // Feedback button caption

// Sending article list to user
$cfg->mail->fromAddress    = ''; // Your default address (service@my-library.net)
$cfg->translations['mail_fromName']['en_US'] = 'MPI JournalTouch';
$cfg->translations['mail_fromName']['de_DE'] = 'MPI JournalTouch';
$cfg->translations['mail_subjectToUser']['en_US'] = 'Your saved articles from JournalTouch';
$cfg->translations['mail_subjectToUser']['de_DE'] = 'Ihre gespeicherten Artikel von Journaltouch';
$cfg->translations['mail_bodyMessage']['en_US'] = 'You sent the following message';
$cfg->translations['mail_bodyMessage']['de_DE'] = 'Ihre Nachricht an uns war';
$cfg->translations['mail_bodySalutation']['en_US'] = 'Here are your articles, enjoy!';
$cfg->translations['mail_bodySalutation']['de_DE'] = 'Ihre Artikel, viel Freude!';
$cfg->translations['mail_bodyClosing']['en_US'] = 'Best regards, your library team!';
$cfg->translations['mail_bodyClosing']['de_DE'] = 'Viele Grüße Ihr Bibliotheksteam!';

// Sending order from user to library
$cfg->mail->toAddress      = ''; // Your contact address (journaltouch@my-library.net)
$cfg->translations['mail_subjectToLib']['en_US'] = 'New order for the library from JournalTouch';
$cfg->translations['mail_subjectToLib']['de_DE'] = 'Neue JournalTouch-Bestellung von Nutzer';
$cfg->translations['mail_bodyOrder']['en_US'] = 'New order from JournalTouch';
$cfg->translations['mail_bodyOrder']['de_DE'] = 'Neue JournalTouch-Bestellung';



$cfg->filters = array();
/**
 * In the column defined by $cfg->csv_col->filter you write a shorthand code for
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
$cfg->filters['psy']['en_US'] = 'Psychology';
$cfg->filters['psy']['de_DE'] = 'Psychologie';
$cfg->filters['pol']['en_US'] = 'Politics';
$cfg->filters['pol']['de_DE'] = 'Politik';
$cfg->filters['wir']['en_US'] = 'Yet another filter';
$cfg->filters['wir']['de_DE'] = 'Weiterer Filter';



$cfg->covers = new stdClass();
/**
 * Put covers into the img folder. The name must be the issn set the column
 * specified for $cfg->csv_col->issn. The extension might be jpg, gif or png.
 *
 * There are rumours that cover api's for journals exist, but no free service
 * is known. So, it's up to you how you get the covers (ask vendor, journal
 * publisher...). While not to be found on JournalTocs official website, the
 * premium service seems to offer cover links via api (which in turn points to the
 * publishers sites) - info as of 2015-10-30.
 *
 * As of version 0.4.0 JournalTouch provides a way to download covers. This
 * divided into two sections: 1) Generic sources and 2) Publisher's websites.
 * By default everything is deactivated. Set 1 in src_genric and src_publisher
 * for whatever service you want to query for covers. Be aware that you should
 * know perfectly well if you are legible to do so (e.g. by some kind of fair
 * use law or special agreements with the specified service/publisher).
 * Using STMcovers is most likely safe since the domain is registered to Elesevier
 * and it offers cover downloads explicitly, yet again - it's your responsibility.
 *
 * As for the logic:
 * 1. If a cover in data/covers exists none will ever be downloaded (e.g. useful if
 *    covers never change anyway). This one will always be displayed.
 * 2. If a cover exists in data/covers/api it will be displayed
 * 3. If no cover exists in either folder, it will be downloaded using the
 *    activated services.
 * 3.1 above publisher is preferred to a generic source (you might want to
 *     disable Elsevier since STMcovers provides real high quality ;))
 * 3.2 If no publisher is provided (auto via updater) generic sources are checked
 * 4. If a cover exists in data/covers/api, but a new issue is found by the
 *    updater, it will be redownloaded.
 *
 * Note: If yout set anything for api no images from the folder will be loaded
 */
$cfg->covers->placeholder = 'img/placeholder.gif';  // Just a placeholder
$cfg->covers->api         = '';                     // You might input an url where an issn can be appended ("http://myservice.net/issn=") - and mail us if you got such a thing ;)
// These are sites where covers for many publishers can be fetched
$cfg->covers->src_genric  = array('STMcovers'           => 0,
                                'JournalTocs'           => 0,
                                'Lehmanns'              => 0,
                                'SubscribeToJournals'   => 0);
// For these publishers JournalTouch provides a way for direct cover download
$cfg->covers->src_publisher = array('DeGruyter' => 0,
                                    'Elsevier'  => 0,
                                    'Sage'      => 0,
                                    'Springer'  => 0);



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
 * System and path settings
 *
 * By default JT saves everything in the data folder and its subsolders (these
 * folders must be writable).
 *
 * You might have a very special use case, where you want to save stuff outside
 * of JT's root folder. One idea: you want to use multiple JT instances and share
 * some covers between them. Here you can point to any folder you like for
 * cache, covers, export and journals. (Well, essentially this is added to make
 * an existing special case world usable ;))
 *
 * If you change anthying here you _must_ use absolute paths. Leave empty to use
 * default paths.
 */
$cfg->sys->data_cache_usr       = ''; // If empty it points to data/cache
$cfg->sys->data_covers_usr      = ''; // If empty it points to data/cover
$cfg->sys->data_export_usr      = ''; // If empty it points to data/export
$cfg->sys->data_journals_usr    = ''; // If empty it points to data/journals



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
$cfg->csv_col->p_issn        = 5;   // Required. Without issn's this'd be pointless :)
$cfg->csv_col->e_issn        = 6;   // Optional. An e-issn to try if issn fails to get toc.
$cfg->csv_col->publisher     = 7;   // Optional/Auto. Publisher. Used for cover download (currently: "De Gruyter", "Elsevier", "Sage" and "Springer-Verlag" will work)
$cfg->csv_col->new           = 8;   // Optional/Auto. Marks journal (issue) as new if not empty
$cfg->csv_col->date          = 9;   // Optional/Auto. Date of last issue
$cfg->csv_col->lastIssue     = 10;  // Optional/Auto. Format "year/vol/issue". Used for Crossref check
$cfg->csv_col->metaPrint     = 11;  // Optional. Are you subscribed for the print edition?
$cfg->csv_col->metaOnline    = 12;  // Optional. Are you subscribed for the online edition?
$cfg->csv_col->metaGotToc    = 13;  // Optional/Auto. You might want to show journals you are subscribed to, even though no toc can be fetched. Leave empty to spare user the check.
$cfg->csv_col->metaShelfmark = 14;  // Optional. Where is your print edition located?
$cfg->csv_col->metaWebsite   = 15;  // Optional/Auto. The journal's website.
$cfg->csv_col->tags          = 16;  // Optional/Auto. Got some subject indexing? Separate tags with commas :)


/**
 * Variables that are not set in the user config - only used for vanilla installation
 */
$cfg->sys->newInstallation = true;  // If this is set, show a hint to user that he should create his config
?>