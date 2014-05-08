<?php
/**
 * Query the experimental CrossRef RESTful API (JSON)
 * Output HTML snippet
 *
 * includes SimpleCart js css classes (see below)
 * CAVEAT: be careful to change the HTML layout (linked to jQuery Selectors)
 * 
 * Time-stamp: "2014-04-10 15:03:26 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */
// do not show system errors, these should be handled in js or below
error_reporting(0);

$config = parse_ini_file('../config/config.ini', TRUE);
//$toAddress = $config['mailer']['toAddress'];
$alink = $config['toc']['alink'];
$issn = $_GET['issn'];

// results output is limited to 20 per page, so send at least two queries
$json = "http://search.crossref.org/dois?q=".$issn."&sort=year";
$json2 = "http://search.crossref.org/dois?q=".$issn."&sort=year&page=2";
$json3 = "http://search.crossref.org/dois?q=".$issn."&sort=year&page=3";
$json4 = "http://search.crossref.org/dois?q=".$issn."&sort=year&page=4";

$file = file_get_contents($json);
$file2 = file_get_contents($json2);
$file3 = file_get_contents($json3);
$file4 = file_get_contents($json4);

$j1 = json_decode($file, true);
$j2 = json_decode($file2, true);
$j3 = json_decode($file3, true);
$j4 = json_decode($file4, true);

// merge 
$records = array_merge_recursive( $j1, $j2, $j3, $j4 );

$toc = array();

foreach ( $records as $item ) {

    $coins = html_entity_decode($item['coins']);
    /* parse OpenURL params (coins) into $pcoins */
    parse_str($coins, $pcoins);
	$title = $item['title'];
	$link = $item['doi'];
	//$source = $item['fullCitation'];
    $source = $pcoins['rft_jtitle'] . ", Vol. " . $pcoins['rft_volume'] . ", No. " . $pcoins['rft_issue'] . " (" . $pcoins['rft_date'] . ")";
    $author = $pcoins['rft_au']; 
    $abstract = '';
    $year = $item['year'];
    $vol = $pcoins['rft_volume'];
    $iss = $pcoins['rft_issue'];
    $spage = $pcoins['rft_spage'];
    $jtitle = $pcoins['rft_jtitle'];
    /* create a sort string */
    $sortStr = $year . '-' . $vol . '-' . $iss . '-' . $spage;
    /* create a vol. string to mark the most recent stuff */
    $volStr = $year . '-' . $vol . '-' . $iss;

    /* only move to array if year is current or before */
    $curY = date("Y");
    if ($year >= $curY-1) { 
	$toc[] = array(
        'title' => $title, 
        'link' => $link,
        'source' => $source,
        'author' => $author,
        'abstract' => strip_tags($abstract), // strip any HTML to avoid errors
        'year' => $year,
        'vol' => $vol,
        'iss' => $iss,
        'jtitle' => urldecode($jtitle),
        'sortStr' => $sortStr,
        'volStr' => $volStr
    ); 
    }
}
	
/* sort array with our sort string to have the latest first */
  function cmp($a, $b)
    {
        return strcmp($b["sortStr"], $a["sortStr"]);
    }
    usort($toc, "cmp");


if (empty($toc)) {
    /* write something we can read from our caller script */
    echo '<span id="noTOC"/>';
/* gets handled in the second ajax call */
/* uncomment if you do not want to query a second source (e.g. JournalTOCs). Configure in js/local/conduit.js */
	/* echo '<div data-alert class="alert-box info"><span id="tocAlertText">No table of contents found! Are you interested in this title?</span>'; */
    /* echo '<a class="button radius" href="checkout.php?action=contact&message=The%20table%20of%20contents%20for%20this%20journal%20seem%20to%20be%20missing%20(ISSN:%20'.$issn.')"><i class="fi-comment"></i> Please notify us!</a>'; */
    /* echo '</div>'; */
} else {
	$no_records = count($toc);

    /* assume that the first element of our sorted array is the current issue; construct a title from it */
    $journalTitle = $toc[0]['jtitle'] . ", Vol. " . $toc[0]['vol'] . ", Nr. " . $toc[0]['iss'] . " (" . $toc[0]['year'] . ")";

	echo '<h4>'.$journalTitle.'</h4>';
    // debugging:
    // echo '<h4 style="background-color:yellow">debugging info: CrossRef</h4>';

    // we have a sorted array and want a foreach to grasp the most current vol./issue to filter out the rest
    // so let us save the first iteration
    $first = true;

    foreach ( $toc as $item ) {

        if ($first) {
            $mykey = $item['volStr'];
            $first = false; /* set to false */
        }

        /* show stuff only for items that were sorting above == current items */
        if ($item['volStr'] == $mykey) {

        // debugging:
        // echo $item['sortStr']. " # ";

            if (!empty($item['title'])) {
                
                echo '<div class="simpleCart_shelfItem row">';
                
                echo '<div class="small-10 medium-9 large-9 columns textbox">';
                echo '<div class="toctitle">';
                if ($alink == true) {
                    echo "<a href=\"".$item['link']."\" class=\"item_name\">";
                } else {
                    echo "<span class=\"item_name\">";
                }
                if (is_array($item['author'])) {
                    echo (!empty($item['author'][0]) ? $item['author'][0].", " : "") . (!empty($item['author'][1]) ? $item['author'][1].": " :  "");
                } else {
                    echo (!empty($item['author']) ? $item['author'].": " : "");
                }
                if ($alink == true) {
                    echo $item['title']."</a>";
                } else {
                    echo $item['title']."</span>";
                }
                //echo $item['source']."</a>";
                //echo $item['source']."</span>";
                echo '</div>';
                /* get extra options, set class to invisible (change in css) */
                echo "<span class=\"item_link invisible\">".$item['link']."</span>";
                echo "<span class=\"item_source invisible\">".$item['source']."</span>";
                echo '</div>';
                echo '<div class="small-2 medium-3 large-3 columns buttonbox">';
                /* abstract button: let us assume that strlen>300 == abstract */
                echo (strlen($item['abstract'])>300 ? '<a class="button medium radius abstract">Abstract</a>&nbsp;' : '');
                /* add button */
                echo "<a class=\"item_add button medium radius\" href=\"javascript:;\"><i class=\"fi-plus\"></i> </a>&nbsp;";
                echo '</div>';
                
                echo (!empty($item['abstract']) ? "<div class=\"abstract invisible\"><span>".$item['abstract']."</span></div>" : "");
                
                echo '</div>';
                
            }
        }
    }
    
}


?>

