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

$config = parse_ini_file('../config/config.ini', TRUE);
$apiUserKey = $config['journaltocs']['apiUserKey'];
$toAddress = $config['mailer']['toAddress'];
$updatesURL = $config['updates']['url'];
$outFile = $config['updates']['outfile'];
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

$x = $updatesURL;

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

    $abstract = myget("//x:description",$xpath);
  
	$toc[] = array(
        'title' => $title, 
        'link' => $link,
        'issn' => $issn,
        'eIssn' => $eIssn,
        'abstract' => strip_tags($abstract)); // strip any HTML to avoid errors
}

$curWeek = date("W");
$newissns = array();

/* load the current array of issns into $data (compare later) */
$json = "../".$outFile;
$file = file_get_contents($json);
$data = json_decode($file, true);

print_r($data);
echo '<hr/>';

unset($file);//prevent memory leaks for large json.

if (empty($toc)) {
	echo 'ERROR!';
} else {
	$no_records = count($toc);

	echo '<h5>'.$journalTitle.'</h5>';

    $arrCmp = json_decode(file_get_contents($json), true);

    foreach ( $toc as $item ) {
        
        if (!empty($item['title'])) {

            /* if we have new issns not already in our array, add them */

            if(!empty($item['eIssn']) && !search_array($item['eIssn'], $arrCmp)) {
              array_push($data, array('issn' => $item['eIssn'], 'cw' => $curWeek));
            }
            if(!empty($item['issn']) && !search_array($item['issn'], $arrCmp)) {
              array_push($data, array('issn' => $item['issn'], 'cw' => $curWeek));
            }
        }
    }
}

/* new $data */
print_r($data);

//save updated data
file_put_contents($json,json_encode($data));
unset($data);//release memory


?>

