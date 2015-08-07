<?php
$doi = $_GET['doi'];
if ($doi) {
    $ch = curl_init("http://dx.doi.org/".$doi); //yep, oldschool
    /*curl_setopt($ch, CURLOPT_ENCODING, 'application/vnd.citationstyles.csl+json');*/
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/vnd.citationstyles.csl+json, application/rdf+xml'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    //For Debugging
    //curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
    echo curl_exec($ch);
    curl_close($ch);
}
?>
