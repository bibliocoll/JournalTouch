<?php
//always load the config-default.php, so nothing is ever missed; povides $cfg
require('../data/config/config-default.php');
// load user cfg
$cfg = cfg_load();

// Start Ajax Save
if (is_ajax()) {
   if (isset($_GET['cfg']) && !empty($_GET['cfg'])) {
        cfg_save();
    	$return['response'] = json_encode('Alles gut');
        echo json_encode($return);
        exit;
    }
}

//Function to check if the request is an AJAX request
function is_ajax() {
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}
// END Ajax Save

// Save config variables as Subobject of cfg (as done until ver. 0.3
// Prevent a complete rewrite
function cfg_save($user_cfg = '../data/config/user_config.php') {
    global $cfg;

    $status = false;
    if (isset($_GET['cfg'])) {
        $save = $_GET['cfg'];
        $config = new stdClass();
        $config = json_encode($save);

        // form data comes as strings. Make it true boolean
        // Numbers stay strings. Well, it's not that php does care
        $config = str_replace('"true"', 'true', $config);
        $config = str_replace('"false"', 'false', $config);
        $config = json_decode($config, FALSE);

        // Overwrite config-default.php with values from form
        $config = (object) array_merge((array) $cfg, (array) $config);

        $save = serialize($config);
        $status = file_put_contents($user_cfg, $save);
    }

    return $status;
}

// Load on demand; set the result always to $cfg = cfg_load();
function cfg_load($user_cfg = '../data/config/user_config.php') {
    global $cfg;

    if (file_exists($user_cfg)) {
        $restore    = file_get_contents($user_cfg);
        $cfg_saved  = unserialize($restore);
        // Overwrite config-default.php with loaded user values
        $cfg = (object) array_merge((array) $cfg, (array) $cfg_saved);
    }

    return $cfg;
}

// Just to make it short withing the html for checkboxes
function frm_checked($value) {
    $status = '';
    if ($value) {
        $status = 'checked="checked"';
    }
    return $status;
}

// Just to make it short withing the html for multiselect
function frm_selected($value) {
    $status = '';
    if ($value) {
        $status = 'selected="selected"';
    }
    return $status;
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
        <script src="../js/vendor/jquery.serialize_checkbox.js"></script>
        <script src="../js/foundation.min.js"></script>


        <script type="text/javascript">
            // Everything is ready to go
            $(document).ready(function(){
                // Foundations: Tabs - Hmm, otherwise foundation is not loaded...
                $(document).foundation();
                // Foundations: Tabs - Hmm, bit buggy? Set via jquery
                $(".tabs-content").css("margin-left", "220px");

                // Form submit button is clicked
                $( "form" ).on( "submit", function( event ) {
                    event.preventDefault();

                    // Serialize form data; set unset checkboxes to false
                    var formData = $(this).serialize({ checkboxesAsBools: true });

                    // Testing
                    console.log( formData );
                    //$.get('settings.php', postData);

                    // Send ajax request to this file (yeah, I know...)
                    $.ajax({
                        type: 'GET',
                        url: 'settings.php',
                        data: formData,
                        dataType:'json',
//                         beforeSend:function(xhr, settings){
//                         settings.data += '&moreinfo=MoreData';
//                         },
                        success:function(data){
                            alert("Success! " + data['response']);
                        },
                        error: function(data) {
                            alert("Failure!");
                        }
                    });
                });
            });
        </script>

        <style type="text/css" media="screen">
            .tabs.vertical  {width: 200px;}
            .content        {width: 600px !important; padding: 0px !important;}
            label           {margin-right: 10px;}
        </style>
    </head>
<body>
    <?php include('menu.inc') ?>
    <h2>JournalTouch Settings for Admins</h2>
    <!--  action="settings.php" method="get" -->
    <form>
    <!-- define Tabs -->
    <ul class="tabs vertical" data-tab="">
      <li class="tab-title"><button type="submit" style="width: 100%" name="save">Save</button></li>
      <li class="tab-title active"><a href="#formTab1">Institution</a></li>
      <li class="tab-title"><a href="#formTab2">Page settings</a></li>
      <li class="tab-title"><a href="#formTab3">API</a></li>
      <li class="tab-title"><a href="#formTab4">Mailing</a></li>
      <li class="tab-title"><a href="#formTab5">Filter</a></li>
      <li class="tab-title"><a href="#formTab6">Covers</a></li>
      <li class="tab-title"><a href="#formTab7">User-DB</a></li>
      <li class="tab-title"><a href="#formTab8">Paths</a></li>
      <li class="tab-title"><a href="#formTab10">Journals</a></li>
      <li class="tab-title"><button type="submit" style="width: 100%" name="save">Save</button></li>
    </ul>
        <!-- start Tabs -->
        <div class="tabs-content">
        <div class="content active" id="formTab1">
            <h3>Institution Settings</h3>
            <fieldset>
                <legend>Ihre Einrichtung</legend>
                    <label for="cfg[prefs][lib_name]">Einrichtungsname</label>
                        <input type="text" name="cfg[prefs][lib_name]" value="<?php echo $cfg->prefs->lib_name ?>"  aria-describedby="nameHelpText" />
                        <p id="nameHelpText">Enter your name.</p>
                    <label for="cfg[prefs][lib_initials]">Initialien</label>
                        <input type="text" name="cfg[prefs][lib_initials]" value="<?php echo $cfg->prefs->lib_initials ?>" />
            </fieldset>
            <fieldset>
                <legend>Einrichtung: Discover und Access</legend>
                    <label for="cfg[prefs][ip_subnet]">IP Subnetz</label>
                        <input type="text" name="cfg[prefs][ip_subnet]" value="<?php echo $cfg->prefs->ip_subnet ?>" />
                    <label for="cfg[prefs][show_dl_button]" class="left">Download-Button?</label>
                        <input type="checkbox" name="cfg[prefs][show_dl_button]" <?php echo frm_checked($cfg->prefs->show_dl_button) ?>><br />
                    <label for="cfg[prefs][inst_service]">Spezialdienst</label>
                        <input type="text" name="cfg[prefs][inst_service]" value="<?php echo $cfg->prefs->inst_service ?>" />
                    <label for="cfg[prefs][proxy]">Proxy für Endnutzer</label>
                        <input type="text" name="cfg[prefs][proxy]" value="<?php echo $cfg->prefs->proxy ?>" />
                    <label for="cfg[prefs][sfx]">SFX-Service</label>
                        <input type="text" name="cfg[prefs][sfx]" value="<?php echo $cfg->prefs->sfx ?>" />
            </fieldset>
        </div>
        <div class="content" id="formTab2">
            <h3>Page Settings</h3>
            <fieldset>
                <legend>Vorhandene und aktivierte Übersetzungen</legend>
                <select name="cfg[prefs][languages][]" size="3" multiple>
                  <option value="de_DE" <?php echo frm_selected($cfg->prefs->languages[0]) ?>>Deutsch</option>
                  <option value="en_US" <?php echo frm_selected($cfg->prefs->languages[1]) ?>>Englisch</option>
                </select>
            </fieldset>
            <fieldset>
                <legend>Seiteneinstellungen: Menüs</legend>
                    <label for="cfg[prefs][menu_show_listview]" class="left">Zeige Menüpunkt Liste</label>
                        <input type="checkbox" name="cfg[prefs][menu_show_listview]" <?php echo frm_checked($cfg->prefs->menu_show_listview) ?>><br />
                    <label for="cfg[prefs][menu_show_sort]" class="left">Zeige Menüpunkt Sortierung (Datum/Alphabetisch)</label>
                        <input type="checkbox" name="cfg[prefs][menu_show_sort]" <?php echo frm_checked($cfg->prefs->menu_show_sort) ?>><br />
                    <label for="cfg[prefs][menu_show_tagcloud]" class="left">Zeige Menüpunkt Tags</label>
                        <input type="checkbox" name="cfg[prefs][menu_show_tagcloud]" <?php echo frm_checked($cfg->prefs->menu_show_tagcloud) ?>><br />
                    <label for="cfg[prefs][min_tag_freq]">Min. Taghäufigkeit für Anzeige in Tags</label>
                        <input type="text" name="cfg[prefs][min_tag_freq]" value="<?php echo $cfg->prefs->min_tag_freq ?>" />
            </fieldset>
            <fieldset>
                <legend>Seiteneinstellungen: Ansicht</legend>
                    <label for="cfg[prefs][default_sort_date]" class="left">Sotierungsstandard Datum?</label>
                        <input type="checkbox" name="cfg[prefs][default_sort_date]" <?php echo frm_checked($cfg->prefs->default_sort_date) ?>><br />
                    <label for="cfg[prefs][show_metainfo_list]" class="left">Metainfo in Listenansicht?</label>
                        <input type="checkbox" name="cfg[prefs][show_metainfo_list]" <?php echo frm_checked($cfg->prefs->show_metainfo_list) ?>><br />
                    <label for="cfg[prefs][show_metainfo_toc]" class="left">Metainfo über Toc?</label>
                        <input type="checkbox" name="cfg[prefs][show_metainfo_toc]" <?php echo frm_checked($cfg->prefs->show_metainfo_toc) ?>><br />
                    <label for="cfg[prefs][show_screensaver]" class="left">Screensaver aktivieren</label>
                        <input type="checkbox" name="cfg[prefs][show_screensaver]" <?php echo frm_checked($cfg->prefs->show_screensaver) ?>><br />
                    <label for="cfg[prefs][show_orbit]" class="left">Orbit aktivieren</label>
                        <input type="checkbox" name="cfg[prefs][show_orbit]" <?php echo frm_checked($cfg->prefs->show_orbit) ?>>
            </fieldset>
        </div>
        <div class="content" id="formTab3">
            <h3>API Settings</h3>
            <fieldset>
                <legend>Performance</legend>
                    <label for="cfg[prefs][cache_toc_enable]" class="left">Tocs cachen?</label>
                        <input type="checkbox" name="cfg[prefs][cache_toc_enable]" <?php echo frm_checked($cfg->prefs->cache_toc_enable) ?>><br />
                    <label for="cfg[prefs][cache_main_enable]" class="left">Hauptseite cachen?</label>
                        <input type="checkbox" name="cfg[prefs][cache_main_enable]" <?php echo frm_checked($cfg->prefs->cache_main_enable) ?>>
            </fieldset>
            <fieldset>
                <legend>Api: Allgemein</legend>
                    <label for="cfg[api][all][articleLink]" class="left">Artikel in Toc verlinken?</label>
                        <input type="checkbox" name="cfg[api][all][articleLink]" <?php echo frm_checked($cfg->api->all->articleLink) ?>><br />
                    <label for="cfg[api][all][is_new_days]">Wieviel Tage gilt Ausgabe als neu?</label>
                        <input type="text" name="cfg[api][all][is_new_days]" value="<?php echo $cfg->api->all->is_new_days ?>" />
            </fieldset>
            <fieldset>
                <legend>Api: JournalTocs</legend>
                    <label for="cfg[api][jt][account]">Konto (Mail)</label>
                        <input type="text" name="cfg[api][jt][account]" value="<?php echo $cfg->api->jt->account ?>" />
                    <label for="cfg[api][jt][premium]" class="left">Premiumkonto</label>
                        <input type="checkbox" name="cfg[api][jt][premium]" <?php echo frm_checked($cfg->api->jt->premium) ?>><br />
                    <label for="cfg[api][jt][upd_show]" class="left">Nutze Premium-Infos auf Startseite (langsam)</label>
                        <input type="checkbox" name="cfg[api][jt][upd_show]" <?php echo frm_checked($cfg->api->jt->upd_show) ?>><br />
                    <label for="cfg[api][jt][updates]">Premium-URL</label>
                        <input type="text" name="cfg[api][jt][updates]" value="<?php echo $cfg->api->jt->updates ?>" />
            </fieldset>
        </div>
        <div class="content" id="formTab4">
            <h3>Mailing Settings</h3>
            <fieldset>
                <legend>Mailing</legend>
                    <fieldset>
                        <legend>Mailing: Allgemein</legend>
                            <label for="cfg[mail][domain]">Domain</label>
                                <input type="text" name="cfg[mail][domain]" value="<?php echo $cfg->mail->domain ?>" />
                            <label for="cfg[mail][subjectFB]">Feedback Button</label>
                                <input type="text" name="cfg[mail][subjectFB]" value="<?php echo $cfg->mail->subjectFB ?>" />
                    </fieldset>
                    <fieldset>
                        <legend>Mailing: Zu Nutzer</legend>
                            <label for="cfg[mail][fromAddress]">Absenderadresse</label>
                                <input type="text" name="cfg[mail][fromAddress]" value="<?php echo $cfg->mail->fromAddress ?>" />
                            <label for="cfg[mail][fromName]">Absendername</label>
                                <input type="text" name="cfg[mail][fromName]" value="<?php echo $cfg->mail->fromName ?>" />
                            <label for="cfg[mail][subjectToUser]">Betreff</label>
                                <input type="text" name="cfg[mail][subjectToUser]" value="<?php echo $cfg->mail->subjectToUser ?>" />
                            <label for="cfg[mail][bodyMessage]">Nachricht (Inhalt)</label>
                                <input type="text" name="cfg[mail][bodyMessage]" value="<?php echo $cfg->mail->bodyMessage ?>" />
                            <label for="cfg[mail][bodySalutation]">Nachricht (Gruß)</label>
                                <input type="text" name="cfg[mail][bodySalutation]" value="<?php echo $cfg->mail->bodySalutation ?>" />
                            <label for="cfg[mail][bodyClosing]">Nachricht (Name)</label>
                                <input type="text" name="cfg[mail][bodyClosing]" value="<?php echo $cfg->mail->bodyClosing ?>" />
                    </fieldset>
                    <fieldset>
                        <legend>Mailing: Von Nutzer (Bestellung)</legend>
                            <label for="cfg[mail][toAddress]">An: Ihre Empfangsadresse</label>
                                <input type="text" name="cfg[mail][toAddress]" value="<?php echo $cfg->mail->toAddress ?>" />
                            <label for="cfg[mail][subjectToLib]">An: Betreff</label>
                                <input type="text" name="cfg[mail][subjectToLib]" value="<?php echo $cfg->mail->subjectToLib ?>" />
                            <label for="cfg[mail][bodyOrder]">Inhalt</label>
                                <input type="text" name="cfg[mail][bodyOrder]" value="<?php echo $cfg->mail->bodyOrder ?>" />
                    </fieldset>
            </fieldset>
        </div>
        <div class="content" id="formTab5">
            <h3>Filter Settings</h3>
            <fieldset>
    <!--NOTE THIS WON'T WORK THIS WAY - NAMED ARRAY -->
                <legend>Portfolio-Filter</legend>
                    <input type="text" name="cfg_ary[filter_key][]" value="psy" />
                    <input type="text" name="cfg_ary[filter_val][]" value="Psychology" /><br />
                    <input type="text" name="cfg_ary[filter_key][]" value="pol" />
                    <input type="text" name="cfg_ary[filter_val][]" value="Politics" />
                    <input type="text" name="cfg_ary[filter_key][]" value="wir" />
                    <input type="text" name="cfg_ary[filter_val][]" value="Yet another filter" />
            </fieldset>
        </div>
        <div class="content" id="formTab6">
            <h3>Cover Settings</h3>
            <fieldset>
                <legend>Covers</legend>
                    <label for="cfg[covers][placeholder]">Platzhalter Cover</label>
                        <input type="text" name="cfg[covers][placeholder]" value="<?php echo $cfg->covers->placeholder ?>" />
                    <label for="cfg[covers][placeholder]">Cover Api</label>
                        <input type="text" name="cfg[covers][api]" value="<?php echo $cfg->covers->api ?>" />
    <!--NOTE THIS WON'T WORK THIS WAY - NAMED ARRAY ... enter
    $cfg->covers->src_genric  = array('STMcovers'           => 0,
                                    'JournalTocs'           => 0,
                                    'Lehmanns'              => 0,
                                    'SubscribeToJournals'   => 0);
    // For these publishers JournalTouch provides a way for direct cover download
    $cfg->covers->src_publisher = array('DeGruyter' => 0,
                                        'Elsevier'  => 0,
                                        'Sage'      => 0,
                                        'Springer'  => 0);
    -->
            </fieldset>
        </div>
        <div class="content" id="formTab7">
            <h3>User-DB Settings</h3>
            <fieldset>
                <legend>Datenbank-Nutzer</legend>
                    <label for="cfg[dbusers][userlist]" class="left">Benutzerliste via DB?</label>
                        <input type="checkbox" name="cfg[dbusers][userlist]" <?php echo frm_checked($cfg->dbusers->userlist) ?>><br />
                    <label for="cfg[dbusers][dbuser]">DB User</label>
                        <input type="text" name="cfg[dbusers][dbuser]" value="<?php echo $cfg->dbusers->dbuser ?>" />
                    <label for="cfg[dbusers][dbpass]">DB Passwort</label>
                        <input type="text" name="cfg[dbusers][dbpass]" value="<?php echo $cfg->dbusers->dbpass ?>" />
            </fieldset>
        </div>
        <div class="content" id="formTab8">
            <h3>Saving Path Settings</h3>
            <fieldset>
                <legend>Speicherpfade</legend>
                    <label for="cfg[sys][data_cache]">Cache</label>
                        <input type="text" name="cfg[sys][data_cache]" value="<?php echo $cfg->sys->data_cache  ?>" />
                    <label for="cfg[sys][data_covers]">Covers</label>
                        <input type="text" name="cfg[sys][data_covers]" value="<?php echo $cfg->sys->data_covers  ?>" />
                    <label for="cfg[sys][data_export]">Export</label>
                        <input type="text" name="cfg[sys][data_export]" value="<?php echo $cfg->sys->data_export  ?>" />
                    <label for="cfg[sys][data_journals]">Journalliste</label>
                        <input type="text" name="cfg[sys][data_journals]" value="<?php echo $cfg->sys->data_journals  ?>" />
            </fieldset>
        </div>
        <div class="content" id="formTab9">
            <h3>Journals Table Fields</h3>
            <fieldset>
                <legend>Tabellenspalten (FYI)</legend>
                    <label for="dummy[csv_col][title]">Titel (Optional)</label>
                        <input type="text" name="dummy[csv_col][title]" value="<?php echo $cfg->csv_col->title  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][title]" value="<?php echo $cfg->csv_col->title  ?>"/>
                    <label for="dummy[csv_col][filter]">Filter (siehe Portfolio oben; Optional))</label>
                        <input type="text" name="dummy[csv_col][filter]" value="<?php echo $cfg->csv_col->filter  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][filter]" value="<?php echo $cfg->csv_col->filter  ?>"/>
                    <label for="dummy[csv_col][col2]">Col2 (ignorieren)</label>
                        <input type="text" name="dummy[csv_col][col2]" value="<?php echo $cfg->csv_col->col2  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][col2]" value="<?php echo $cfg->csv_col->col2  ?>"/>
                    <label for="dummy[csv_col][important]">Favoriten (Optional)</label>
                        <input type="text" name="dummy[csv_col][important]" value="<?php echo $cfg->csv_col->important  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][important]" value="<?php echo $cfg->csv_col->important  ?>"/>
                    <label for="dummy[csv_col][col4]">Col4 (ignorieren)</label>
                        <input type="text" name="dummy[csv_col][col4]" value="<?php echo $cfg->csv_col->col4  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][col4]" value="<?php echo $cfg->csv_col->col4  ?>"/>
                    <label for="dummy[csv_col][p_issn]">P-Issn (Pflicht)</label>
                        <input type="text" name="dummy[csv_col][p_issn]" value="<?php echo $cfg->csv_col->p_issn  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][p_issn]" value="<?php echo $cfg->csv_col->p_issn  ?>"/>
                    <label for="dummy[csv_col][e_issn]">E-Issn (Optional)</label>
                        <input type="text" name="dummy[csv_col][e_issn]" value="<?php echo $cfg->csv_col->e_issn  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][e_issn]" value="<?php echo $cfg->csv_col->e_issn  ?>"/>
                    <label for="dummy[csv_col][publisher]">Verlag (Optional/Auto)</label>
                        <input type="text" name="dummy[csv_col][publisher]" value="<?php echo $cfg->csv_col->publisher  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][publisher]" value="<?php echo $cfg->csv_col->publisher  ?>"/>
                    <label for="dummy[csv_col][new]">Neu-Marker (Optional/Auto)</label>
                        <input type="text" name="dummy[csv_col][new]" value="<?php echo $cfg->csv_col->new  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][new]" value="<?php echo $cfg->csv_col->new  ?>"/>
                    <label for="dummy[csv_col][date]">Heftdatum (Optional/Auto)</label>
                        <input type="text" name="dummy[csv_col][date]" value="<?php echo $cfg->csv_col->date  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][date]" value="<?php echo $cfg->csv_col->date  ?>"/>
                    <label for="dummy[csv_col][lastIssue]">Heftzählung (Optional/Auto)</label>
                        <input type="text" name="dummy[csv_col][lastIssue]" value="<?php echo $cfg->csv_col->lastIssue  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][lastIssue]" value="<?php echo $cfg->csv_col->lastIssue  ?>"/>
                    <label for="dummy[csv_col][metaPrint]">Printbestand? (Optional)</label>
                        <input type="text" name="dummy[csv_col][metaPrint]" value="<?php echo $cfg->csv_col->metaPrint  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][metaPrint]" value="<?php echo $cfg->csv_col->metaPrint  ?>"/>
                    <label for="dummy[csv_col][metaOnline]">Onlinebestand? (Optional)</label>
                        <input type="text" name="dummy[csv_col][metaOnline]" value="<?php echo $cfg->csv_col->metaOnline  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][metaOnline]" value="<?php echo $cfg->csv_col->metaOnline  ?>"/>
                    <label for="dummy[csv_col][metaGotToc]">TOC verfügbar? (Optional/Auto)</label>
                        <input type="text" name="dummy[csv_col][metaGotToc]" value="<?php echo $cfg->csv_col->metaGotToc  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][metaGotToc]" value="<?php echo $cfg->csv_col->metaGotToc  ?>"/>
                    <label for="dummy[csv_col][metaShelfmark]">Regalsignatur? (Optional)</label>
                        <input type="text" name="dummy[csv_col][metaShelfmark]" value="<?php echo $cfg->csv_col->metaShelfmark  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][metaShelfmark]" value="<?php echo $cfg->csv_col->metaShelfmark  ?>"/>
                    <label for="dummy[csv_col][metaWebsite]">Journal-Website (Optional/Auto)</label>
                        <input type="text" name="dummy[csv_col][metaWebsite]" value="<?php echo $cfg->csv_col->metaWebsite  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][metaWebsite]" value="<?php echo $cfg->csv_col->metaWebsite  ?>"/>
                    <label for="dummy[csv_col][tags]">Tags (Optional/Auto)</label>
                        <input type="text" name="dummy[csv_col][tags]" value="<?php echo $cfg->csv_col->tags  ?>" disabled="disabled"/>
                        <input type="hidden" name="cfg[csv_col][tags]" value="<?php echo $cfg->csv_col->tags  ?>"/>
            </fieldset>
        <!-- end Tabs -->
        </div>
        </div>
    </form>
<pre>
<?php
// Save form
//cfg_save();
echo '<h2>Trying to access as used</h2>';
echo $cfg->prefs->languages[0];
echo $cfg->prefs->lib_name;
?>
</body>
</html>