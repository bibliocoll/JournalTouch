<?php
$doi = (isset($_GET['doi'])) ? $_GET['doi'] : false;

if ($doi) {
    $url = "http://dx.doi.org/$doi";

	$context = stream_context_create(array(
        'http' => array('header' => 'Accept: application/vnd.citationstyles.csl+json, application/rdf+xml')
    ));
	$cr_json = file_get_contents($url, false, $context);

    echo $cr_json;

}
?>
