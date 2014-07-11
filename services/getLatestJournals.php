<?php
/**
 * Run a regular check against JournalTocs for updates and store it to a JSON file (run separately)
 * a JournalTOCs Premium API account is essential for this service (or write a similar service)
 * 
 * TODO: remove old entries in json file (calendar week - 4 or sth like that)
 *
 * 
 * Time-stamp: "2014-04-10 15:03:26 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */
// do not show system errors, these should be handled in js or below
//error_reporting(0);

require_once('../config.php');
$updatesURL = $cfg->api->jt->updates . $cfg->api->jt->account;
$issn = $_GET['issn'];

function myget ($query,$xpath) {
  $result=array();

  foreach ($xpath->query($query) as $item) {
    if (!empty($item->nodeValue)) { $result[]=trim($item->nodeValue);} 
  }
	
  switch (sizeof($result)) {
  case 0: return ""; break; // if empty
  case 1: return $result[0]; break; // single
  default: return $result; // multiple
  }
}

function search_array($needle, $haystack) {
     if(in_array($needle, $haystack)) {
          return true;
     }
     foreach($haystack as $element) {
          if(is_array($element) && search_array($needle, $element))
               return true;
     }
   return false;
}

/* query loop for multiple update URLs from config */



foreach ($updatesURL as $updateURL) {

    echo "querying " .$updateURL."...";

$x = $updateURL;

$neuDom = new DOMDocument;

$neuDom->load($x);
$xpath = new DOMXPath( $neuDom ); 

$rootNamespace = $neuDom->lookupNamespaceUri($neuDom->namespaceURI); 
$xpath->registerNamespace('x', $rootNamespace); 

// get the title second option (incl. vol/no = snatch from first item & cut pages) (beware!)
$journalTitle = myget("//x:item[1]/dc:source",$xpath);
$journalTitle = preg_replace('/pp\..+/','',$journalTitle);
if (empty($journalTitle)) { $journalTitle = myget("//x:channel/x:title",$xpath); }  // more robust
$records = $xpath->query("//x:item");
$toc = array();
 
foreach ( $records as $item ) {
	$newDom = new DOMDocument;
	$newDom->appendChild($newDom->importNode($item,true));
 
	$xpath = new DOMXPath( $newDom ); 
	$rootNamespace = $newDom->lookupNamespaceUri($newDom->namespaceURI); 
	$xpath->registerNamespace('x', $rootNamespace); 
	$xpath->registerNamespace("dc","http://purl.org/dc/elements/1.1/");
    $xpath->registerNamespace("prism","http://prismstandard.org/namespaces/1.2/basic/");

	$title = myget("//x:title",$xpath);
	$link = myget("//x:link",$xpath);
	$issn = myget("//prism:issn",$xpath);
	$eIssn = myget("//prism:eIssn",$xpath);
    $date = myget("//dc:date",$xpath);

    $abstract = myget("//x:description",$xpath);
  
	$toc[] = array(
        'title' => $title, 
        'link' => $link,
        'issn' => $issn,
        'eIssn' => $eIssn,
        'date' => $date
    );
}


/* load the current array of issns into $data (compare later) */
/* $json = "../".$cfg->api->jt->outfile; */
/* $file = file_get_contents($json); */
/* $data = json_decode($file, true); */

/* echo '<h1>Read file contents:</h1>'; */
/* print_r($data); */

/* echo '<hr/>'; */

/* unset($file);//prevent memory leaks for large json. */

if (empty($toc)) {
	echo 'ERROR!';
} else {
	$no_records = count($toc);

	echo '<h5>'.$journalTitle.'</h5>';
    $json = "../".$cfg->api->jt->outfile;
    $arrCmp = json_decode(file_get_contents($json), true);

    foreach ($arrCmp as $k1=>$v) {

        foreach ($v as $k2 => $r) {
            if (strlen($r) == 10) {
            /* unless we use PHP 5.3 (with DateTime::sub), we need to add a timespan for comparison */
            $td = strtotime($r);
            $cdate = date("Y-m-d", strtotime("+1 month", $td));
            /* if there is a $date (e.g. from csv), compare with current date */
            $curDate = new DateTime(); // today
            $myDate   = new DateTime($cdate);
            // if ($r <= $weekSpan) {
            if ($r >= $curDate) {
                unset($arrCmp[$k1]);
            }
            }
        }
    }

    foreach ( $toc as $item ) {
        
        if (!empty($item['title'])) {

            /* convert found date of last update in the data to calendar week */
            $date = new DateTime($item['date']);
            /* unless we use PHP 5.3 (with DateTime::sub), we need to add a timespan for comparison */
            $td = strtotime($item['date']);
            $cdate = date("Y-m-d", strtotime("+1 month", $td));
            /* if there is a $date (e.g. from csv), compare with current date */
            $curDate = new DateTime(); // today
            $myDate   = new DateTime($cdate);

            /* if we have new issns not already in our array, add them */

            /* skip entries too old */
            if ($myDate >= $curDate) {
                if(!empty($item['eIssn']) && !search_array($item['eIssn'], $arrCmp)) {
                    array_push($arrCmp, array('issn' => $item['eIssn'], 'date' => $item['date']));
                }
                if(!empty($item['issn']) && !search_array($item['issn'], $arrCmp)) {
                    array_push($arrCmp, array('issn' => $item['issn'], 'date' => $item['date']));
                }
            }

        }
    }
}

/* new $data */
echo ' <strong>done.</strong><br/>';
//print_r($arrCmp);


//save updated data
file_put_contents($json,json_encode($arrCmp));
unset($data);//release memory
unset($arrCmp);//release memory

}

?>


