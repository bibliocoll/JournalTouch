<?php
/**
 * Use functions for overview. Use separate file to prevent redeclarations
 *
 * Time-stamp: "2015-08-30 2:25:00 zeumer"
 *
 * @author Tobias Zeumer <tobias.zeumer@tuhh.de>
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */


/**
  * @brief   Check if a journal touch update is required

/**
  * @brief   Check if a journal touch upgrade is required
  *
  * Updating was introduced with version 0.4
  *
  * @todo Instead of just using empty files, the files could be the release notes
  *
  * @return \b bool True if upgrade is needed, false otherwise
  */
function check_upgrade_required($cfg) {
    // The very first time, create an info, what is our initial JT version
    // (the "fresh" installation). It's easy - it's the first info in history
    $upd_dir    = $cfg->sys->basepath.'admin/upgrade/';

    $historyDir     = glob($upd_dir.'history/ver_*');
    $historyCount   = count($historyDir);

    // Write our initial version
    if ($historyCount === 0) {
        // Special case, coming from 0.3, which had no upgrade mechanism
        // Check for something that only existed in 0.3
        if (file_exists($cfg->sys->basepath.'locale/de_DE.gif')) {
            file_put_contents($upd_dir.'history/ver_0.3', '');
        }
        else {
            file_put_contents($upd_dir.'history/ver_'.$cfg->sys->current_jt_version, '');
        }
    }

    // Now check if the current version differs from our last upgraded version
    if (!file_exists($cfg->sys->basepath.'admin/upgrade/history/ver_'.$cfg->sys->current_jt_version)) {
        return true;
    } else {
        return false;
    }
}


/**
  * @brief   Sanitize user input. Currently only a basic check to prevent xss
  *
  * @note
  * - Added as function for possibly more complex sanitizing (future)
  * - INPUT_REQUEST not yet implemented
  * - For explicit check of date and issn FILTER_SANITIZE_NUMBER_INT
  *   would be the best option. @see http://php.net/manual/en/book.filter.php
  *   (well a simple check for numbers and hyphens is enough)
  *
  * @return \b void (get and post are globals)
  */
function sanitize_request() {
  $_GET   = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
  $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

  /* Not yet implemented: unset. Or replace by get + post
  $_REQUEST = filter_input_array(INPUT_REQUEST, FILTER_SANITIZE_NUMBER_INT);
  */
}


/**
  * @brief  check whether a String is a valid ISSN number
  * @see    https://en.wikipedia.org/wiki/International_Standard_Serial_Number#Code_format
  * @return \b int TRUE = valid ISSN, FALSE = ISSN format, but invalid, -1 = bad input format
  */
function valid_issn($input, $validate = TRUE) {
  $m = array();
  if (!preg_match('/^(\d{4})-(\d{3})([\dxX])$/', $input, $m)) {
    return -1; //'ERRORCODE: '. preg_last_error();
  } elseif ($validate) {
    $m1 = strval($m[1]);
    $m2 = strval($m[2]);
    $m3 = strval($m[3]);
    $sum = intval($m1[0]) * 8 + intval($m1[1]) * 7 + intval($m1[2]) * 6 + intval($m1[3]) * 5;
    $sum += intval($m2[0]) * 4 + intval($m2[1]) * 3 + intval($m2[2]) * 2;
    if ($m3 === 'X' || $m3 === 'x') {
      $sum += 10;
    } else {
      $sum += intval($m3);
    }
    return (($sum % 11) === 0);
  }
  // no validation and no preg fail
  return TRUE;
}
?>