<?php
/*
$this->release_note;
$this->release_filesMove;
$this->release_foldersDelete;
*/

$this->release_note =
'Version 0.5
New: xxxMajorFeaturexxx
xxx


New
- Introduced xxx
- Added option xxx


Changes
- 


Improvements
- xxx


Fixes
- xxx


Known Issues
- The cover update does not work if you use the JournalTocs Premium update. JournalTocs now provides covers via api. Since I got no premium account, I can\'t modify admin/services/getLatestJournalTocPremium.php accordingly.
- The config-default.php is only loaded unless no user_config.php exists (means: only for a fresh install). Thus adding new variables to config-default.php and settings.php for a new version will raise an PHP notice "Undefined property" unless the configuration is saved again. Also settings.php won\'t get the sample values from config-default.php. For now make sure to re-save you config if you apply a new version of JournalTouch (and set something for the new options).
- Sometimes the log is not outputted to the Journal Update page (likely because browser times out); you can manually check data/journals/LastUpdateLog.html or open it from the admin menu


Added 3rd party ressources
- xxx: URL


Credits
- xxx
';


/**
 * Delete these folder on update
*/
$this->release_foldersDelete = array (
//    'xxx1',
//    'xxx2'
);


/**
 * @Note    Files that must be moved. Don't use trailing slashes.
 *          Use * (many chars) or ? (one character) as wildcards for source ('from').
 *          If you use wildcards for the source, set a * at the end of the target ('to')
 *
 *          If you want to copy a single file, spell it out in 'from' and 'to'
*/
//$this->release_filesMove['from'][]  = 'xxx.php';
//$this->release_filesMove['to'][]    = 'xxx/xxx.php';


?>