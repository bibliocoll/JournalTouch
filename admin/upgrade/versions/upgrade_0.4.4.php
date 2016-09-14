<?php
/*
$this->release_note;
$this->release_filesMove;
$this->release_foldersDelete;
*/

$this->release_note =
'Version 0.4.4
New

Changes
- config-default.php was moved from data/config/ to sys/, which means theres is one less non-user file in data/

Improvements

Fixes
- article titles or abstracts from journaltocs or crossref with quotes in them should no longer break our html (technically, this was a dangling markup injection vulnerability, but the attacker would have to control jtocs/cr to exploit it)
- this version has a correct version number, which should calm the updater.
- links to external sites now properly open in an iframe-popover when opened from the "view/print" page.
- opening the cart via the "send articles" button in the lower right no longer leads to a cart with missing buttons
- some more spelling fixes

Known Issues
- The cover update does not work if you use the JournalTocs Premium update. JournalTocs now provides covers via api. Since I got no premium account, I can\'t modify admin/services/getLatestJournalTocPremium.php accordingly.
- Sometimes the log is not outputted to the Journal Update page (likely because browser times out); you can manually check data/journals/LastUpdateLog.html or open it from the admin menu
- JournalTOCs has faulty data for some journals, with all information stored in the "abstract" field. if you find a journal where they have this problem, tell them.


Added 3rd party ressources

Credits
 @realsobek, @MPIKGLibrary and @reicheltmediadesign for their bug reports and comments
';


/**
 * @Note    Files that must be moved. Don't use trailing slashes.
 *          Use * (many chars) or ? (one character) as wildcards for source ('from').
 *          If you use wildcards for the source, set a * at the end of the target ('to')
 *
 *          If you want to copy a single file, spell it out in 'from' and 'to'
*/
$this->release_filesMove['from'][]  = 'data/config/config-default.php';
$this->release_filesMove['to'][]    = 'sys/config-default.php';


/**
 * Delete these folder on update
 */
//$this->release_foldersDelete = array (
//    'admin/upgrade/history'
//);


?>
