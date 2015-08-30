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


?>