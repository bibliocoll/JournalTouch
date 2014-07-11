<?php
/**
 * Query the JournalTOCs RESTful API (XML)
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

require_once('../config.php');
// $toAddress = $config['mailer']['toAddress'];
$issn = $_GET['issn'];

function myget ($query,$xpath) {
  $result=array();

  foreach ($xpath->query($query) as $item) {
    if (!empty($item->nodeValue)) { $result[]=trim($item->nodeValue);} 
  }
	
  switch (sizeof($result)) {
  case 0: return ""; break; // if empty
      // turn any control characters to a space:
  case 1: return preg_replace('/[\x00-\x1F\x7F]/', ' ', $result[0]); break; // single
  default: return preg_replace('/[\x00-\x1F\x7F]/', ' ', $result); // multiple
  }
}

/* sort function */
function array_sort_by_column(&$arr, $col, $dir = SORT_DESC) {
    $sort_col = array();
    foreach ($arr as $key=> $row) {
        $sort_col[$key] = $row[$col];
    }

    array_multisort($sort_col, $dir, $arr);
}

$x = "http://www.journaltocs.ac.uk/api/journals/".$issn."?output=articles&user=".$cfg->api->jt->account;

$neuDom = new DOMDocument;

$neuDom->load($x);
$xpath = new DOMXPath( $neuDom ); 

$rootNamespace = $neuDom->lookupNamespaceUri($neuDom->namespaceURI); 
$xpath->registerNamespace('x', $rootNamespace); 

/* $xpath->registerNamespace("rdf","http://www.w3.org/1999/02/22-rdf-syntax-ns#"); */
/* $xpath->registerNamespace("prism","http://prismstandard.org/namespaces/1.2/basic/"); */
/* $xpath->registerNamespace("dc","http://purl.org/dc/elements/1.1/"); */
/* $xpath->registerNamespace("mn","http://usefulinc.com/rss/manifest/"); */

$records = $xpath->query("//x:item");
$toc = array();
 
foreach ( $records as $item ) {
	$newDom = new DOMDocument;
	$newDom->appendChild($newDom->importNode($item,true));
 
	$xpath = new DOMXPath( $newDom ); 
	$rootNamespace = $newDom->lookupNamespaceUri($newDom->namespaceURI); 
	$xpath->registerNamespace('x', $rootNamespace); 
	$xpath->registerNamespace("dc","http://purl.org/dc/elements/1.1/");
    $xpath->registerNamespace("prism", "http://prismstandard.org/namespaces/1.2/basic/");

	$title = myget("//x:title",$xpath);
	$link = myget("//x:link",$xpath);
	$source = myget("//dc:source",$xpath);
    $author = myget("//dc:creator",$xpath); // not always good data (2. field is sometimes surname, sometimes second author...)
    /* do some clean up (MIT journals: authors are in brackets, other?) */
    preg_match_all("/\((.*?)\)/", $author, $matches);
    $author = ($matches[1] ? $matches[1] : $author);
    $abstract = myget("//x:description",$xpath);
    $date = myget("//dc:date",$xpath);
    if (empty($date)) {
        $prismDate = myget("//prism:publicationDate",$xpath);
        if (!empty($prismDate)) {
            $date = date('Y-m-d',strtotime($prismDate));
        } else {
            //  if date && prismDate are empty, fill in a current date to get those articles sorted; assume they are new
            $date = date('Y-m-d');            
        }
    }
    

	$toc[] = array(
        'title' => $title, 
        'link' => $link,
        'source' => $source,
        'author' => $author,
        'date' => $date,
        'abstract' => strip_tags($abstract)); // strip any HTML to avoid errors
}

if (empty($toc)) {
    /* write something we can read from our caller script */
    echo '<span id="noTOC"/>';
/* trigger error response from conduit.js; configure in index.php */
} else {
	$no_records = count($toc);

    // sort array by date
    array_sort_by_column($toc, 'date', SORT_DESC);

// get the title & date from the first item (incl. vol/no = snatch from first item & cut pages) (beware!)
    $journalTitle = $toc[0]['source'];
    $journalTitle = preg_replace('/pp\..+/','',$journalTitle);
    $journalTitle = preg_replace('/,\s+\(.+\)$/','',$journalTitle);
    $journalTitle = preg_replace('/,$/','',$journalTitle);
    //   if (empty($journalTitle)) { $journalTitle = "TEST". myget("//x:channel/x:title",$xpath); }  // more robust
    // set time
    $timestring = date('c', strtotime($toc[0]['date'])); 

	//	echo "<br/>Found " .$no_records . " current articles from <strong>".$journalTitle."</strong>:<br/><br/>";
	echo '<h4>'.$journalTitle.'</h4>';
    echo '<h6><i class="fi-asterisk"></i> last update: <time class="timeago" datetime="'.$timestring.'">'.$timestring.'</time> <i class="fi-asterisk"></i></h6>';

    foreach ( $toc as $item ) {
        //print "<br>";print_r($item); print "<br>";
        
        if (!empty($item['title'])) {

            echo '<div class="simpleCart_shelfItem row">' . PHP_EOL;

            echo '<div class="small-6 medium-7 large-8 columns textbox">' . PHP_EOL;
            echo '<div class="toctitle">' . PHP_EOL;
            if ($cfg->api->all->articleLink == true) {
                echo "<a href=\"".$item['link']."\" class=\"item_name\">";
            } else {
                echo "<span class=\"item_name\">";
            }
            if (is_array($item['author'])) {
                echo (!empty($item['author'][0]) ? $item['author'][0].", " : "") . (!empty($item['author'][1]) ? $item['author'][1].": " :  "");
            } else { 
                echo (!empty($item['author']) ? $item['author'].": " : "");
            }
            if ($cfg->api->all->articleLink == true) {
                echo $item['title']."</a>";
            } else {
                echo $item['title']."</span>";
            }
            echo '</div>' . PHP_EOL;
            /* get extra options, set class to invisible (change in css) */
            echo "<span class=\"item_link invisible\">".$item['link']."</span>";
            echo "<span class=\"item_source invisible\">".$item['source']."</span>";
            echo '</div>' . PHP_EOL;
            echo '<div class="small-6 medium-5 large-4 columns buttonbox">';
            /* abstract button: let us assume that strlen>300 == abstract */
            echo (strlen($item['abstract'])>300 ? '<a class="button medium radius abstract">Abstract</a>&nbsp;' : '');
            /* add button */
            echo "<a class=\"item_add button medium radius\" href=\"javascript:;\"><i class=\"fi-plus\"></i> </a>&nbsp;";
            echo '</div>' . PHP_EOL;

            echo (!empty($item['abstract']) ? "<div class=\"abstract invisible\"><span>".$item['abstract']."</span></div>" . PHP_EOL : "");

            echo '</div>' . PHP_EOL;
            
        }
    }


    
    
}


?>

