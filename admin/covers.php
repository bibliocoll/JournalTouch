<?php
/**
 * @brief   Manage covers
 *
 * Introduced: 2015-11-21
 *
 * @todo
 *
 * @author Tobias Zeumer <tzeumer@verweisungsform.de>
 */
require('../sys/bootstrap.php');

// Start Ajax Save
if (is_ajax()) {
//var data = {action: 'del', file: del};
   if (isset($_GET['action'])) {
        // Action is delete
        if ($_GET['action'] == 'del') {
            $file = $_GET['file'];
            $status = unlink($file);

            $status     = ($status) ? 'success' : 'fail';
            $message    = ($status) ? 'Image was deleted' : 'Failed deleting image';
        }

        $return["status"] = $status;
        $return["message"] = utf8_encode($message);
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
$entry_start = (isset($_GET['start'])) ? $_GET['start'] : 0;

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
    $num_entries = ceil($total / $limit);

    $nav = '<ul class="pagination">
                <!-- <li class="arrow unavailable"><a href="">&laquo;</a></li> -->';
    for ($entry = 0; $entry < $num_entries; $entry++) {
        $start  = $entry * $limit+1;
        $end    = $start + $limit-1;

        if ($start == $current) {
            $nav .= '<li class="current"><a href="">'.$start.'-'.$end.'</a></li>';
        } else {
            $nav .= '<li><a href="covers.php?start='.$start.'">'.$start.'-'.$end.'</a></li>';
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
        <title>JournalTouch Settings</title>
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


                // Delete cover
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
                                    $('.'+id+' > button').css('display', 'none');
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
                        type: 'GET',
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

            /* make rows use the full screen size */
            .fullWidth {width: 80%; margin-left: auto; margin-right: auto; max-width: initial;}

            /* Sticky alert boxes */
            .alert-box {position: fixed; top: 50; right: 10; width: 150px; z-index: 999;}

            .tab-title button {width: 100%; margin: 0;}
        </style>
    </head>
<body>
<?php include('menu.inc') ?>
<div class="row fullWidth">
    <div class="small-10 medium-10 large-10 columns">
        <h2><?php echo __('Cover Settings for Admins') ?></h2>
    </div>
</div>
<div class="row fullWidth">
    <!-- Main structure 1: Menu column -->
    <div class="small-2 columns">
        <!-- define Tabs -->
        <ul class="tabs vertical" data-tab="">
            <li class="tab-title"><button type="submit" class="button submit_btn" name="save"><?php echo __('Save') ?></button></li>
            <li class="tab-title active"><a href="#formTab1"><?php echo __('Covers used') ?></a></li>
            <li class="tab-title"><a href="#formTab2"><?php echo __('Help') ?></a></li>
            <li class="tab-title"><button type="submit" class="button submit_btn" name="save"><?php echo __('Save') ?></button></li>
        </ul>
    </div>
    <!-- Main structure 2: Body column -->
    <div class="small-8 columns">
        <!-- start Tabs -->
        <div class="tabs-content">
            <div class="content active" id="formTab1">
                <p><?php echo create_nav(count($covers), $entry_start, $max_show); ?></p><br>
                <h3><?php echo __('Available and missing covers').' '.($entry_start).' to '. ($entry_start + $max_show -1) ?></h3>
<?php
// Loop all issn's we got (from manual folder, api folder and journals.csv file)
$covers_slice = array_slice($covers, $entry_start-1, $entry_start+$max_show-1);
$i = 0;
foreach ($covers_slice AS $issn => $source) {
    $header = (isset($covers[$issn]['journal'])) ? __('Covers for')." $issn: <br />".$covers[$issn]['journal'] : __('Cover(s) are not used for any journal');
    echo '<table class="cover_table">
            <thead>
                <tr><th colspan="6">'.$header.'</th></tr>
                <tr><th colspan="3">'.__('Your covers').'</th><th colspan="3">'.__('API covers').'</th></tr>
            </thead>
            <tbody>';

    // Check for all our file extension if a file is available
    $cols_manual = $cols_api = $footer_manual = $footer_api = $size = $del = $id = $path = '';
    foreach ($extensions as $ext) {
        // Manual sources
        if (isset($covers[$issn]['manual'][$ext])) {
            $id   = 'manual_'.$ext.'_'.$issn;
            $src  = '../'.$cfg->sys->data_covers.$issn.'.'.$ext;
            $path = $covers[$issn]['manual'][$ext];
            $size = $covers[$issn]['manual']['size'];
            $size = " ($size KB)";
            $del  = '<button class="button tiny alert right delete_cover" value="'.$id.'" name="Delete"> X </button>';
        } else {
            $src  = '../img/transparent.gif';
            $size = $del = $id = $path = '';
        }
        $cols_manual .= '<td class="cover_td"><span class="cover_outline"><img class="cover" id="'.$id.'" src="../img/lazyloader.gif" data-src="'.$src.'" data-path="'.$path.'"></span></td>';
        $footer_manual .= '<td class="cover_legend '.$id.'">'.$ext.$size.$del.'</td>';

        // Api sources
        // Manual sources
        if (isset($covers[$issn]['api'][$ext])) {
            $id   = 'api_'.$ext.'_'.$issn;
            $src  = '../'.$cfg->sys->data_covers.'api/'.$issn.'.'.$ext;
            $path = $covers[$issn]['api'][$ext];
            $size = $covers[$issn]['api']['size'];
            $size = " ($size KB)";
            $del  = '<button class="button tiny alert right delete_cover" value="'.$id.'" name="Delete"> X </button>';
        } else {
            $src  = '../img/transparent.gif';
            $size = $del = $id = $path = '';
        }
        $cols_api .= '<td class="cover_td"><span class="cover_outline"><img class="cover" id="'.$id.'" src="../img/lazyloader.gif" data-src="'.$src.'" data-path="'.$path.'"></span></td>';
        $footer_api .= '<td class="cover_legend '.$id.'">'.$ext.$size.$del.'</td>';
    }
    echo "  <tr>$cols_manual $cols_api</tr>
            <tr>$footer_manual $footer_api</tr>
            </tbody></table>";

    $i++;
//    if ($i > 20) break;
}
?>
                <p><?php echo create_nav(count($covers), $entry_start, $max_show); ?></p><br>
            </div>
            <div class="content" id="formTab2">
                <h3><?php echo __('Help') ?></h3>
                <p>The leftmost cover is the one actually shown on the frontpage</p>
                <p>The blue outline with the gray background indicates the size a cover is stretched to on the frontpage. The red outline  is to better distinguish the real image size from the background.</p>
                <p>Best way (outline)
                    <ol>
                        <li>Make sure the data/covers folder is empty</li>
                        <li>Download covers via api (make sure you check services in the Settings page, then run Update Journals)</li>
                        <li>Open this page</li>
                        <li>Check if you like the api cover</li>
                        <li>If you know that the api cover will likely never change, move it from data/covers/api to the data/covers folder. This reduces the time on updating journals with covers.</li>
                        <li>If you don't like the api cover get one yourself and put it in data/covers. If you can't find one, the best way to prevent further update is to use img/transparent.gif and put it into data/covers for this journal.</li>
                    </ol>
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