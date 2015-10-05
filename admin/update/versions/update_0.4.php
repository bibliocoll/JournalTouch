<?php
/*
$this->release_note;
$this->release_filesMove;
$this->release_foldersDelete;
*/

$this->release_note =
'Version 0.4
New
- Introduced a new folder layout and options to set custom paths. Easier management of write rights and more flexibility
- Introduced an update mechanism, so a switch from 0.3 to 0.4 leaves you JT folder cluttered with old files
- Added option for default sort and made sorting menu entry multilanguage

Fixes
- timeago: now shows future dates correctly and support multilanguage
- default config.php was missing (JT didn\'t run out of the box)
- Some code refactoring';


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