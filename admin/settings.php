<?php
/**
 * @brief   Edit JournalTouch's configuration easily in one page
 *
 * Introduced: 2015-11-15
 *
 * @todo
 * - Add option for screensaver and basket timeout!
 * - IMPORTANT: Adding new variables to config-default.php and adding a form
 *   field here will raise a php notice, since the variable is not in the (after
 *   saving only used user-config). The clean way were to always use the
 *   default-config as default value. But this readds the overhead I intended to
 *   prevent.
 *   Maybe a between way - check mod date of config-default and force admin to save
 *   once to prevent the notice.
 *
 * @author Tobias Zeumer <tzeumer@verweisungsform.de>
 */
require('../sys/bootstrap.php');

// get avaulable languages - we need this throughout the forms
$langs_available = get_languages();
$langs_frm = frm_languages();


// Start Ajax Save
if (is_ajax()) {
   if (isset($_POST['cfg']) && !empty($_POST['cfg'])) {
        cfg_save();
    	$return['response'] = json_encode('Alles gut');
        echo json_encode($return);
        exit;
    }
}


/**
 * @brief   Helper function to check if the request is an AJAX request
 */
function is_ajax() {
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
// END Ajax Save


/**
 * @brief   Saves user configuration to file
 *
 * Save config variables as Subobject of cfg (as done until ver. 0.3
 *
 * @note    Load function is in bootstrap.functions.php
 */
function cfg_save($user_cfg = '../data/config/user_config.php') {
    $status = false;
    if (isset($_POST['cfg'])) {
        // add the "$cfg" object on top; errm this is stupid?!?
        //$config = new stdClass();
        // encode to json for easier handling
        $config = json_encode($_POST['cfg']);

        // form data comes as strings. Make it true boolean
        // Numbers stay strings. Well, it's not that php does care
        $config = str_replace('"true"', 'true', $config);
        $config = str_replace('"false"', 'false', $config);

        // a) Now decode to get back our object (FALSE)
        $config = json_decode($config, FALSE);

        // b) Now do the same for the cover download and similar options, that must stay an array
        $arrays = json_encode($_POST['cfg_ary']);

        $arrays = str_replace('"true"', 'true', $arrays);
        $arrays = str_replace('"false"', 'false', $arrays);
        // Decode, but this time as associative array!
        $arrays = json_decode($arrays, true);
        // Add the arrays to our config object above
        $config->covers->src_genric     = $arrays['covers']['src_genric'];
        $config->covers->src_publisher  = $arrays['covers']['src_publisher'];
        $config->filters = '';
        $config->filters = $arrays['filters'];
        $config->translations = '';
        $config->translations = $arrays['translations'];

        // Use the ~"php version" of json_encode to save object
        $save = serialize($config);
        $status = file_put_contents($user_cfg, $save);
    }

    return $status;
}


/**
 * @brief   Get available languages from languages folder
 */
function get_languages($path = '../languages/') {
    $dirs = glob($path.'*', GLOB_ONLYDIR); // get all directories within language directory
    foreach ($dirs as &$dir) $dir = str_replace($path, '', $dir); //remove path
    $dirs = array_flip($dirs); // Make dir names the key - easier to compare
    $dirs['en_US'] = -1; // Part a: Make english always the default language
    asort($dirs); // Part b...
    return $dirs;
}


/**
 * @brief   Helper to quickly create checkboxes
 */
function frm_checked($value) {
    $status = '';
    if ($value) {
        $status = 'checked="checked"';
    }
    return $status;
}


/**
 * @brief   Helper to quickly create multiselect options
 */
function frm_selected($value) {
    $status = '';
    if ($value) {
        $status = 'selected="selected"';
    }
    return $status;
}


/**
 * @brief   Creates the options for the select menu with enabled/disabled languages
 *
 * @note    Important to do it before frm_input_translatable() is used,
 *          because we remember the $langs_available status for later use.
 */
function frm_languages() {
    global $cfg, $langs_available; // gosh, classes are just cleaner. Well...

    $langs_unused = $langs_available;
    $options = '';
    foreach ($cfg->prefs->languages AS $language) {
        if (isset($langs_unused[$language])) unset ($langs_unused[$language]);
        $langs_available[$language] = 1; // remember that it initially was enabled
        $options .= '<option value="'.$language.'" '.frm_selected($cfg->prefs->languages[0]).'>'.$language.'</option>';
    }
    // Do it again for the remaining available languages (if something reamains)
    // We set the language as key before for easier comparison - so, don't wonder about the $enabled ;)
    if ($langs_unused) {
        foreach ($langs_unused AS $language => $enabled) {
            $langs_available[$language] = 0; // remember that it initially was disabled
            $options .= '<option value="'.$language.'">'.$language.'</option>';
        }
    }

    return $options;
}


/**
 * @brief   Helper that returns an input field for each available language
 * 2016-03-02: Added $textarea. Set true for textarea, else it defaults to input
 */
function frm_input_translatable($name, $value, $label = '', $aria = '', $textarea = array('rows' => 0, 'cols' => 0)) {
    global $cfg, $langs_available; // @see frm_languages()

    $inputs = '<div class="row">';
    foreach ($langs_available AS $language => $enabled) {
        // Usually we use something like cfg->myOption->language
        // Mainly because it is easier to save the object after sending it as
        // json from the form; fully aware that it is not the most clean way...
        if (isset($value->$language)) {
            $frm_value  = $value->$language;
        }
        // But sometimes using arrays can't really be prevented without getting
        // really ugly. The one and only case currenly: the filters (multidimensional)
        elseif (is_array($value)) {
            $frm_value  = (isset($value[$language])) ? $value[$language] : '';
        } else {
            $frm_value = '';
        }

        $frm_name   = $name.'['.$language.']';
        $css        = ($enabled) ? 'language_enabled' : 'language_disabled';
        $css       .= " toggle_$language";

        $inputs .= '<div class="large-6 columns '.$css.'">
                        <label for="'.$frm_name.'">'.$label.' ('.$language.')</label>';
        // Input or texarea?
        if ($textarea['rows'] == 0) {
            $inputs .= '<input type="input" name="'.$frm_name.'" value="'.$frm_value.'" aria-describedby="'.$aria.'" />';
        } else {
            $inputs .= '<textarea name="'.$frm_name.'" aria-describedby="'.$aria.'" rows="'.$textarea['rows'].'" cols="'.$textarea['cols'].'">'.$frm_value.'</textarea>';
        }
        $inputs .= '</div>';

    }
    $inputs .= '</div>';

    return $inputs;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <title>JournalTouch Settings</title>
        <link rel="stylesheet" href="../css/foundation.min.css" />
        <link rel="stylesheet" href="../css/foundation-icons/foundation-icons.css" />

        <script src="../js/vendor/jquery.js"></script>
        <script src="../js/vendor/jquery.are-you-sure.js"></script>
        <script src="../js/vendor/jquery.serialize_checkbox.js"></script>
        <script src="../js/vendor/jquery-ui/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="../js/vendor/jquery-ui/jquery-ui.css">
        <script src="../js/foundation.min.js"></script>

        <!-- Better select for foundation: https://github.com/roymckenzie/foundation-select -->
        <script src="../js/foundation/foundation-select/foundation-select.js"></script>
        <link rel="stylesheet" href="../js/foundation/foundation-select/foundation-select.css" />
        <script src="../js/vendor/modernizr.js"></script>


        <script type="text/javascript">
            /**
             * @brief   Function to add new filter entry - either click or on enter
             */
            function add_new_entry() {
                var newKey = $('#new_filter_entry').val();
                // Always make keys lowercase to prevent subsequent problems
                newKey = newKey.toLowerCase();

                // If no key was given return
                if (newKey == '') {
                    alert('Enter a key name');
                    return;
                }
                // Check that key does not already exist
                else if ($('#key_'+newKey).length) {
                    alert(newKey+' already exists. Delete it first.');
                    return;
                }

                // Else clone the DUMMY entry
                var cloneEntry = $( ".cloneable_dummy" ).clone(true).html();

                // Replace dummy with the new key
                cloneEntry = cloneEntry.replace(/DUMMY/g, newKey);

                // Add entry
                $('#filter_entries').append(cloneEntry);

                // Empty input field
                $('#new_filter_entry').val('');
            };


            /**
             * @brief   Jquery: Everything is ready to go
             */
            $(document).ready(function(){
                // https://github.com/roymckenzie/foundation-select
                $('select').foundationSelect()
                // Load foundation
                $(document).foundation();


                /**
                 * @brief   Monitor form changes and warn
                 */
                // Warn user if form content was changed, but not saved
                $('form').areYouSure( {'message':'You changed something. Are you sure you don\'t want to save first?'} );
                // Enable save button only as the form is dirty.
                $('form').on('dirty.areYouSure', function() {
                    $('.submit_btn').each(function() {
                        $(this).addClass('alert');
                    });
                    $('#dataUnsaved').removeClass('hidden');
                    $('#dataSaved').addClass('hidden');
                });
                // Form is clean so nothing to save - disable the save button.
                $('form').on('clean.areYouSure', function() {
                    $('.submit_btn').each(function() {
                        $(this).removeClass('alert');
                    });
                    $('#dataUnsaved').addClass('hidden');
                });


                /**
                 * @brief   Methods to add and delete filter entries
                 */
                // Delete (dynamically added) filter elements on click
                $('#filter_entries').on('click', 'a.del_filter_entry', function(event) {
                    event.preventDefault();
                    $(this).parents('.filter_entry').remove();
                });
                // Add filter element on click
                $('a.add_filter_entry').click(function(event) {
                    event.preventDefault();
                    add_new_entry();
                });
                // Add filter element on enter
                $('#new_filter_entry').keypress(function (event) {
                    var key = event.which;
                    if(key == 13) {  // the enter key code
                        event.preventDefault();
                        add_new_entry();
                    }
                });


                /**
                 * @brief   Show help per field
                 *
                 * Howto
                 * - Add aria-describedby="help_VALUE_NAME" to input
                 * - Add a div like
                 *   <div id="help_VALUE_NAME" class="tooltip" role="tooltip" aria-hidden="true"><span>Blabla</span></div>
                 */
                $('[aria-describedby]').on('focus hover mouseenter', function() {
                    id = $(this).attr('aria-describedby');
                    $('#help').html(
                        $('#'+id).clone().children().html()
                    );

                    // Position near selected input field
                    posTop = $(this).offset().top - 105;
                    $('#help').css({
                        position: "absolute",
                        marginLeft: 0, marginTop: 0,
                        top: posTop, left: 0
                    })
                });


                /**
                 * @brief   Ajax on form submit button being clicked
                 */
                $( "form" ).on( "submit", function( event ) {
                    event.preventDefault();

                    // Serialize form data; set unset checkboxes to false
                    var formData = $(this).serialize({ checkboxesAsBools: true });

                    // Testing
                    console.log( formData );

                    // Send ajax request to this file (yeah, I know...)
                    $.ajax({
                        type: 'POST',
                        url: 'settings.php',
                        data: formData,
                        dataType:'json',
//                         beforeSend:function(xhr, settings){
//                         settings.data += '&moreinfo=MoreData';
//                         },
                        success:function(data){
                            $('#dataUnsaved').addClass('hidden');
                            $('#dataSaved').removeClass('hidden');
                            $('.submit_btn').each(function() {
                                $(this).removeClass('alert');
                            });
                            // reset areYouSure
                             $('form').trigger('reinitialize.areYouSure');
                        },
                        error: function(data) {
                            alert("Failure!");
                        }
                    });
                });

            });


            /**
             * @brief   Make a list sortable (Covers generic for now only
             *
             * @note    Hmm, nice alternative but complex https://github.com/vicb/bsmSelect
             */
            $(function() {
                $( "#sortableCoverGeneric" ).sortable();
                $( "#sortableCoverGeneric" ).disableSelection();
            });
        </script>

        <style type="text/css" media="screen">
            .hidden         {display: none;}
            .tabs.vertical  {width: 100%;}
            label           {margin-right: 10px;}

            /* make rows use the full screen size */
            .fullWidth {width: 80%; margin-left: auto; margin-right: auto; max-width: initial;}

            /* Sticky alert boxes */
            .alert-box {position: fixed; top: 50; right: 10; width: 150px; z-index: 999;}

            .tab-title button {width: 100%; margin: 0;}

            .language_enabled  {}
            .language_disabled {display: none;}

            .sortable { list-style-type: none; margin: 0; padding: 0; width: 60%; }
            .sortable li { margin: 0 3px 5px 3px; padding: 0; padding-left: 1.5em; font-size: 1.4em; height: 37px; }
            .sortable li span { position: absolute; margin-left: -1.3em; }
            .sortable li a {color: #ffffff !important;}

            /* Slightly smaller Tab Menu */
            .tabs .tab-title > a {padding: 1rem 1.5rem; font-size: 0.9rem};
        </style>
    </head>
<body>
<?php include('menu.inc') ?>
<div class="row fullWidth">
    <div class="small-10 medium-10 large-10 columns">
        <h2><?php echo __('JournalTouch Settings for Admins') ?></h2>
    </div>
</div>
<div class="row fullWidth">
    <form>
    <!-- Main structure 1: Menu column -->
    <div class="small-2 columns">
        <!--  action="settings.php" method="get" -->
        <!-- define Tabs -->
        <ul class="tabs vertical" data-tab="">
            <li class="tab-title"><button type="submit" class="button submit_btn" name="save"><?php echo __('Save') ?></button></li>
            <li class="tab-title active"><a href="#formTab1"><?php echo __('Your Institution') ?></a></li>
            <li class="tab-title"><a href="#formTab2"><?php echo __('Preferences') ?></a></li>
            <li class="tab-title"><a href="#formTab3"><?php echo __('Translations') ?></a></li>
            <li class="tab-title"><a href="#formTab4"><?php echo __('API') ?></a></li>
            <li class="tab-title"><a href="#formTab5"><?php echo __('Covers') ?></a></li>
            <li class="tab-title"><a href="#formTab6"><?php echo __('Filter') ?></a></li>
            <li class="tab-title"><a href="#formTab7"><?php echo __('Kiosk PCs') ?></a></li>
            <li class="tab-title"><a href="#formTab8"><?php echo __('Mailing') ?></a></li>
            <li class="tab-title"><a href="#formTab9"><?php echo __('Paths') ?></a></li>
            <li class="tab-title"><a href="#formTab10"><?php echo __('Journal List') ?></a></li>
            <li class="tab-title"><button type="submit" class="button submit_btn" name="save"><?php echo __('Save') ?></button></li>
        </ul>
    </div>
    <!-- Main structure 2: Body column -->
    <div class="small-8 columns">
        <!-- start Tabs -->
        <div class="tabs-content">
            <div class="content active" id="formTab1">
                <h3><?php echo __('Settings for your institution') ?></h3>
                <fieldset>
                    <legend><?php echo __('Your institution') ?></legend>
                        <?php echo frm_input_translatable('cfg_ary[translations][prefs_lib_name]', $cfg->translations['prefs_lib_name'], __('Institution name'), 'help_lib_name') ?>
                        <div id="help_lib_name" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Your institution name. Currently only shown in the about text.') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Institution: Discover and access') ?></legend>
                        <label for="cfg[prefs][inst_service]"><?php echo __('Catalogue Link') ?></label>
                            <input type="text" name="cfg[prefs][inst_service]" value="<?php echo $cfg->prefs->inst_service ?>" aria-describedby="help_inst_service"/>
                            <div id="help_inst_service" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Adds a link with the journal\'s issn to your catalogue or discovery. The link shows as a meta button next to the journal in the list and/or above the toc. Leave empty to disable.') ?></span></div>
                        <label for="cfg[prefs][proxy]"><?php echo __('Proxy') ?></label>
                            <input type="text" name="cfg[prefs][proxy]" value="<?php echo $cfg->prefs->proxy ?>" aria-describedby="help_proxy" />
                            <div id="help_proxy" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('If you got a proxy (e.g. EZproxy) to allow patrons outside of your ip range access then set the base url here (e.g. http://www.umm.uni-heidelberg.de/ezproxy/login.auth?url=).') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][show_dl_button]" <?php echo frm_checked($cfg->prefs->show_dl_button) ?> aria-describedby="help_show_dl_button">
                            <label for="cfg[prefs][show_dl_button]"><?php echo __('Enable button for direct download?') ?></label><br />
                            <div id="help_show_dl_button" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('If you enable this a button for directly downloading a article\'s pdf file (and onyl pdf currently) is displayed in the toc for a journal. This only applies to publishers where we were able to figure out the link to do that. The idea is to not force a user to the publishers landing page.') ?></span></div>
                        <label for="cfg[prefs][sfx]"><?php echo __('SFX Service') ?></label>
                            <input type="text" name="cfg[prefs][sfx]" value="<?php echo $cfg->prefs->sfx ?>" aria-describedby="help_sfx" />
                            <div id="help_sfx" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Your sfx baseurl (e.g. http://sfx.gbv.de/sfx_tuhh). Currently used as alternative for the direct download button.') ?></span></div>
                </fieldset>
            </div>
            <div class="content" id="formTab2">
                <h3><?php echo __('Preferences') ?></h3>
                <fieldset>
                    <legend><?php echo __('Preferences: Navigation Menues') ?></legend>
                        <input type="checkbox" name="cfg[prefs][menu_show_listview]" <?php echo frm_checked($cfg->prefs->menu_show_listview) ?> aria-describedby="help_menu_show_listview" />
                            <label for="cfg[prefs][menu_show_listview]"><?php echo __('Enable list view') ?></label><br />
                            <div id="help_menu_show_listview" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Show menu entry to switch to list view (otherwise it\'s always the grid view with covers)') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][menu_show_sort]" <?php echo frm_checked($cfg->prefs->menu_show_sort) ?> aria-describedby="help_menu_show_sort" />
                            <label for="cfg[prefs][menu_show_sort]"><?php echo __('Enable sorting by date or alphabetical') ?></label><br />
                            <div id="help_menu_show_sort" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Show the menu entry to switch between alphabetical and date sorting (otherwise it\'s the default sort set below)') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][menu_show_tagcloud]" <?php echo frm_checked($cfg->prefs->menu_show_tagcloud) ?> aria-describedby="help_menu_show_tagcloud" />
                            <label for="cfg[prefs][menu_show_tagcloud]"><?php echo __('Enable tags') ?></label><br />
                            <div id="help_menu_show_tagcloud" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Show the menu entry for the tagcloud?') ?></span></div>
                        <label for="cfg[prefs][min_tag_freq]"><?php echo __('Minimum tag frequency') ?></label><br />
                            <input type="text" name="cfg[prefs][min_tag_freq]" value="<?php echo $cfg->prefs->min_tag_freq ?>" aria-describedby="help_min_tag_freq" />
                            <div id="help_min_tag_freq" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('How often must a tag be used at least to show up in the tagcloud (if enabled)? Useful if you got very many tags.') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Preferences: Default View') ?></legend>
                        <input type="checkbox" name="cfg[prefs][default_sort_date]" <?php echo frm_checked($cfg->prefs->default_sort_date) ?> aria-describedby="help_default_sort_date" />
                            <label for="cfg[prefs][default_sort_date]"><?php echo __('Sort by date by default') ?></label><br />
                            <div id="help_default_sort_date" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Enabled to sort date by default. Otherwise it is alphabetical.') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][show_metainfo_list]" <?php echo frm_checked($cfg->prefs->show_metainfo_list) ?> aria-describedby="help_show_metainfo_list" />
                            <label for="cfg[prefs][show_metainfo_list]"><?php echo __('Show meta menu in journal list') ?></label><br />
                            <div id="help_show_metainfo_list" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Enable to show the block with the meta menu buttons rightside from the covers (toc, weblink, shelfmark etc.).') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][show_metainfo_toc]" <?php echo frm_checked($cfg->prefs->show_metainfo_toc) ?> aria-describedby="help_show_metainfo_toc" />
                            <label for="cfg[prefs][show_metainfo_toc]"><?php echo __('Show meta menu above toc') ?></label><br />
                            <div id="help_show_metainfo_toc" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Show the block with the meta menu buttons above the toc. <strong>Note</strong>: If show_metainfo_list is true, it will show above the toc, even if set to false here. Sorry for now if you happen to exactly not wanting this...') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][rss]" <?php echo frm_checked($cfg->prefs->rss) ?> aria-describedby="help_rss" />
                            <label for="cfg[prefs][rss]"><?php echo __('Enable RSS Meta Button (read help carefully)') ?></label><br />
                            <div id="help_rss" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('This enabled a link to the JournalTocs RSS feed. Since the feed is only usable with an account, YOUR email must be used and is visible in the url. Two aspects make this charming. First and before all: if you use a proxy (see Your Institution), the links in the rss feed are "proxified". Second: it\'s easier to provide the user with a link than explaining how to create an account themselves at JournalTocs.') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][show_orbit]" <?php echo frm_checked($cfg->prefs->show_orbit) ?> aria-describedby="help_show_orbit" />
                            <label for="cfg[prefs][show_orbit]"><?php echo __('Enable Orbit') ?></label>
                            <div id="help_show_orbit" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('This displayed a slide of the newest issues at the top of the journal list.') ?></span></div>
                        <label for="cfg[prefs][screensaver_secs]"><?php echo __('Screesaver timing') ?></label><br />
                            <input type="text" name="cfg[prefs][screensaver_secs]" value="<?php echo $cfg->prefs->screensaver_secs ?>" aria-describedby="help_screensaver_secs" />
                            <div id="help_screensaver_secs" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Set the idle time in seconds before screensaver is displayed. Set to 0 to disable screensaver. Default is 240 seconds (4 minutes).') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Preferences: Checkout') ?></legend>
                        <label for="cfg[prefs][clear_basket]"><?php echo __('Basket timing') ?></label><br />
                            <input type="text" name="cfg[prefs][clear_basket]" value="<?php echo $cfg->prefs->clear_basket ?>" aria-describedby="help_clear_basket" />
                            <div id="help_clear_basket" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Set the idle time in seconds before the basket is cleared. Default is 260 seconds (4 minutes, 20 seconds). Hint: set it slightly higher then the screensaver timing.') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][allow_ask_pdf]" <?php echo frm_checked($cfg->prefs->allow_ask_pdf) ?> aria-describedby="help_allow_ask_pdf" />
                            <label for="cfg[prefs][allow_ask_pdf]"><?php echo __('Allow mail for PDF') ?></label>
                            <div id="help_allow_ask_pdf" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Checkout action: May users send a mail to the library asking to get the pdf send to them?') ?></span></div>
                </fieldset>
            </div>

            <div class="content" id="formTab3">
                <h3><?php echo __('Translations') ?></h3>
                <fieldset>
                    <legend><?php echo __('Default language and languages enabled for switching') ?></legend>
                    <div class="row">
                        <div class="small-6 medium-6 large-6 columns">
                            <label for="cfg[prefs][language_default]" aria-describedby="help_language_default"><?php echo __('Default language') ?></label>
                                <select name="cfg[prefs][language_default]" aria-describedby="help_language_default">
                                <?php
                                    foreach ($langs_available AS $language => $enabled) {
                                        $disabled = (!$enabled) ? ' disabled' : '';
                                        $selected = ($language == $cfg->prefs->language_default) ? ' selected="selected"' : '';
                                        echo '<option value="'.$language.'" '.$disabled.$selected.'>'.$language.'</option>';
                                    }
                                ?>
                                </select>
                                <div id="help_language_default" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The language used initially.') ?></span></div>
                        </div>
                        <div class="small-6 medium-6 large-6 columns">
                            <label for="cfg[prefs][languages][]" aria-describedby="help_languages"><?php echo __('Enabled languages (multiple choice)') ?></label>
                                <select id="multiple-languages" multiple data-prompt="<?php echo __('Enable languages') ?>" name="cfg[prefs][languages][]" aria-describedby="help_languages">
                                    <?php echo $langs_frm; ?>
                                </select>
                                <div id="help_languages" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Enable all languages that should be available to visitors. For alle actived languages some fields are translatable in this admin menu. If you activate a new language, the translation fields will only show up, after you saved the settings and reload the page.') ?></span></div>
                        </div>
                    </div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Translations: General Elements') ?></legend>
                        <?php echo frm_input_translatable('cfg_ary[translations][main_tagline]', $cfg->translations['main_tagline'], __('Tagline'), 'help_trans_main_tagline') ?>
                        <div id="help_trans_main_tagline" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The tagline at the left upper screen.') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][main_float_sendArticle]', $cfg->translations['main_float_sendArticle'], __('Floating "Send articles"'), 'help_trans_main_float_sendArticle') ?>
                        <div id="help_trans_main_float_sendArticle" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The button floating at the right side, so a user can always send his basket articles, without going up to the menu.') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Translations: Menus') ?></legend>
                        <?php echo frm_input_translatable('cfg_ary[translations][menu_tag]',  $cfg->translations['menu_tag'], __('Tags'), 'help_trans_menu_tag') ?>
                        <div id="help_trans_menu_tag" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Name for the tag menu.') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][menu_filter]',  $cfg->translations['menu_filter'], __('Filters'), 'help_trans_menu_filter') ?>
                        <div id="help_trans_menu_filter" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The name of the filter menu.') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][menu_filter_special]',  $cfg->translations['menu_filter_special'], __('Special Filter'), 'help_trans_menu_filter_special') ?>
                        <div id="help_trans_menu_filter_special" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The name of you special filter within the filter menu (special filters are set in column 4 of the journals.csv).') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][menu_basket]',  $cfg->translations['menu_basket'], __('My Basket'), 'help_trans_menu_basket') ?>
                        <div id="help_trans_menu_basket" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The basket menu name.') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Translations: Menus with toggle') ?></legend>
                        <?php echo frm_input_translatable('cfg_ary[translations][menu_sort_date]',  $cfg->translations['menu_sort_date'], __('Sort by date'), 'help_trans_menu_sort_date') ?>
                        <div id="help_trans_menu_sort_date" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The name of the sort menu: when switching to date sort.') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][menu_sort_az]',  $cfg->translations['menu_sort_az'], __('Sort alphabetically'), 'help_trans_menu_sort_az') ?>
                        <div id="help_trans_menu_sort_az" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The name of the sort menu: when switching to alphabetical sort.') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][menu_list]',  $cfg->translations['menu_list'], __('List view'), 'help_trans_menu_list') ?>
                        <div id="help_trans_menu_list" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The name of the menu to toggle the list view') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][menu_grid]',  $cfg->translations['menu_grid'], __('Grid view'), 'help_trans_menu_grid') ?>
                        <div id="help_trans_menu_grid" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The name of the menu to toggle the grid view') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Translations: Meta Info Buttons') ?></legend>
                        <?php echo frm_input_translatable('cfg_ary[translations][meta_toc]',  $cfg->translations['meta_toc'], __('Meta Button: TOC (available)'), 'help_trans_meta_toc') ?>
                        <div id="help_trans_meta_toc" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The button indicating that a toc is available (see journals.csv column 14).') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][meta_journalHP]',  $cfg->translations['meta_journalHP'], __('Meta Button: Journal Homepage'), 'help_trans_meta_journalHP') ?>
                        <div id="help_trans_meta_journalHP" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The button linking to the journal\'s homepage (see journals.csv column 16).') ?></span></div>
                        <?php echo frm_input_translatable('cfg_ary[translations][meta_inst_service]',  $cfg->translations['meta_inst_service'], __('Library service'), 'help_trans_meta_inst_service') ?>
                        <div id="help_trans_meta_inst_service" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The link to you catalogue/service witht the journal\'s issn appended.') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Translations: Other') ?></legend>
                        <?php //@TODO: Make inputing html nicer and easier ?>
                        <?php echo frm_input_translatable('cfg_ary[translations][other_about]',  $cfg->translations['other_about'], __('About and Screensaver'), 'help_trans_other_about', $textarea = array('rows' => 25, 'cols' => 55)) ?>
                        <div id="help_trans_other_about" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The text displayed when screensaver triggers or user clicks the about button. Please use [ and ] instead of &lt; and &gt; for HTML tags. The text fields are sized for an amount of text that can reasonably be displayed on a single screen.') ?></span></div>
                </fieldset>
            </div>
            <div class="content" id="formTab4">
                <h3><?php echo __('API Settings') ?></h3>
                <fieldset>
                    <legend><?php echo __('Performance') ?></legend>
                        <div class="panel"><?php echo __('Only activate caching if you do<ul><li>A (daily) cron like <i>wget -O - -q -t 1 "http://myinstallation.net/admin/updateTocs.php?optRecent=on&optCovers=on&upd=true" >/dev/null 2>&1</i></li><li>If you got a premium Jtoc account: a cron too for: http://my.journaltouch.local/admin/services/getLatestJournals.php (replace url in above example for cron job)</li></ul>') ?></div>
                        <input type="checkbox" name="cfg[prefs][cache_toc_enable]" <?php echo frm_checked($cfg->prefs->cache_toc_enable) ?> aria-describedby="help_cache_toc_enable" />
                            <label for="cfg[prefs][cache_toc_enable]"><?php echo __('Enable Caching Tocs') ?>?</label><br />
                            <div id="help_cache_toc_enable" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Caches fetched tocs so they only are processed once there is a new issue. Recommended.') ?></span></div>
                        <input type="checkbox" name="cfg[prefs][cache_main_enable]" <?php echo frm_checked($cfg->prefs->cache_main_enable) ?> aria-describedby="help_cache_main_enable" />
                            <label for="cfg[prefs][cache_main_enable]"><?php echo __('Enabling Caching Main Page') ?></label>
                            <div id="help_cache_main_enable" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Basically JT only serves a static page, so it makes sense not to generate it all the time. Anyway, best is, you only activate this, if run your cron often. Since this caching might be a source for confusion, it is not really recommended.') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Api: General Settings') ?></legend>
                        <input type="checkbox" name="cfg[api][all][articleLink]" <?php echo frm_checked($cfg->api->all->articleLink) ?> aria-describedby="help_articleLink" />
                            <label for="cfg[api][all][articleLink]"><?php echo __('Make articles in tocs a clickable link') ?></label>
                            <div id="help_articleLink" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('If people access JournalTouch from somewhere where they have no access to the licenced ressources, disabling the linking might be an option. They can still add the items to the basket and order them (or fetch them in the library themselves).') ?></span></div>
                        <label for="cfg[api][all][is_new_days]"><?php echo __('Mark issue for how many days as new?') ?></label>
                            <input type="text" name="cfg[api][all][is_new_days]" value="<?php echo $cfg->api->all->is_new_days ?>" aria-describedby="help_is_new_days" />
                            <div id="help_is_new_days" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('If a new issue is found on the update (use a daily cron to keep track) it is marked as new for as many days as specified here. You might think it\'d be much cooler if the journal\'s publishing frequency would be used. Well, we think so too, but where to get it - or who would want to enter it by hand. Anyway, if you got a premium account and use the data for the live view (see below) you can ignore this setting.)') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Api: JournalTocs') ?></legend>
                        <label for="cfg[api][jt][account]"><?php echo __('Your JournalTocs Account (Mail)') ?></label>
                            <input type="text" name="cfg[api][jt][account]" value="<?php echo $cfg->api->jt->account ?>" aria-describedby="help_account" />
                            <div id="help_account" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The mail you are registered with at JournalTocs. This is crucial for making the most of JournalTouch!') ?></span></div>
                        <input type="checkbox" name="cfg[api][jt][premium]" <?php echo frm_checked($cfg->api->jt->premium) ?> aria-describedby="help_premium" />
                            <label for="cfg[api][jt][premium]"><?php echo __('Got Premium Account?') ?></label>
                            <div id="help_premium" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Enable if you have a premium account. You get slightly better data for recent issue dates. Check yourself what the benefits of a premium account are - it is not required.') ?></span></div>
                        <label for="cfg[api][jt][updates]"><?php echo __('URL for JournalTocs Premium') ?></label>
                            <input type="text" name="cfg[api][jt][updates]" value="<?php echo $cfg->api->jt->updates ?>" aria-describedby="help_updates" />
                            <div id="help_updates" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('This is just for the unlikely case that the url ever changes.') ?></span></div>
                        <input type="checkbox" name="cfg[api][jt][upd_show]" <?php echo frm_checked($cfg->api->jt->upd_show) ?> aria-describedby="help_upd_show" />
                            <label for="cfg[api][jt][upd_show]"><?php echo __('Use premium data for live view (slow!)') ?></label>
                            <div id="help_upd_show" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Uses premium infos to mark issues as new. Slows page loading down') ?></span></div>
                </fieldset>
            </div>
            <div class="content" id="formTab5">
                <a href="#" data-reveal-id="help_coverSettingsmyModal" class="button right"><?php echo __('Information & Disclaimer') ?></a>
                <div id="help_coverSettingsmyModal" class="reveal-modal xlarge" data-reveal aria-labelledby="help_coverSettingsmyModal" aria-hidden="true" role="dialog">
                  <?php echo __('<h2>Cover Downloading</h2>
                    <p>Put your own covers into the data/covers folder. The name must be the issn specified for the journal. The extension might be jpg, gif or png.</p>
                    <p>There are rumours that cover api\'s for journals exist, but no free service is known. So, it\'s up to you how you get the covers (ask vendor, journal publisher...). While not to be found on JournalTocs official website, the premium service seems to offer cover links via api (which in turn points to the publishers sites) - info as of 2015-10-30.</p>
                    <p>As of version 0.4.0 JournalTouch provides a way to download covers. This divided into two sections: 1) Generic sources and 2) Publisher\'s websites. By default everything is deactivated. You can enable whatever service you want to query for covers on this setting page. Be aware that you should know perfectly well if you are legible to do so (e.g. by some kind of fair use law or special agreements with the specified service/publisher). Using STMcovers is most likely safe since the domain is registered to Elesevier and it offers cover downloads explicitly, <strong>yet again - it\'s your responsibility!</strong>
                    <p>As for the logic:
                    <ul>
                    <li>1. If a cover in data/covers exists none will ever be downloaded (e.g. useful if covers never change anyway). This one will always be displayed.</li>
                    <li>2. If a cover exists in data/covers/api it will be displayed</li>
                    <li>3. If no cover exists in either folder, it will be downloaded using the activated services.</li>
                    <li><ul>
                    <li>3.1 Enabled publisher sources are preferred to generic sources (you might want to disable Elsevier since STMcovers provides real high quality ;))</li>
                    <li>3.2 If no publisher is provided (which automatically is the case if found at JournalTocs) generic sources are checked</li>
                    </ul></li>
                    <li>4. If a cover exists in data/covers/api, but a new issue is found by the updater, it will be redownloaded.</li>
                    </p>
                    <p>Note: If yout set anything for api no images from the folder will be loaded</p>
                  ') ?>
                  <a class="close-reveal-modal" aria-label="Close">&#215;</a>
                </div>
                <h3><?php echo __('Cover Settings') ?></h3><br />
                <fieldset>
                    <legend><?php echo __('Covers: General Settings') ?></legend>
                    <label for="cfg[covers][placeholder]"><?php echo __('Cover Placeholder') ?></label>
                        <input type="text" name="cfg[covers][placeholder]" value="<?php echo $cfg->covers->placeholder ?>" aria-describedby="help_placeholder" />
                        <div id="help_placeholder" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('<p>An image that is used if no journal cover is available.</p>') ?></span></div>
                    <label for="cfg[covers][placeholder]"><?php echo __('Cover Api') ?></label>
                        <input type="text" name="cfg[covers][api]" value="<?php echo $cfg->covers->api ?>" aria-describedby="help_api" />
                        <div id="help_api" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('You might input an url where an issn can be appended ("http://myservice.net/issn=") - and mail us if you got such a thing ;)') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Download: Generic sources') ?></legend>
                    <div class="panel"><?php echo __('Drag service in the order you want them to be checked for covers') ?></div>
                    <ul id="sortableCoverGeneric" class="sortable">
                        <li class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
                            <input type="checkbox" name="cfg_ary[covers][src_genric][STMcovers]" <?php echo frm_checked($cfg->covers->src_genric['STMcovers']) ?> aria-describedby="help_src_genric" />
                                <label for="cfg_ary[covers][src_genric][STMcovers]">STMcovers</label>
                                <a href="http://www.stmcovers.com/" class="button tiny info right" style="margin: 0" target="_blank">Website</a>
                        </li>
                        <li class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
                            <input type="checkbox" name="cfg_ary[covers][src_genric][JournalTocs]" <?php echo frm_checked($cfg->covers->src_genric['JournalTocs']) ?> aria-describedby="help_src_genric" />
                                <label for="cfg_ary[covers][src_genric][JournalTocs]">JournalTocs</label>
                                <a href="http://www.journaltocs.ac.uk/" class="button tiny info right" style="margin: 0" target="_blank">Website</a>
                        <li class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
                            <input type="checkbox" name="cfg_ary[covers][src_genric][Lehmanns]" <?php echo frm_checked($cfg->covers->src_genric['Lehmanns']) ?> aria-describedby="help_src_genric" />
                                <label for="cfg_ary[covers][src_genric][Lehmanns]">Lehmanns</label>
                                <a href="http://www.lehmanns.de/" class="button tiny info right" style="margin: 0" target="_blank">Website</a>
                        </li>
                        <li class="ui-state-default"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
                            <input type="checkbox" name="cfg_ary[covers][src_genric][SubscribeToJournals]" <?php echo frm_checked($cfg->covers->src_genric['SubscribeToJournals']) ?> aria-describedby="help_src_genric" />
                                <label for="cfg_ary[covers][src_genric][SubscribeToJournals]">Subscribe To Journals</label>
                                <a href="http://subscribetojournalsonline.com/" class="button tiny info right" style="margin: 0" target="_blank">Website</a>
                        </li>
                    </ul>
                    <div id="help_src_genric" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('These are sites where covers for many publishers can be fetched. If a cover is found the remaining sources won\'t be checked.') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Download: Publishers') ?></legend>
                    <input type="checkbox" name="cfg_ary[covers][src_publisher][DeGruyter]" <?php echo frm_checked($cfg->covers->src_publisher['DeGruyter']) ?> aria-describedby="help_src_publisher" />
                        <label for="cfg_ary[covers][src_publisher][DeGruyter]">De Gruyter</label>
                    <input type="checkbox" name="cfg_ary[covers][src_publisher][Elsevier]" <?php echo frm_checked($cfg->covers->src_publisher['Elsevier']) ?>aria-describedby="help_src_publisher" />
                        <label for="cfg_ary[covers][src_publisher][Elsevier]">Elsevier</label>
                    <input type="checkbox" name="cfg_ary[covers][src_publisher][Sage]" <?php echo frm_checked($cfg->covers->src_publisher['Sage']) ?>>
                        <label for="cfg_ary[covers][src_publisher][Sage]">Sage</label>
                    <input type="checkbox" name="cfg_ary[covers][src_publisher][Springer]" <?php echo frm_checked($cfg->covers->src_publisher['Springer']) ?>aria-describedby="help_src_publisher" />
                        <label for="cfg_ary[covers][src_publisher][Springer]">Springer</label>
                    <div id="help_src_publisher" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('For these publishers JournalTouch provides a way for direct cover download.') ?></span></div>
                </fieldset>
            </div>
            <div class="content" id="formTab6">
                <h3><?php echo __('Filter Settings') ?></h3>
                <!-- Button to add a new filter entry -->
                <div class="row collapse">
                    <div class="large-12 columns">
                        <div class="row collapse">
                            <div class="small-10 columns">
                                <input type="text" name="new_filter_entry" id="new_filter_entry" placeholder="<?php echo __('New filter alias for journals.csv table') ?>" aria-describedby="help_new_filter_entry" />
                                <div id="help_new_filter_entry" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('In the filter column (= column 2 in journals.csv) you write a shorthand code for the filter. Here you map that shorthand code to some human readable name and add translations. These will be shown in the filter menu to filter by topic.') ?></span></div>
                            </div>
                            <div class="small-2 columns">
                                <a href="#" class="button postfix add_filter_entry"><i class="fi-plus"></i> <?php echo __('Add filter') ?></a>
                            </div>
                        </div>
                    </div>
                <!-- The active filter entries -->
                <fieldset id="filter_entries">
                    <legend><?php echo __('Filters for filter menu') ?></legend>
                    <?php
                        // Sort by filter key first
                        ksort($cfg->filters);
                        foreach ($cfg->filters AS $f_key => $translations) {
                            // Don't show the dummy
                            if ($f_key == 'DUMMY') continue;

                            // Show the user entries
                            echo '  <div class="row collapse filter_entry">
                                        <label for="'.$f_key.'">Filtername '.$f_key.'</label>
                                        <div class="small-9 columns">
                                            <input type="text" id="key_'.$f_key.'" name="'.$f_key.'" value="'.$f_key.'" aria-describedby="help_filter_entry_key" readonly />
                                        </div>
                                        <div class="small-3 columns">
                                            <span class="postfix"><a href="#" class="del_filter_entry">'.__('Delete').'</a></span>
                                        </div>';
                                        $f_name = 'cfg_ary[filters]['.$f_key.']';
                                        echo frm_input_translatable($f_name, $cfg->filters[$f_key], $f_key, 'help_filter_entry');
                            echo '  </div>';
                        }

                        // Create a dummy as template
                        echo '<span class="cloneable_dummy hidden">
                                <div class="row collapse filter_entry">
                                    <label for="DUMMY">Filtername DUMMY</label>
                                    <div class="small-9 columns">
                                        <input type="text" id="key_DUMMY" name="DUMMY" value="DUMMY" aria-describedby="help_filter_entry_key" readonly />
                                    </div>
                                    <div class="small-3 columns">
                                        <span class="postfix"><a href="#" class="del_filter_entry">'.__('Delete').'</a></span>
                                    </div>';
                                    $f_name = 'cfg_ary[filters][DUMMY]';
                                    echo frm_input_translatable($f_name, 'DUMMY', 'DUMMY', 'help_filter_entry');
                        echo '  </div>
                            </span>';
                    ?>
                    <div id="help_filter_entry_key" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('You can\'t change a filter shorthand later. Remove and readd it above.') ?></span></div>
                    <div id="help_filter_entry" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Give your shorthand a meaningful name to show up in the filter menu.') ?></span></div>
                </fieldset>
                </div>
            </div>
            <div class="content" id="formTab7">
                <h3><?php echo __('Kiosk PCs') ?></h3>
                <fieldset>
                    <legend><?php echo __('Agent\'s & IP\'s') ?></legend>
                        <div class="panel"><?php echo __('<p>If you display JournalTouch on a kiosk device within your institution, you might want to disable certain settings. For example displaying printing buttons might make no sense.<br />Instead of setting up a second JournalTouch installation, you just can disable certain elements for an IP or a browser user agent.</p><ul><li><strong>IP</strong>: only works if the kiosk PC is accessing the webserver directly (not behind a proxy or a NAT router)</li><li><strong>Browser agent</strong>: Kiosk software often offer an easy way to set a custom user agent string for the browser. If you don\'t got such software or it doesn\'t come with such an option, you can do it yourself. It can be done easily for most browser, e.g. with a plugin.</li></ul><p>You can include many IPs or agents, but you can\'t set different policies for each. If you require that, edit js/kiosk/kiosk_policy_custom.js - this file won\'t be overwritten</p>') ?></div>

                        <label for="cfg[kiosk][IPs]"><?php echo __('IP Addresses') ?></label><br />
                            <input type="text" name="cfg[kiosk][IPs]" value="<?php echo $cfg->kiosk->IPs ?>" aria-describedby="help_kiosk_ips" />
                            <div id="help_kiosk_ips" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Enter one or multiple IPs. Separate by comma (<i>1.2.3.1, 1.2.3.2</i>).') ?></span></div>
                        <label for="cfg[kiosk][agents]"><?php echo __('Browser User Agents') ?></label><br />
                            <input type="text" name="cfg[kiosk][agents]" value="<?php echo $cfg->kiosk->agents ?>" aria-describedby="help_kiosk_agents" />
                            <div id="help_kiosk_agents" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Enter one or more user agent (the relevant keyword is enough). You might enter multiple agents. Separate by comma.') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Policies: Checkout page') ?></legend>
                        <input type="checkbox" name="cfg[kiosk][policy_NoPrint]" <?php echo frm_checked($cfg->kiosk->policy_NoPrint) ?> aria-describedby="help_kiosk_ips" />
                            <label for="cfg[kiosk][policy_NoPrint]"><?php echo (__('Disable').' "'.__('View &amp; Print').'"') ?>?</label><br />
                            <div id="help_kiosk_policy_NoPrint" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Hide printing on kiosk pc\'s.') ?></span></div>

                        <input type="checkbox" name="cfg[kiosk][policy_NoSendLib]" <?php echo frm_checked($cfg->kiosk->policy_NoSendLib) ?> aria-describedby="help_kiosk_policy_NoSendLib" />
                            <label for="cfg[kiosk][policy_NoSendLib]"><?php echo (__('Disable').' "'.__('Send to library to get PDFs').'"') ?>?</label><br />
                            <div id="help_kiosk_policy_NoSendLib" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Don\'t allow users to send request for PDFs to library on kiosk pc\'s. To completly disable this feature, go to "Settings"') ?></span></div>
                </fieldset>
                <fieldset>
                    <legend><?php echo __('Policies: Main page') ?></legend>
                        <input type="checkbox" name="cfg[kiosk][policy_NoRSS]" <?php echo frm_checked($cfg->kiosk->policy_NoRSS) ?> aria-describedby="help_kiosk_policy_NoRSS" />
                            <label for="cfg[kiosk][policy_NoRSS]"><?php echo __('Disable RSS meta button') ?>?</label><br />
                            <div id="help_kiosk_policy_NoRSS" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Don\'t show RSS button in meta menu on kiosk pc\'s. Of little use there.') ?></span></div>
                </fieldset>
            </div>
            <div class="content" id="formTab8">
                <h3><?php echo __('Mailing Settings') ?></h3>
                    <fieldset>
                        <legend><?php echo __('Mailing: General Settings') ?></legend>
                            <label for="cfg[mail][domain]"><?php echo __('Restrict users addresses to domain') ?></label>
                                <input type="text" name="cfg[mail][domain]" value="<?php echo $cfg->mail->domain ?>" aria-describedby="help_domain" />
                                <div id="help_domain" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('If you want to restrict mailing to institutional user, you might specify the domain here (e.g. john.doe@<strong>my-university.net</strong>). Leave empty to allow all user mail addresses.') ?></span></div>
                    </fieldset>
                    <fieldset>
                        <legend><?php echo __('Mailing: To User') ?></legend>
                            <label for="cfg[mail][fromAddress]"><?php echo __('Your From Address') ?></label>
                                <input type="text" name="cfg[mail][fromAddress]" value="<?php echo $cfg->mail->fromAddress ?>" aria-describedby="help_fromAddress" />
                                <div id="help_fromAddress" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('From which address shall mails be sent (e.g. library@my-university.net).') ?></span></div>
                            <?php echo frm_input_translatable('cfg_ary[translations][mail_fromName]', $cfg->translations['mail_fromName'], __('From Name'), 'help_mailToUser') ?>
                            <?php echo frm_input_translatable('cfg_ary[translations][mail_subjectToUser]', $cfg->translations['mail_subjectToUser'], __('Subject'), 'help_mailToUser') ?>
                            <?php echo frm_input_translatable('cfg_ary[translations][mail_bodyMessage]', $cfg->translations['mail_bodyMessage'], __('Message body'), 'help_mailToUser') ?>
                            <?php echo frm_input_translatable('cfg_ary[translations][mail_bodySalutation]', $cfg->translations['mail_bodySalutation'], __('Salutation'), 'help_mailToUser') ?>
                            <?php echo frm_input_translatable('cfg_ary[translations][mail_bodyClosing]', $cfg->translations['mail_bodyClosing'], __('Signature'), 'help_mailToUser') ?>
                            <div id="help_mailToUser" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The message will look like this:<br><br><strong>From mail: library@my-university.net<br>From name: Library Name<br>Subject: Your saved articles from JournalTouch</strong><br><br>Your message was: xxx<br><br>Here are your articles, enjoy!<br><br>Best regards, your library team!') ?></span></div>
                    </fieldset>
                    <fieldset>
                        <legend><?php echo __('Mailing: From User (Orders)') ?></legend>
                            <label for="cfg[mail][toAddress]"><?php echo __('To (your receiving address)') ?></label>
                                <input type="text" name="cfg[mail][toAddress]" value="<?php echo $cfg->mail->toAddress ?>" aria-describedby="help_toAddress" />
                                <div id="help_toAddress" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('To which mail shalls orders be sent?') ?></span></div>
                            <?php echo frm_input_translatable('cfg_ary[translations][mail_subjectToLib]', $cfg->translations['mail_subjectToLib'], __('Order subject'), 'mailToLib') ?>
                            <?php echo frm_input_translatable('cfg_ary[translations][mail_bodyOrder]', $cfg->translations['mail_bodyOrder'], __('Order message'), 'mailToLib') ?>
                            <div id="mailToLib" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The message will look like this:<br><br><strong>From mail: some.user@somewhere.net<br>Subject: New order for the library from JournalTouch</strong><br><br>New order from JournalTouch <i>Article list</i>') ?></span></div>
                    </fieldset>
                    <fieldset>
                        <legend><?php echo __('Allowed users from database') ?></legend>
                            <input type="checkbox" name="cfg[dbusers][userlist]" <?php echo frm_checked($cfg->dbusers->userlist) ?> aria-describedby="help_userlist" />
                                <label for="cfg[dbusers][userlist]"><?php echo __('Enable user list from database') ?></label>
                                <div id="help_userlist" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('On mailing users can select their name from a list (instead of supplying a mail). This is something very special for MPI purposes and you should leave it disabled (unless you happen to work there ;)).') ?></span></div>
                            <label for="cfg[dbusers][dbuser]"><?php echo __('DB User') ?></label>
                                <input type="text" name="cfg[dbusers][dbuser]" value="<?php echo $cfg->dbusers->dbuser ?>" aria-describedby="help_dbuser" />
                                <div id="help_dbuser" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('User name with access to database.') ?></span></div>
                            <label for="cfg[dbusers][dbpass]"><?php echo __('DB Password') ?></label>
                                <input type="text" name="cfg[dbusers][dbpass]" value="<?php echo $cfg->dbusers->dbpass ?>" aria-describedby="help_dbpass" />
                                <div id="help_dbpass" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('The password...') ?></span></div>
                    </fieldset>
            </div>
            <div class="content" id="formTab9">
                <h3>Saving Path Settings</h3>
                <fieldset>
                    <legend>Speicherpfade</legend>
                    <div class="panel"><?php echo __('Usually everything concerning you JournalTouch settings is saved into /data. This folder must be writable by the webserver. If you really need to, you can change some of the paths here. Yet, why would you want that?') ?></div>
                    <label for="cfg[sys][data_cache_usr]"><?php echo __('Cache') ?></label>
                        <input type="text" name="cfg[sys][data_cache_usr]" value="<?php echo $cfg->sys->data_cache_usr  ?>" placeholder="<?php echo $cfg->sys->data_cache ?>" aria-describedby="help_data_cache_usr" />
                        <div id="help_data_cache_usr" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Here are fetched tocs cached. See menu API.') ?></span></div>
                    <label for="cfg[sys][data_covers_usr]"><?php echo __('Covers') ?></label>
                        <input type="text" name="cfg[sys][data_covers_usr]" value="<?php echo $cfg->sys->data_covers_usr  ?>" placeholder="<?php echo $cfg->sys->data_covers ?>" aria-describedby="help_data_covers_usr" />
                        <div id="help_data_covers_usr" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Here you put your own journal covers. Automatically downloaded covers are put into the "api" subfolder.') ?></span></div>
                    <label for="cfg[sys][data_export_usr]"><?php echo __('Export') ?></label>
                        <input type="text" name="cfg[sys][data_export_usr]" value="<?php echo $cfg->sys->data_export_usr  ?>" placeholder="<?php echo $cfg->sys->data_export ?>" aria-describedby="help_data_export_usr" />
                        <div id="help_data_export_usr" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Here are exports from the basket cached.') ?></span></div>
                    <label for="cfg[sys][data_journals_usr]"><?php echo __('Journal list') ?></label>
                        <input type="text" name="cfg[sys][data_journals_usr]" value="<?php echo $cfg->sys->data_journals_usr  ?>" placeholder="<?php echo $cfg->sys->data_journals ?>" aria-describedby="help_data_journals_usr" />
                        <div id="help_data_journals_usr" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Here remains the journals.csv - all you holdings in one file, nicely updated by JournalTouch via JournalTocs and CrossRef.') ?></span></div>
                </fieldset>
            </div>
            <div class="content" id="formTab10">
                <h3><?php echo __('Journals.csv Columns') ?></h3>
                <fieldset>
                    <legend><?php echo __('Table Columns') ?></legend>
                    <div class="panel"><?php echo __('This is only a reference to the columns in journals.csv, since they are not in the file itself.') ?></div>
                    <div class="row">
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][title]" aria-describedby="help_csv_col_0"><?php echo __('Titel') ?> (<?php echo __('Optional') ?>)</label>
                                <input type="text" name="dummy[csv_col][title]" value="<?php echo $cfg->csv_col->title+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][title]" value="<?php echo $cfg->csv_col->title  ?>" />
                                <div id="help_csv_col_0" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional (recommended). The title of the journal.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][filter]" aria-describedby="help_csv_col_1"><?php echo __('Filter') ?> (<?php echo __('Optional') ?>)</label>
                                <input type="text" name="dummy[csv_col][filter]" value="<?php echo $cfg->csv_col->filter+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][filter]" value="<?php echo $cfg->csv_col->filter  ?>"/>
                                <div id="help_csv_col_1" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional. Used for the filter menu, to filter journals by category. See config menu "Filter".') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][col2]" aria-describedby="help_csv_col_2"><?php echo __('Col2') ?> (<?php echo __('Ignore') ?>)</label>
                                <input type="text" name="dummy[csv_col][col2]" value="<?php echo $cfg->csv_col->col2+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][col2]" value="<?php echo $cfg->csv_col->col2  ?>"/>
                                <div id="help_csv_col_2" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Ignore. This column exists for historical reasons, but is not used for anything by JournalTouch.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][important]" aria-describedby="help_csv_col_3"><?php echo __('Favorites') ?> (<?php echo __('Optional') ?>)</label>
                                <input type="text" name="dummy[csv_col][important]" value="<?php echo $cfg->csv_col->important+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][important]" value="<?php echo $cfg->csv_col->important  ?>"/>
                                <div id="help_csv_col_3" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional. These journals are filtered with the "special" filter in filter menu, if column isn\'t empty (anything is ok).') ?></span></div>
                        </div>
                    </div>
                    <!-- End row 1, Start row 2 -->
                    <div class="row">
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][col4]" aria-describedby="help_csv_col_4"><?php echo __('Col4') ?> (<?php echo __('Ignore') ?>)</label>
                                <input type="text" name="dummy[csv_col][col4]" value="<?php echo $cfg->csv_col->col4+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][col4]" value="<?php echo $cfg->csv_col->col4  ?>"/>
                                <div id="help_csv_col_4" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Ignore. This column exists for historical reasons, but is not used for anything by JournalTouch.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][p_issn]" aria-describedby="help_csv_col_5"><?php echo __('P-Issn') ?> (<?php echo __('Required') ?>)</label>
                                <input type="text" name="dummy[csv_col][p_issn]" value="<?php echo $cfg->csv_col->p_issn+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][p_issn]" value="<?php echo $cfg->csv_col->p_issn  ?>"/>
                                <div id="help_csv_col_5" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Required. Without issn\'s we have nothing to work with :)') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][e_issn]" aria-describedby="help_csv_col_6"><?php echo __('E-Issn') ?> (<?php echo __('Optional') ?>)</label>
                                <input type="text" name="dummy[csv_col][e_issn]" value="<?php echo $cfg->csv_col->e_issn+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][e_issn]" value="<?php echo $cfg->csv_col->e_issn  ?>"/>
                                <div id="help_csv_col_6" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional. An e-issn to try if issn fails to get toc.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][publisher]" aria-describedby="help_csv_col_7"><?php echo __('Publisher') ?> (<?php echo __('Optional/Auto') ?>)</label>
                                <input type="text" name="dummy[csv_col][publisher]" value="<?php echo $cfg->csv_col->publisher+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][publisher]" value="<?php echo $cfg->csv_col->publisher  ?>"/>
                                <div id="help_csv_col_7" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional/Auto. Publisher. Used for cover download (currently: "De Gruyter", "Elsevier", "Sage" and "Springer-Verlag" will work). Publishers are automatically fetched if you used "fetch metadata".') ?></span></div>
                        </div>
                    </div>
                    <!-- End row 2, Start row 3 -->
                    <div class="row">
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][new]" aria-describedby="help_csv_col_8"><?php echo __('New Marker') ?> (<?php echo __('Optional/Auto') ?>)</label>
                                <input type="text" name="dummy[csv_col][new]" value="<?php echo $cfg->csv_col->new+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][new]" value="<?php echo $cfg->csv_col->new  ?>"/>
                                <div id="help_csv_col_8" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional/Auto. Marks journal (issue) as new if not empty. This is done automatically, but you might mark a journal as new by hand (e.g. if no toc is found).') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][date]" aria-describedby="help_csv_col_9"><?php echo __('Issue date') ?> (<?php echo __('Optional/Auto') ?>)</label>
                                <input type="text" name="dummy[csv_col][date]" value="<?php echo $cfg->csv_col->date+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][date]" value="<?php echo $cfg->csv_col->date  ?>"/>
                                <div id="help_csv_col_9" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional/Auto. Date of last issue.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][lastIssue]" aria-describedby="help_csv_col_10"><?php echo __('Issue numbering') ?> (<?php echo __('Optional/Auto') ?>)</label>
                                <input type="text" name="dummy[csv_col][lastIssue]" value="<?php echo $cfg->csv_col->lastIssue+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][lastIssue]" value="<?php echo $cfg->csv_col->lastIssue  ?>"/>
                                <div id="help_csv_col_10" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional/Auto. Format "year/vol/issue". Used for Crossref check.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][metaPrint]" aria-describedby="help_csv_col_11"><?php echo __('Print holding') ?> (<?php echo __('Optional') ?>)</label>
                                <input type="text" name="dummy[csv_col][metaPrint]" value="<?php echo $cfg->csv_col->metaPrint+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][metaPrint]" value="<?php echo $cfg->csv_col->metaPrint  ?>"/>
                                <div id="help_csv_col_11" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional. Are you subscribed for the print edition of this journal? Just enter anything (e.g. "p"). It\'s used in combination with the shelfmark for the meta info button.') ?></span></div>
                        </div>
                    </div>
                    <!-- End row 3, Start row 4 -->
                    <div class="row">
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][metaOnline]" aria-describedby="help_csv_col_12"><?php echo __('Online holding') ?> (<?php echo __('Optional') ?>)</label>
                                <input type="text" name="dummy[csv_col][metaOnline]" value="<?php echo $cfg->csv_col->metaOnline+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][metaOnline]" value="<?php echo $cfg->csv_col->metaOnline  ?>"/>
                                <div id="help_csv_col_12" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional. Are you subscribed for the online edition of the journal? This information is used to decide if a direct download button should be shown; see Your Institution" setting.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][metaGotToc]" aria-describedby="help_csv_col_13"><?php echo __('Toc available') ?> (<?php echo __('Optional/Auto') ?>)</label>
                                <input type="text" name="dummy[csv_col][metaGotToc]" value="<?php echo $cfg->csv_col->metaGotToc+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][metaGotToc]" value="<?php echo $cfg->csv_col->metaGotToc  ?>"/>
                                <div id="help_csv_col_13" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional/Auto. You might want to show journals you are subscribed to, even though no toc can be fetched. Usually this is automatically set on update. If for some reason toc fetching does not work, you might clear this field in the table. The info is shown as meta info button.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][metaShelfmark]" aria-describedby="help_csv_col_14"><?php echo __('Shelfmark') ?> (<?php echo __('Optional') ?>)</label>
                                <input type="text" name="dummy[csv_col][metaShelfmark]" value="<?php echo $cfg->csv_col->metaShelfmark+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][metaShelfmark]" value="<?php echo $cfg->csv_col->metaShelfmark  ?>"/>
                                <div id="help_csv_col_14" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional. If you got the print edition (too) you might provide the shelfmark here. The info is shown as meta info button.') ?></span></div>
                        </div>
                        <div class="large-3 columns">
                            <label for="dummy[csv_col][metaWebsite]" aria-describedby="help_csv_col_15"><?php echo __('Journal\'s Homepage') ?> (<?php echo __('Optional/Auto') ?>)</label>
                                <input type="text" name="dummy[csv_col][metaWebsite]" value="<?php echo $cfg->csv_col->metaWebsite+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][metaWebsite]" value="<?php echo $cfg->csv_col->metaWebsite  ?>"/>
                                <div id="help_csv_col_15" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional/Auto. The journal\'s website. Automatically fetched if you used "fetch metadata" (and information was available).') ?></span></div>
                        </div>
                    </div>
                    <!-- End row 4, Start row 5 -->
                    <div class="row">
                        <div class="large-3 columns end">
                            <label for="dummy[csv_col][tags]" aria-describedby="help_csv_col_16"><?php echo __('Tags') ?> (<?php echo __('Optional/Auto') ?>)</label>
                                <input type="text" name="dummy[csv_col][tags]" value="<?php echo $cfg->csv_col->tags+1  ?>" disabled="disabled" />
                                <input type="hidden" name="cfg[csv_col][tags]" value="<?php echo $cfg->csv_col->tags  ?>"/>
                                <div id="help_csv_col_16" class="tooltip" role="tooltip" aria-hidden="true"><span><?php echo __('Optional/Auto. Got some subject indexing? Separate tags with commas :)') ?></span></div>
                        </div>
                    </div>
                    <!-- End row 5 -->
                </fieldset>
            </div>
        <!-- end Tabs -->
        </div>
    </div>
    <!-- Main structure 3: Info column -->
    <div class="small-2 columns">
        <div id="dataUnsaved" data-alert class="alert-box warning radius hidden">
            <?php echo __('Beware, you have unsaved settings') ?>
            <!-- <a href="#" class="close">&times;</a> -->
        </div>

        <div id="dataSaved" data-alert class="alert-box success radius hidden">
            <?php echo __('Configuration successfully saved!') ?>
            <!-- <a href="#" class="close">&times;</a> -->
        </div>

        <div id="help"></div>
    </div>
    </form>
</div>
<pre>
<?php
/*
// Save form
//cfg_save();
echo '<pre>';
var_export($cfg);
echo '</pre>';
echo '<h2>Trying to access as used</h2>';
echo $cfg->prefs->languages[0];
echo $cfg->prefs->lib_name->en_US;
*/
?>
</body>
</html>