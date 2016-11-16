# About
JournalTouch provides a touch-optimized, responsive interface for browsing current journal table-of-contents.

See latest changelog version 0.4.4: [JournalTouch Version 0.4](doc/github/version_0.4.x.md).

# Installation
## Quickstart Guide for a Test Setup
1. Download and install [PHP 5.5 or newer](https://secure.php.net/downloads.php), if you're on linux, use your package manager, ie: `sudo apt-get install php` on Debian/Ubuntu
2. Download JournalTouch, either by clicking "Download ZIP" (upper right on this page) and extracting it to a folder of your choice, or by using `git clone` (might make updating easier)
3. Open a console, change your working directory to where you put JournalTouch, and attempt to start the PHP webserver with `php -S localhost:8000`. If that fails with an unknown command error, PHP is not installed properly (most likely, your PATH environment variable needs tweaking). If it fails with another type of error, maybe the port is already used on your machine. Try `php -S localhost:8080`, any number above 1024 should work.
4. Point your browser to [http://localhost:8000/](http://localhost:8000). You should see a running basic installation with a grid view of some example journals
5. Now browse to [http://localhost:8000/admin](http://localhost:8000/admin/index.php) and read through the available options (on a non-test installation, you also should make sure this folder is secured, e.g. by using a htaccess).
6. Edit data/journals/journals.csv (text editor or e.g. Libre Office calc). In a text editor each semicolon represents a column separator. Print-ISSNs must be in column six (five semicolons before), E-ISSN go in column seven (six semicolons before). There are no column headers, but you can refer to the config.php file and check the $cfg->csv_col variables to see what goes where. Yet, you only really need the ISSNs to get started, although we recommend adding the journal title too, e.g. if it can't be found online.
7. Go again to [http://localhost:8000/admin](http://localhost:8000/admin/index.php). Under "Update options" click "Start" and wait (this will seem like the server hangs and can take minutes, be patient). You journals.csv will get updated.
8. Go to [http://localhost:8000/](http://localhost:8000) and be happy

## Setting up a proper installation
### Prerequisites
1. HTTP Server (we assume `Apache 2`) with `PHP 5.5` or newer (for `mbstring`) and access to the Internet (the server will have to contact journaltocs.com, crossref.org and various publisher sites for cover downloads)
2. For showing covers: Don't put JournalTouch on the open internet. If you do, talk to your lawyer. JournalTouch can check publisher sites for journal cover images to copy/download and display. We're pretty confident that it is legal to do so in Europe (if the public you make the cover available to is part of the public that can access the cover picture on the publisher site) and in the US (fair use), but please be aware that this opinion is no legal counsel and some publishers claim their journal covers to be content (and not advertisement for the content within the journal) and might take offense at you copying and displaying their content...

### Installation
1. Properly set up your webserver. We'll wait here while you work :D
2. Copy your JournalTouch test installation somewhere where the webserver can reach it and make the server actually, well, serve it.
3. Make sure file permissions are in order (ie: everything in the JournalTouch folder is owned by www-data:www-data on a Debian/Ubuntu machine)
4. Make sure the webserver has write permission on the `JournalTouch/data/` folder and its subfolders
5. Decide on a way to prevent access to `JournalTouch/admin/`. Depending on your setup, this can be done with an `.htaccess` file (there is an example file at `JournalTouch/admin/.htaccess.sample`). Try to not lock yourself out as well ;)
9. If you are happy, set a cronjob to call `http://<myinstallation.net>/admin/updateTocs.php?optRecent=on&upd=true` daily or use `http://<myinstallation.net>/admin/updateTocs.php?optRecent=on&optCovers=on&upd=true` if you want to update covers too (Settings menu or release infos for 0.4 for more infos).
Example Cronjob: `wget -O - -q -t 1 -T 10000 "http://<myinstallation.net>/admin/updateTocs.php?optRecent=on&optCovers=on&upd=true" >/dev/null 2>&1`

## Translations
- If you want to change translations you can use [Poedit](https://poedit.net/) - it's free.
- As of version 0.4 many thing can be translated in the Settings menu in the admin panel :)

# Current version: This is JournalTouch Version 0.4.4

We're using the git-flow project structure, so the master branch always holds the latest stable released version of this project.
Work towards the next release happens on the `develop` branch. For more information, please refer to the [Wiki] (https://github.com/bibliocoll/JournalTouch/wiki/Contributing)

# License
@copyright 2015-2016 MPI for Research on Collective Goods, Library
(Contact: fruehauf@coll.mpg.de and krug@coll.mpg.de)

@author Alexander Krug <krug@coll.mpg.de> (Maintainer)

@author Tobias Zeumer <tzeumer@verweisungsform.de>

@author Daniel Zimmel <dnl@mailbox.org>

License: http://www.gnu.org/licenses/gpl.html GPL version 3 or higher

# Live Demo
Try here: http://www.coll.mpg.de/bib/jtdemo-public/

# Dependencies
- PHP 5.5 or newer ([http://www.php.net]), please use the most up-to-date version you have available
- API key for JournalTocs ([http://www.journaltocs.ac.uk])

# Wiki
The documentation for this project lives on the [wiki] (https://github.com/bibliocoll/JournalTouch/wiki/), please continue reading there.
