<?php
/*
$this->release_note;
$this->release_filesMove;
$this->release_foldersDelete;
*/

$this->release_note =
'Version 0.4
New: Shiny admin menu
The config.php (and config-default.php) became quite hard to understand. So now you can configure JournalTouch via web menu. Just go to http://mysite.net/admin. You also can now easily translate most of the settings.
The drawback is: you old config.php won\'t be converted (in fact no such file is used anymore). But it should hardly take more than a few minutes to enter you old infos via the config website. For reference you\'ll find you old config here: data/config/config.php


New: Cover download
As of this version JournalTouch provides a way to download covers. This is divided into two source categories:
 1) Generic sources and
 2) Publisher\'s websites.
By default everything is deactivated in the config. So go there and enable each service you want to query for covers.
But be aware that you should know perfectly well if you are legible to do so (e.g. by some kind of fair use law or special agreements with the specified service/publisher).
Using STMcovers is most likely safe since the domain is registered to Elesevier and it offers cover downloads explicitly, yet again - it\'s completely your responsibility.

As for the logic:
1.  If a cover in data/covers exists none will ever be downloaded (e.g. useful if
    covers never change anyway). This one will always be displayed.
2.  Second choice is: a cover exists in data/covers/api
3.  If no cover exists in either folder, it will be downloaded trying the activated services.
3.1 Publishers are preferred to a generic source (you might want to disable Elsevier since STMcovers provides real high quality ;))
3.2 If no publisher is provided (the updater does this automatically) or a download fails generic sources are checked
4.  If a cover exists in data/covers/api, but a new issue is found by the updater, it will be redownloaded.

As for image size:
Some of the downloaded images might be very large. A feature like automatic resizing is under consideration. For now I highly recommend to resize all covers larger than 12 KB. For windows IrfanView works very nice using jpg as target format and setting the "Riot"-Plugin to target size 12 KB. As size set 170x254 (or just one side).
While it doesn\'t really matter on a LAN, mobile users might thank you.


New
- Introduced a new folder layout and options to set custom paths. Easier management of write rights and more flexibility
- Introduced an update mechanism, so a switch from 0.3 to 0.4 leaves you JT folder cluttered with old files
- Added option for default sort and made sorting menu entry multilanguage
- Added option to hide the list view
- Added option to use proxy (e.g. EZproxy) for article links
- Added option to display metainfo (and the journal title) above the toc, not only in list. Activate via config.


Changes
- config option "$cfg->csv_col->col7 = 7" became "$cfg->csv_col->publisher = 7" - publisher is used for cover Download. The publisher is now automatically added on the (first) metadata update (at least if found at JournalTocs or JournalSeek).


Improvements
- Use JournalTocs title if no title is set in journals.csv on metadata fetching. Thus only the ISSNs are required to get started (although using the "library\'s journal name" is recommended). You might try it by renaming journals.csv.example to journals.csv in the data/journals folder


Fixes
- timeago: now shows future dates correctly and support multilanguage
- default config.php was missing (JT didn\'t run out of the box)
- Some code refactoring
- The journaltoc-suggest.csv file created on metadata update included all journals, not only the ones not listed at JournalTocs
- GetText files started outputting empty lines for header. Removed redundant lines
- List view toggle wasn\'t multilanguage

Known Issues
- The cover update does not work if you use the JournalTocs Premium update. JournalTocs now provides covers via api. Since I got no premium account, I can\'t modify admin/services/getLatestJournalTocPremium.php accordingly.


Credits
Last but not least. Thanks for suggestions and help from:
- Andreas Bohne-Lang (Medizinische Fakultät Mannheim) - EZproxy, Cover download, RSS (not yet ;))
- Oliver Löwe (UB TU Bergakademie Freiberg) - incent to at least provide some quick start info, showing JournalTouch on "the Console" :)
';


/**
 * @Note
 * Automatically created on release extraction
 *  - data/cache
 *  - data/covers (issn.jpg/png etc; was just img)
 *  - data/export
 *  - data/journals (was input)
 *  - data/journals/backup
 *  - css/foundation-icons
 *  - languages (was locale)
*/
$this->release_foldersDelete = array (
    'cache',
    'export',
    'foundation-icons',
    'input',
    'locale'
);


/**
 * @Note    Files that must be moved. Don't use trailing slashes.
 *          Use * (many chars) or ? (one character) as wildcards for source ('from').
 *          If you use wildcards for the source, set a * at the end of the target ('to')
 *
 *          If you want to copy a single file, spell it out in 'from' and 'to'
*/
$this->release_filesMove['from'][]  = 'config.php';
$this->release_filesMove['to'][]    = 'data/config/config.php';

$this->release_filesMove['from'][]  = 'cache/*.*';
$this->release_filesMove['to'][]    = 'data/cache/*';

$this->release_filesMove['from'][]  = 'export/*.*';
$this->release_filesMove['to'][]    = 'data/export/*';

$this->release_filesMove['from'][]  = 'input/*.*';
$this->release_filesMove['to'][]    = 'data/journals/*';

$this->release_filesMove['from'][]  = 'input/backup/*.*';
$this->release_filesMove['to'][]    = 'data/journals/backup/*';

$this->release_filesMove['from'][]  = 'img/????-????.*';
$this->release_filesMove['to'][]    = 'img/covers/*';


?>