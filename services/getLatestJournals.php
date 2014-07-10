<?php 
/**
 * Run a regular check against JournalTocs for updates and store it to a JSON file
 * this is read by sys/class.ListJournals.php (getJournalUpdates())
 * You may also want to include the output file via AJAX queries for example
 * a JournalTOCs Premium API account is essential for this service (or write a similar service)
 * Time-stamp: "2014-07-10 12:44:40 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */
// do not show system errors, these should be handled in js or below
//error_reporting(0);

require_once dirname(__FILE__).'/../sys/class.ListJournals.php';
/* setup methods & objects */
$lister = new ListJournals();
$journals = $lister->getJournals();

$config = parse_ini_file('../config/config.ini', TRUE);
$apiUserKey = $config['journaltocs']['apiUserKey'];
$updatesURL = $config['updates']['url'];
$issn = $_GET['issn'];

$jsonFile = "../input/updates.json.txt";


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

// fresh array:
$upd = array();

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
    // write only one $issn
    $issn = !empty($issn) ? $issn : $eIssn;
    $date = myget("//dc:date",$xpath);

    $abstract = myget("//x:description",$xpath);
  
	$toc[] = array(
        'title' => $title, 
        'link' => $link,
        'issn' => $issn,
        //'eIssn' => $eIssn,
        'date' => $date
    );
}

if (empty($toc)) {
	echo 'ERROR!';
} else {

	echo '<h5>Most recent journal updates from the last 2 weeks</h5>';

    foreach ( $toc as $item ) {
        
        if (!empty($item['title'])) {

            if (!empty($item['issn'])) {
                if (search_array($item['issn'], $journals)) {
                     
                    $date = new DateTime($item['date']);
                    /* unless we use PHP 5.3 (with DateTime::sub), we need to add a timespan for comparison */
                    $td = strtotime($item['date']);
                    $cdate = date("Y-m-d", strtotime("+2 weeks", $td));
                    /* compare with current date */
                    $curDate = new DateTime(); // today
                    $myDate   = new DateTime($cdate);
                    
                    /* only store current dates (not older than the given date in $cdate) */
                    if ($myDate >= $curDate) {
                        print $item['title'] . ", last update on ".$item['date']."<br/>";
                        // save to array
                        array_push($upd, array(
                            'issn' => $item['issn'], 
                            'date' => $item['date'],
                            'title' => $item['title'], 
                            'timestr' => date('c', strtotime($item['date']))));
                    }
                 }
            }
        }
    }
}


/* new $data */
echo ' <strong>done.</strong><br/>';
echo '<p><hr/></p>';
}

//save updated data
file_put_contents($jsonFile,json_encode($upd));

?>

