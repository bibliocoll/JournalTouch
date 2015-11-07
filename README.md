# About
JournalTouch provides a touch-optimized, responsive interface for browsing current journal table-of-contents.

# Quickstart
## Prerequisites
1. Make sure you have Apache and PHP available. Nothing else is needed
2. Be aware that much of the fun is with showing covers. But there we don't (really) know freely available sources, so you have to get them yourself. JournalTocs offers covers via api, but only for premium accounts (as of 2015-10-28). Keep in mind, journal covers could also be a cool thin for discovery system ;)
The [Journal Covers Database](http://www.stmcovers.com/) is a free source and the domain is registered to Elsevier. Yet, the site itself makes no statement about rights or licensing. Anyway, it _might_ be a fair use thing (which in turn isn't heard of outside of the US?). Your choice... Put the covers into data/covers and use the journal's issn as name (e.g. 1234-5678.jpg).

## Quick Start
1. Create a free [JournalTocs account](http://www.journaltocs.ac.uk/index.php?action=register). While not strictly required, it is highly recommended.
2. Download JournalToc one the right side ("Download zip")
3. Extract to you apache webfolder. Make sure the data folder and its subfolders are writable by the webserver.
4. Edit config.php. For the real quick test, just enter you JournalTocs mail for $cfg->api->jt->account
5. Edit data/cover/journals.csv (text editor or e.g. Libre Office calc). In a text editor each semicolon represents a column separator. Print-ISSNs must be in column six (five semicolons before), E-ISSN go in column 6 (six semicolons before). There are no column headers, but you can refer to the config.php file and check the $cfg->csv_col variables to see what goes where. Yet, you only really need the ISSNs to get started, although we recommend adding the journal title too, e.g. if it can't be found online.
6. Open you webbrowser and go to http://yourserver.net/journaltouch/admin. Under "Update options" click "Start" and wait. You journals.csv will get updated.
7. Go to http://yourserver.net/journaltouch and be happy
8. IF you are happy, set a cronjob to call http://yourserver.net/admin/index.php?optRecent=on&upd=true daily or use http://yourserver.net/admin/index.php?optRecent=on&optCovers=on&upd=true if you want to update covers too (see config.php or release infos for 0.4 for more infos)


## Translations
If you want to change translations you can use [Poedit](https://poedit.net/) - it's free. We are going to look into a better way to translate the few language specific config option. Yet for now... :)

# Current version: 0.3
This is JournalTouch Version 0.3

We're using the git-flow project structure, so the master branch always holds the latest stable released version of this project.
Work towards the next release happens on the `develop` branch. For more information, please refer to the [Wiki] (https://github.com/bibliocoll/JournalTouch/wiki/Contributing)

# License
@copyright 2015 MPI for Research on Collective Goods, Library
(Contact: fruehauf@coll.mpg.de and krug@coll.mpg.de)

@author Alexander Krug <krug@coll.mpg.de> (Maintainer)

@author Tobias Zeumer <tzeumer@verweisungsform.de>

@author Daniel Zimmel <dnl@mailbox.org>

License: http://www.gnu.org/licenses/gpl.html GPL version 3 or higher

# Live Demo
Try here: http://www.coll.mpg.de/bib/jtdemo-public/

# Dependencies
- PHP 5.3 ([http://www.php.net])
- API key for JournalTocs ([http://www.journaltocs.ac.uk])

# Wiki
The documentation for this project lives on the [wiki] (https://github.com/bibliocoll/JournalTouch/wiki/), please continue reading there.
