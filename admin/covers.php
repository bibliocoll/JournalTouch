<?php
/**
 * @brief   Manage covers
 *
 * Introduced: 2015-11-21
 *
 * @todo
 * - add option to create dummy in manual folder to prevent api downloads
 * - bulk actions?
 *
 * @author Tobias Zeumer <tzeumer@verweisungsform.de>
 */
require('../sys/bootstrap.php');

// Start Ajax Save
if (is_ajax()) {
//var data = {action: 'del', file: del};
   if (isset($_GET['action'])) {
        $return["status"]   = 'fail';
        $return["message"]  = 'Unknown action '.$_GET['action'];

        // Action is delete
        if ($_GET['action'] == 'del') {
            $file = $_GET['file'];
            $status = unlink($file);

            $return["status"]   = ($status) ? 'success' : 'fail';
            $return["message"]  = ($status) ? 'Image was deleted' : 'Failed deleting image';
        }

        // Action is move
        if ($_GET['action'] == 'move') {
            $file = $_GET['file'];

            $path       = ($cfg->sys->data_covers == 'data/covers/') ? $cfg->sys->basepath.$cfg->sys->data_covers : $cfg->sys->data_covers;
            $path_old   = $path.'api/'.$file;
            $path_new   = $path.$file;

            $status = rename($path_old, $path_new);

            $return["status"]   = ($status) ? 'success' : 'fail';
            $return["message"]  = ($status) ? 'Image was moved to manual folder' : 'Moving image failed';
            $return["source"]   = ($status) ? '../'.$cfg->sys->data_covers.$file : '';
        }

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



// Paths and extensions used
$extensions         = array('jpg', 'gif', 'png');
$path_covers_manual = $cfg->sys->basepath.'data/covers/';
$path_covers_api    = $cfg->sys->basepath.'data/covers/api/';

// Url params
$max_show = 100;
$entry_start = (isset($_GET['start'])) ? $_GET['start'] : 1;

// Get available journals images (manual and api)
// Merge everthing in a big array with issn as key
/*
    [1572-9125] => Array(
        [journal] => BIT. Numerical mathematics
        [manual] => Array([jpg] => C:\ProgrammePortable\!_NoSync\Seafile\Filesafe\Programme\UniServerZ\www\_gitRep\JournalTouch\sys/../data/covers/1572-9125.jpg)
        [api] => Array([jpg] => C:\ProgrammePortable\!_NoSync\Seafile\Filesafe\Programme\UniServerZ\www\_gitRep\JournalTouch\sys/../data/covers/api/1572-9125.jpg)
    )
*/
$journals = get_issn_csv();
$covers_m = get_dir_images($path_covers_manual, 'manual');
$covers_a = get_dir_images($path_covers_api, 'api');
$covers = array_merge_recursive($journals, $covers_m, $covers_a);


/**
 * @brief   Get covers from dir
 *
 * @return Array like x[filename][type][extension] = full_path;
 */
function get_dir_images($path, $type) {
    global $extensions;
    $images     = array();

    // Get images by extension
    foreach ($extensions as $ext) {
        $images_dir = glob($path."*.$ext");
        // Strip path and extension
        foreach ($images_dir as $img_path) {
            $name = str_replace($path, '', $img_path);
            $name = str_replace(".$ext", '', $name);
            // flip key and name
            $images[$name][$type][$ext]   = $img_path;
            $images[$name][$type]['size'] = round(filesize($img_path) / 1024, 2);
        }
    }

    return $images;
}


/**
 * @brief   Get journals issn and title
 *
 * nearly same as get_dir_images()
 *
 * @return Array like x[issn]['journal'] = title;
 */
function get_issn_csv() {
    global $cfg;
    $journals = array();

    if (($handle = fopen($cfg->csv_file->path, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, $cfg->csv_file->separator)) !== FALSE) {
            $myISSN = '';
            if (valid_issn($data[$cfg->csv_col->p_issn], TRUE) === TRUE) {
                $myISSN = $data[$cfg->csv_col->p_issn];
            } elseif (valid_issn($data[$cfg->csv_col->e_issn], TRUE) === TRUE) {
                $myISSN = $data[$cfg->csv_col->e_issn];
            } else {
                //no valid ISSN, lets skip this one
                continue;
            }

            $title = $data[$cfg->csv_col->title];
            $journals[$myISSN]['journal'] = $title;
        }
    }

    return $journals;
}


function create_nav($total, $current, $limit = 100) {
    global $cfg;
    $num_entries = ceil($total / $limit);

    $lang = 'lang='.$cfg->prefs->current_lang;

    $nav = '<ul class="pagination">
                <li class="unavailable"><a href="">'.__('LOAD').'</a></li>';
    for ($entry = 0; $entry < $num_entries; $entry++) {
        $start  = $entry * $limit+1;
        $end    = $start + $limit-1;

        if ($start == $current) {
            $nav .= '<li class="current"><a href="">'.$start.'-'.$end.'</a></li>';
        } else {
            $nav .= '<li><a href="covers.php?'.$lang.'&start='.$start.'">'.$start.'-'.$end.'</a></li>';
        }
    }
    $nav .= '    <!-- <li class="arrow"><a href="">&raquo;</a></li> -->
            </ul>';

    return $nav;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <title><?php echo __('JournalTouch Cover Management') ?></title>
        <link rel="stylesheet" href="../css/foundation.min.css" />
        <link rel="stylesheet" href="../css/foundation-icons/foundation-icons.css" />

        <script src="../js/vendor/jquery.js"></script>
        <script src="../js/vendor/jquery.are-you-sure.js"></script>
        <script src="../js/vendor/jquery.serialize_checkbox.js"></script>
        <script src="../js/vendor/jquery.unveil.min.js"></script>
        <script src="../js/vendor/jquery-ui/jquery-ui.min.js"></script>
        <link rel="stylesheet" href="../js/vendor/jquery-ui/jquery-ui.css">
        <script src="../js/foundation.min.js"></script>


        <script type="text/javascript">
            /**
             * @brief   Jquery: Everything is ready to go
             */
            $(document).ready(function(){
                // Load foundation
                $(document).foundation();

            	// run unveil plugin on page load
            	setTimeout(function() {$("img.cover").unveil();}, 1);

                /**
                 * @brief   Apply filters
                 */
            	$('a.filter').click(function() {
            		var curFilter = $(this).attr('id');
            		//[> highlight current filter <]
            		$(this).parent().siblings().removeClass('active');
            		$(this).parent().addClass('active');
            		if (curFilter === "filter-reset") {
            			$('.cover_table').show();
            		} else {
            			$('.cover_table').not('.'+curFilter).hide();
            			$('.'+curFilter).show();
            			//[> trigger for unveil.js: show all filtered images <]
            			$('.'+curFilter+ '> img').trigger('unveil');
            		}
            	});


                /**
                 * @brief   Ajax: Delete cover
                 */
                $('.cover_legend').on('click', 'button.delete_cover', function() {
                    var id  = $(this).val();
                    var del = $('#'+id).attr('data-path');

                    var data = {action: 'del', file: del};

                    // Send ajax request to this file (yeah, I know...)
                    $.ajax({
                        type: 'GET',
                        url: 'covers.php',
                        data: data,
                        dataType:'json',
//                         beforeSend:function(xhr, settings){
//                         settings.data += '&moreinfo=MoreData';
//                         },
                        success:function(response){
                            switch(response.status){
                                case 'success':
                                    alert(response.message); // do what you want
                                    // Hide stuff after deleting
                                    $('#'+id).attr('src', '../img/transparent.gif');
                                    $('.'+id+' > .action_buttons').css('display', 'none');
                                    break;
                                case 'empty':
                                    alert(response.message);
                                    break;
                                default:
                                    alert("unknown response");
                            }
                        },
                        error: function(data) {
                            alert("Failure!");
                        }
                    });
                });


                /**
                 * @brief   Ajax: Move cover from api to manual folder
                 */
                $('.cover_legend').on('click', 'button.move_cover', function() {
                    var id   = $(this).val();

                    var info = id.split('_'); //get parts of id type/extension/issn
                    var ext  = info[1];
                    var issn = info[2];

                    var img_src = $('#'+id).attr('src');
                    var move = issn+'.'+ext;

                    var new_id = 'manual_'+ext+'_'+issn;

                    var data = {action: 'move', file: move};

                    // Send ajax request to this file (yeah, I know...)
                    $.ajax({
                        type: 'GET',
                        url: 'covers.php',
                        data: data,
                        dataType:'json',
//                         beforeSend:function(xhr, settings){
//                         settings.data += '&moreinfo=MoreData';
//                         },
                        success:function(response){
                            switch(response.status){
                                case 'success':
                                    alert(response.message); // do what you want
                                    // Hide and show stuff after deleting
                                    $('#'+new_id).attr('src', response.source);
                                    $('#'+id).attr('src', '../img/transparent.gif');
                                    $('.'+id+' > .action_buttons').css('display', 'none');
                                    break;
                                case 'empty':
                                    alert(response.message);
                                    break;
                                default:
                                    alert("unknown response");
                            }
                        },
                        error: function(data) {
                            alert("Failure!");
                        }
                    });
                });


                /**
                 * @brief   Nothing yet
                 */
 

            });
        </script>

        <style type="text/css" media="screen" rel="stylesheet">
            .hidden         {display: none;}
            .tabs.vertical  {width: 100%;}
            label           {margin-right: 10px;}

            /* Covers */
            img.cover {
                max-height: 254px; max-width: 170px;
                background-image: url("<?php echo '../'.$cfg->covers->placeholder; ?>");
                display: block; margin: auto; vertical-align: middle;
                border: 1px solid red;
            }
            .cover_outline  {display: inline-flex; margin-left: auto; margin-right: auto; border: 1px solid #77CBE8; height: 256px; width: 172px; background-color: gray;}
            .cover_td {height: 264px; min-width: 180px; padding: 0; margin: 0; background-color: #000000; text-align: center;}
            .cover_legend {vertical-align: top; }

            th.journal_missing {background-color: red;}

            .action_buttons button {padding: 12px !important;}

            /* make rows use the full screen size */
            .fullWidth {width: 1140px; margin-left: auto; margin-right: auto; max-width: initial;}

            /* Sticky alert boxes */
            .alert-box {position: fixed; top: 50; right: 10; width: 150px; z-index: 999;}

        </style>
    </head>
<body>
<?php include('menu.inc') ?>
<div class="row fullWidth">
    <div class="small-8 medium-8 large-8 columns">
        <h2><?php echo __('Cover Settings for Admins') ?></h2>
    </div>
    <div class="small-4 medium-4 large-4  columns">
        <!-- define Tabs -->
        <ul class="tabs right" data-tab="">
            <li class="tab-title active"><a href="#formTab1"><?php echo __('Covers used') ?></a></li>
            <li class="tab-title"><a href="#formTab2"><?php echo __('Help') ?></a></li>
        </ul>
    </div>
</div>
<div class="row fullWidth">
    <div class="small-12 medium-12 large-12 columns">
        <?php echo create_nav(count($covers), $entry_start, $max_show); ?>
        <dl class="sub-nav">
            <dt><?php echo __('Cover filter') ?>:</dt>
            <dd class="active"><a href="#" class="filter active" id="filter-reset" title="<?php echo __('Show all journals') ?>"><?php echo __('All') ?></a></dd>
            <dd><a href="#" class="filter" id="filter-api_and_manual" title="<?php echo __('Journal has manual and api covers') ?>"><?php echo __('API & Manual') ?></a></dd>
            <dd><a href="#" class="filter" id="filter-api_only" title="<?php echo __('Journal has api only covers') ?>"><?php echo __('API only') ?></a></dd>
            <dd><a href="#" class="filter" id="filter-manual_only" title="<?php echo __('Journal has only manually added covers') ?>"><?php echo __('Manual only') ?></a></dd>
            <dd><a href="#" class="filter" id="filter-multiple_covers" title="<?php echo __('Journal has more than one cover. Note: only appear at end of list.') ?>"><?php echo __('Multiple') ?></a></dd>
            <dd><a href="#" class="filter" id="filter-no_cover" title="<?php echo __('Journal has no cover') ?>"><?php echo __('None') ?></a></dd>
            <dd><a href="#" class="filter" id="filter-cover_wo_journal" title="<?php echo __('Covers for issn\'s that are not in journals.csv') ?>"><?php echo __('Unused') ?></a></dd>
            <dd><a href="#" class="filter" id="filter-large_active_cover" title="<?php echo __('Covers greater than 15KB') ?>"><?php echo __('Large active cover') ?></a></dd>
            <!--
            <dd><a href="#" class="filter" id="topJ" title="<?php echo __('xxx') ?>">Suspended</a></dd>
            -->
        </dl>
    </div>
</div>
<div class="row fullWidth">
    <!-- Main structure 1: Body column -->
    <div class="small-10 medium-10 large-10 columns">
        <!-- start Tabs -->
        <div class="tabs-content">
            <div class="content active" id="formTab1">
                <h3><?php echo __('Available and missing covers').' '.($entry_start).' '.__('to').' '. ($entry_start + $max_show -1) ?></h3>
<?php
// Loop $max_show issn's (from manual folder, api folder and journals.csv file)
$covers_slice = array_slice($covers, $entry_start-1, $entry_start+$max_show-1);
$i = 0;
foreach ($covers_slice AS $issn => $source) {
    // Check for all our file extension if a file is available
    $cols_manual = $cols_api = $footer_manual = $footer_api = $filter_large_active = '';
    $count_manual = $count_api = $count_total = 0;
    foreach ($extensions as $ext) {
        $src  = '../img/transparent.gif';
        $size = $del = $path = $move = '';

        // Manual sources
        $id   = 'manual_'.$ext.'_'.$issn;
        if (isset($covers[$issn]['manual'][$ext])) {
            $src  = '../'.$cfg->sys->data_covers.$issn.'.'.$ext;
            $path = $covers[$issn]['manual'][$ext];
            $size = $covers[$issn]['manual']['size'];
            if ($count_total == 0 && $size > 15) $filter_large_active = ' filter-large_active_cover';
            $size = " ($size KB)";
            $del  = '<button class="button tiny alert delete_cover" value="'.$id.'" name="DeleteFile" title="'.__('Delete file for good') .'"><i class="fi-x"></i></button> ';
            $count_manual++;
            $count_total++;
        }
        $cols_manual .= '<td class="cover_td"><span class="cover_outline"><img class="cover" id="'.$id.'" src="../img/lazyloader.gif" data-src="'.$src.'" data-path="'.$path.'"></span></td>';
        $footer_manual .= '<td class="cover_legend '.$id.'"><span class="right action_buttons">'.$del.'</span>'.$ext.'<br />'.$size.'</td>';

        // Hmm, reset for api - yeah, really should be another loop
        $src  = '../img/transparent.gif';
        $size = $del = $path = '';

        // Api sources
        $id   = 'api_'.$ext.'_'.$issn;
        if (isset($covers[$issn]['api'][$ext])) {
            $src  = '../'.$cfg->sys->data_covers.'api/'.$issn.'.'.$ext;
            $path = $covers[$issn]['api'][$ext];
            $size = $covers[$issn]['api']['size'];
            if ($count_total == 0 && $size > 15) $filter_large_active = ' filter-large_active_cover';
            $size = " ($size KB)";
            $del   = '<button class="button tiny alert delete_cover" value="'.$id.'" name="DeleteFile" title="'.__('Delete file for good') .'"><i class="fi-x"></i></button> ';
            $move  = '<button class="button tiny info move_cover" value="'.$id.'" name="MoveFile" title="'.__('Move file to manual folder (prevents being downloaded ever again by api).') .'"><i class="fi-arrow-left"></i></button> ';
            $count_api++;
            $count_total++;
        }
        $cols_api .= '<td class="cover_td"><span class="cover_outline"><img class="cover" id="'.$id.'" src="../img/lazyloader.gif" data-src="'.$src.'" data-path="'.$path.'"></span></td>';
        $footer_api .= '<td class="cover_legend '.$id.'"><span class="right action_buttons">'.$move.$del.'</span>'.$ext.'<br />'.$size.'</td>';
    }

    // Create filters
    $filters = '';
    if ($count_manual > 0 && $count_api > 0) $filters .= ' filter-api_and_manual';
    if ($count_manual + $count_api > 1) $filters .= ' filter-multiple_covers';
    if ($count_manual > 0 && $count_api == 0) $filters .= ' filter-manual_only';
    if ($count_manual == 0 && $count_api > 0) $filters .= ' filter-api_only';
    if ($count_total == 0) $filters .= ' filter-no_cover';
    if (!isset($covers[$issn]['journal'])) $filters .= ' filter-cover_wo_journal';
    if (isset($filter_large_active)) $filters .= $filter_large_active;

    // Output entry
    $header         = (isset($covers[$issn]['journal'])) ? __('Covers for')." $issn: <br />".$covers[$issn]['journal'] : __('Cover(s) are not used for any journal with issn').' '.$issn;
    $header_class   = (isset($covers[$issn]['journal'])) ? 'journal_exists' : 'journal_missing';
    echo "<table class=\"cover_table $filters\">
            <thead>
                <tr><th colspan=\"6\" class=\"$header_class\">$header</th></tr>
                <tr><th colspan=\"3\">".__('Your covers')."</th><th colspan=\"3\">".__('API covers')."</th></tr>
            </thead>
            <tbody>
                <tr>$cols_manual $cols_api</tr>
                <tr>$footer_manual $footer_api</tr>
            </tbody></table>";

    $i++;
//    if ($i > 20) break;
}
?>
            </div>
            <div class="content" id="formTab2">
                <?php echo __('
                    <h3>Help</h3>
                    <ul>
                        <li>The leftmost cover is the one actually shown on the frontpage</li>
                        <li>The blue outline with the gray background indicates the size a cover is stretched to on the frontpage. The red outline  is to better distinguish the real image size from the background.</li>
                        <li>Unused covers (no journal) are at the end of the list</li>
                        <li>The filters only work for the current 100 entries shown - not the whole list</li>
                    </ul>
                    <p>Best way (outline)
                        <ol>
                            <li>Make sure the data/covers folder is empty</li>
                            <li>Download covers via api (make sure you check services in the Settings page, then run Update Journals)</li>
                            <li>Open this page</li>
                            <li>Check if you like the api cover</li>
                            <li>If you know that the api cover will likely never change, move it from data/covers/api to the data/covers folder. This reduces the time on updating journals with covers.</li>
                            <li>If you don\'t like the api cover get one yourself and put it in data/covers. If you can\'t find one, the best way to prevent further update is to use img/transparent.gif and put it into data/covers for this journal.</li>
                        </ol>
                    </p>
                ') ?>
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
</div>
</body>
</html>