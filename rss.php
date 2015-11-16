<?php
/**
 * @brief   Fetches a rss feed from JournalTocs and set proxy for links
 *
 * Introduced: 2015-11-16
 *
 * @author Andreas Bohne-Lang (Medizinische Fakultät Mannheim der Ruprecht-Karls-Universität Heidelberg)
 */

require_once("sys/bootstrap.php");

#This is the  Base for the JournalTOCs API, which is freely available for developers to create their own web applications with Table of Contents (TOCs) published by over 16,000 scholarly journals.
# http://www.journaltocs.ac.uk/api/index.php

if (isset($_GET["issn"]) && ! empty( $_GET["issn"]) ){
    $rss = 'http://www.journaltocs.ac.uk/api/journals/'.$_GET["issn"].'?output=articles&user='.$cfg->api->jt->account;

    if (!(isset($cfg->prefs->proxy) && !empty($cfg->prefs->proxy))) {
        header("Location: $rss");
        exit;
	} else {
		$rss_content=file($rss);
		foreach ($rss_content as $ind => $val) {
			if( strstr($val,"rdf:about=") || strstr($val,"rdf:resource") || strstr($val,"<content:encoded>")|| strstr($val,"<link>")  ){
				$rss_content[$ind] = substr($val,0,strpos($val,"http://")) . $cfg->prefs->proxy . substr($val,strpos($val,"http://"));
			}
		}
	}
}
header("Content-Type: application/xml; charset=utf-8");
echo trim(implode("",$rss_content));
?>
