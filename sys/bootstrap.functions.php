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
  * @brief   Load user configuration
  *
  * Always use $cfg = cfg_load() to get configuration. Without calling this, nothing will work ;)
  *
  * @see admin/settings.php - there the object is saved
  *
  * @todo Don't always load default config, only if new JournalTouch version is detected. If so
  *       do it just once and save the new user_cfg.
  *
  * @return \b OBJ The configuration object
  */
function cfg_load($user_cfg = '') {
    if (!$user_cfg) $user_cfg = realpath( __DIR__ ).'/../data/config/user_config.php';

    // Always load the config-default.php, so nothing is ever missed; povides $cfg_demo
    require(realpath( __DIR__ ).'/../data/config/config-default.php');

    // If user config exist, add
    if (file_exists($user_cfg)) {
        $restore    = file_get_contents($user_cfg);
        $cfg        = unserialize($restore);

        // Overwrite $cfg_demo settings with user settings and use it as $cfg
        $cfg = merge_recursive_ary_or_obj($cfg_demo, $cfg);
        $cfg->sys->newInstallation  = false;
    } else {
        // rename $cfg_demo to $cfg
        $cfg = $cfg_demo;
    }

    return $cfg;
}


/**
  * @brief  Create a language switch that can be used in any foundation menu
  *         (admin-page or user site)
  *
  * @todo
  * - Use url_rewrite via bootstrap to make it looks nicer (jt.de/EN/index.php)
  * - create something like $cfg->sys->url_home to prevent shit like $relative_dir
  *
  * @return \b STR HTML for language switch
  */
function language_switch($cfg) {
    $lng_options = $switch = '';
    $current_url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

    $relative_dir = (strpos($current_url, 'admin')) ? '../' : '';

    foreach ($cfg->prefs->languages as $set_lang) {
        if ($set_lang != $cfg->prefs->current_lang) {
            $lng_options .= '<li><a id="switch-language" href="'.$current_url.'?lang='.$set_lang.'"><img src="'.$relative_dir.'languages/'.$set_lang.'.gif" /></a></li>';
        }
    }

    // Show a drop down menu if more than two languages are available
    if (count($cfg->prefs->languages) > 2) {
        $switch = '  <li class="divider"></li>
                <li class="has-dropdown switch-language">
                    <a id="langauge-view" href="#"><i class="fi-flag"></i>&#160;'. __('Language').'</a>
                    <ul class="dropdown">'.$lng_options.'</ul>
                </li>';
    }
    // Otherwise just show a simple toggle
    elseif (count($cfg->prefs->languages) == 2) {
        $switch = '<li class="divider"></li>'.$lng_options;
    }
    // And (implicit) nothing if only one language is available

    return $switch;
}

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
    // The very first run, create an info about our initial JT version
    // (the "fresh" installation). It's easy - it's the first info in upgraded
    // history
    $upd_dir    = $cfg->sys->basepath.'admin/upgrade/';

    $historyDir     = glob($cfg->sys->data_upgraded.'ver_*');
    $historyCount   = count($historyDir);

    // Write our initial version
    if ($historyCount === 0) {
        // Special case, coming from 0.3, which had no upgrade mechanism
        // Check for something that only existed in 0.3
        if (file_exists($cfg->sys->basepath.'locale/de_DE.gif')) {
            file_put_contents($cfg->sys->data_upgraded.'ver_0.3', '');
        }
        else {
            file_put_contents($cfg->sys->data_upgraded.'ver_'.$cfg->sys->current_jt_version, '');
        }
    }

    // Now check if the current version differs from our last upgraded version
    if (!file_exists($cfg->sys->data_upgraded.'ver_'.$cfg->sys->current_jt_version)) {
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


/**
  * @brief  Save same data to identify client and only locally apply some
  *         jquery actions. Useful for kiosk or library pc's
  * 
  * @return \b str HTML to output on page and that can be used by javascript
  */
function get_client_infos($cfg) {
    $html = '';

    // Move policies to array (easier to check)
    $policyIPs    = array_flip(array_map('trim', explode(',', $cfg->kiosk->IPs)));
    $policyAgents = array_map('trim', explode(',', $cfg->kiosk->agents));

    // Get client infos
    $clientIP    = $_SERVER['REMOTE_ADDR'];
    $clientAgent = $_SERVER['HTTP_USER_AGENT'];

    // Check if current browser user agent contains the agent(s) set via setting
    $clientAgentHit = false;
    foreach ($policyAgents as $agent) {
        if (stripos($clientAgent, $agent)) {
            $clientAgentHit = true;
        }
    }

    // Create html if IP exists
    if (array_key_exists($clientIP, $policyIPs) || $clientAgentHit) {
        $html = '<span id="kioskPolicies" style="display: none">
                    <!--Script for JournalTouch policies -->
                    <script src="js/kiosk/kiosk_policy.js"></script>
                    <!-- Policies: Main -->
                    <span id="kioskPolicy_NoRSS">'.$cfg->kiosk->policy_NoRSS.'</span>
                    <!-- Policies: Checkout -->
                    <span id="kioskPolicy_NoPrint">'.$cfg->kiosk->policy_NoPrint.'</span>
                    <span id="kioskPolicy_NoSendLib">'.$cfg->kiosk->policy_NoSendLib.'</span>

                    <!--Script for your own policies -->
                    <script src="js/kiosk/kiosk_policy_custom.js"></script>
                    <!-- Client info -->
                    <span id="client_IP">'.$clientIP.'</span>
                    <span id="client_Agent">'.$clientAgent.'</span>
                </span>';
    }

  return $html;
}


/**
  * @brief   Helper function: Merge associative array or object with sub objects
  *         (object can have array(s) as property)
  *
  * @return \b OBJ or ARY The merged array/object
  */
function merge_recursive_ary_or_obj(&$array1, &$array2) {
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
        // It's an object
        if (is_string($key)) {
            if (isset($merged->{$key}) && is_object($merged->{$key})) {
                $merged->{$key} = merge_recursive_ary_or_obj($merged->{$key}, $value);
            }
            else {
                $merged->{$key} = $value;
            }
        }
        // It's an array
        else if (is_array($value)) {
            if (isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = merge_recursive_ary_or_obj($merged[$key], $value);
            }
            else if (is_numeric($key)) {
                if (!in_array($value, $merged))     $merged[] = $value;
            }
            else {
                $merged[$key] = $value;
            }
        }
    }

    return $merged;
}
?>