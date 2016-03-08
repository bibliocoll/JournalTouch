#Version 0.4 (unreleased)
##Major new features
###New: Shiny admin menu
The config.php (and config-default.php) became quite hard to understand. So now you can configure JournalTouch via web menu. Just go to http://mysite.net/admin. You also can now easily translate most (not all) of the settings. Includes the about and screensaver text.
The drawback is: you old config.php won't be converted (in fact no such file is used anymore). But it should hardly take more than a few minutes to enter you old infos via the config website. For reference you'll find you old config here: data/config/config.php

###New: Cover download
As of this version JournalTouch provides a way to download covers. This is divided into two source categories:
1. Generic sources and
2. Publisher's websites.
By default everything is deactivated in the config. So go there and enable each service you want to query for covers.
But be aware that you should know perfectly well if you are legible to do so (e.g. by some kind of fair use law or special agreements with the specified service/publisher).
Using STMcovers is most likely safe since the domain is registered to Elesevier and it offers cover downloads explicitly, yet again - it's completely your responsibility.

As for the logic:
1.  If a cover in data/covers exists none will ever be downloaded (e.g. useful if
    covers never change anyway). This one will always be displayed.
2.  Second choice is: a cover exists in data/covers/api
3.  If no cover exists in either folder, it will be downloaded trying the activated services.
  1. Publishers are preferred to a generic source (you might want to disable Elsevier since STMcovers provides real high quality ;))
  2. If no publisher is provided (the updater does this automatically) or a download fails generic sources are checked
4.  If a cover exists in data/covers/api, but a new issue is found by the updater, it will be redownloaded.

As for image size:
Some of the downloaded images might be very large. A feature like automatic resizing is under consideration. For now I highly recommend to resize all covers larger than 12 KB. For windows IrfanView works very nice using jpg as target format and setting the "Riot"-Plugin to target size 12 KB. As size set 170x254 (or just one side).
While it doesn't really matter on a LAN, mobile users might thank you.


###New: Cover management
To easily set default and api covers, the admin menu now also features a simple cover management. Be aware that currently only 100 journals at a time are shown.


##New
- Introduced a new folder layout and options to set custom paths. Easier management of write rights and more flexibility
- Introduced an update mechanism, so a switch from 0.3 to 0.4 leaves you JT folder cluttered with old files
- Added option for default sort and made sorting menu entry multilanguage
- Added option to hide the list view
- Added option to use proxy (e.g. EZproxy) for article links
- Added option to display metainfo (and the journal title) above the toc, not only in list. The browsing happens in the iframe. An back btton show dynamically next to the buttons. Activate via config.
- Added option to show metainfo rss button (use with care and read the help in the config page)
- Added button to show qr code for article link; doi if available otherwise fetched link (displays always, currently no option)
- Added warning if admin folder is not secured by .htaccess
- Added option to hide some elements for given IPs or browser user agents. For example useful to hide printing option on kiosk pc's.


##Changes
- config option "$cfg->csv_col->col7 = 7" became "$cfg->csv_col->publisher = 7" - publisher is used for cover Download. The publisher is now automatically added on the (first) metadata update (at least if found at JournalTocs or JournalSeek).


##Improvements
- Use JournalTocs title if no title is set in journals.csv on metadata fetching. Thus only the ISSNs are required to get started (although using the "library's journal name" is recommended). You might try it by renaming journals.csv.example to journals.csv in the data/journals folder
- Filter menu now uses three columns (menu doesn't overflow screen bottom so easily)
- Article Tocs now use full width in frame
- Removed curl requirement
- Screensaver activation time is configurable. It also can be an animation now.
- Cart rest time is configurable
- Tags are presented a little bit better


##Fixes
- timeago: now shows future dates correctly and support multilanguage
- Some code refactoring
- The journaltoc-suggest.csv file created on metadata update included all journals, not only the ones not listed at JournalTocs
- GetText files started outputting empty lines for header. Removed redundant lines
- List view toggle wasn't multilanguage
- Internet Explorer sometimes didn't hide toc loading animation


##Known Issues
- The cover update does not work if you use the JournalTocs Premium update. JournalTocs now provides covers via api. Since I got no premium account, I can't modify admin/services/getLatestJournalTocPremium.php accordingly.
- The config-default.php is only loaded unless no user_config.php exists (means: only for a fresh install). Thus adding new variables to config-default.php and settings.php for a new version will raise an PHP notice "Undefined property" unless the configuration is saved again. Also settings.php won't get the sample values from config-default.php. For now make sure to re-save you config if you apply a new version of JournalTouch (and set something for the new options).
- Sometimes the log is not outputted to the Journal Update page (likely because browser times out); you can manually check data/journals/LastUpdateLog.html or open it from the admin menu



##Other
###Added 3rd party ressources
- Jquery UI: https://jqueryui.com/
- foundation-select: https://github.com/roymckenzie/foundation-select
- jquery.AreYouSure: https://github.com/codedance/jquery.AreYouSure
- JQuerySerializeCheckbox.js: https://gist.github.com/TaoK/1572512
- jquery-qrcode: https://github.com/lrsjng/jquery-qrcode (wrapper for qrcode-generator: https://github.com/kazuhikoarase/qrcode-generator)


###Credits
Last but not least. Thanks for suggestions and help from:
- Andreas Bohne-Lang (Medizinische Fakultät Mannheim der Ruprecht-Karls-Universität Heidelberg) - EZproxy, Cover download, RSS
- Oliver Löwe (UB TU Bergakademie Freiberg) - incent to at least provide some quick start info, showing JournalTouch on "the Console" :)
';