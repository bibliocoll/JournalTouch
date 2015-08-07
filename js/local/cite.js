$(document).ready(function() {
    var itemIds = [];
    simpleCart.load();
    simpleCart.each(function(item){
        itemIds.push(item.get('id'));
    });

    console.log(itemIds);

    var sys = {};
    sys.retrieveLocale =(function(lang){
        return '<locale xml:lang="en" xmlns="http://purl.org/net/xbiblio/csl">  <style-options punctuation-in-quote="true"/>  <date form="text">    <date-part name="month" suffix=" "/>    <date-part name="day" suffix=", "/>    <date-part name="year"/>  </date>  <date form="numeric">    <date-part name="year"/>    <date-part name="month" form="numeric" prefix="-" range-delimiter="/"/>    <date-part name="day" prefix="-" range-delimiter="/"/>  </date>  <terms>    <term name="document-number-label">No.</term>    <term name="document-number-authority-suffix">Doc.</term>    <term name="un-sales-number-label">U.N. Sales No.</term>    <term name="collection-number-label">No.</term>    <term name="open-quote">“</term>    <term name="close-quote">”</term>    <term name="open-inner-quote">‘</term>    <term name="close-inner-quote">’</term>    <term name="ordinal-01">st</term>    <term name="ordinal-02">nd</term>    <term name="ordinal-03">rd</term>    <term name="ordinal-04">th</term>    <term name="long-ordinal-01">first</term>    <term name="long-ordinal-02">second</term>    <term name="long-ordinal-03">third</term>    <term name="long-ordinal-04">fourth</term>    <term name="long-ordinal-05">fifth</term>    <term name="long-ordinal-06">sixth</term>    <term name="long-ordinal-07">seventh</term>    <term name="long-ordinal-08">eighth</term>    <term name="long-ordinal-09">ninth</term>    <term name="long-ordinal-10">tenth</term>    <term name="at">at</term>    <term name="in">in</term>    <term name="ibid">ibid</term>    <term name="accessed">accessed</term>    <term name="retrieved">retrieved</term>    <term name="from">from</term>    <term name="forthcoming">forthcoming</term>    <term name="references">      <single>reference</single>      <multiple>references</multiple>    </term>    <term name="references" form="short">      <single>ref</single>      <multiple>refs</multiple>    </term>    <term name="no date">n.d.</term>    <term name="and">and</term>    <term name="et-al">et al.</term>    <term name="interview">interview</term>    <term name="letter">letter</term>    <term name="anonymous">anonymous</term>    <term name="anonymous" form="short">anon.</term>    <term name="and others">and others</term>    <term name="in press">in press</term>    <term name="online">online</term>    <term name="cited">cited</term>    <term name="internet">internet</term>    <term name="presented at">presented at the</term>    <term name="ad">AD</term>    <term name="bc">BC</term>    <term name="season-01">Spring</term>    <term name="season-02">Summer</term>    <term name="season-03">Autumn</term>    <term name="season-04">Winter</term>    <term name="with">with</term>        <!-- CATEGORIES -->    <term name="anthropology">anthropology</term>    <term name="astronomy">astronomy</term>    <term name="biology">biology</term>    <term name="botany">botany</term>    <term name="chemistry">chemistry</term>    <term name="engineering">engineering</term>    <term name="generic-base">generic base</term>    <term name="geography">geography</term>    <term name="geology">geology</term>    <term name="history">history</term>    <term name="humanities">humanities</term>    <term name="literature">literature</term>    <term name="math">math</term>    <term name="medicine">medicine</term>    <term name="philosophy">philosophy</term>    <term name="physics">physics</term>    <term name="psychology">psychology</term>    <term name="sociology">sociology</term>    <term name="science">science</term>    <term name="political_science">political science</term>    <term name="social_science">social science</term>    <term name="theology">theology</term>    <term name="zoology">zoology</term>        <!-- LONG LOCATOR FORMS -->    <term name="book">      <single>book</single>      <multiple>books</multiple>    </term>    <term name="chapter">      <single>chapter</single>      <multiple>chapters</multiple>    </term>    <term name="column">      <single>column</single>      <multiple>columns</multiple>    </term>    <term name="figure">      <single>figure</single>      <multiple>figures</multiple>    </term>    <term name="folio">      <single>folio</single>      <multiple>folios</multiple>    </term>    <term name="issue">      <single>number</single>      <multiple>numbers</multiple>    </term>    <term name="line">      <single>line</single>      <multiple>lines</multiple>    </term>    <term name="note">      <single>note</single>      <multiple>notes</multiple>    </term>    <term name="opus">      <single>opus</single>      <multiple>opera</multiple>    </term>    <term name="page">      <single>page</single>      <multiple>pages</multiple>    </term>    <term name="paragraph">      <single>paragraph</single>      <multiple>paragraph</multiple>    </term>    <term name="part">      <single>part</single>      <multiple>parts</multiple>    </term>    <term name="section">      <single>section</single>      <multiple>sections</multiple>    </term>    <term name="volume">      <single>volume</single>      <multiple>volumes</multiple>    </term>    <term name="edition">      <single>edition</single>      <multiple>editions</multiple>    </term>    <term name="verse">      <single>verse</single>      <multiple>verses</multiple>    </term>    <term name="sub verbo">      <single>sub verbo</single>      <multiple>s.vv</multiple>    </term>        <!-- SHORT LOCATOR FORMS -->    <term name="book" form="short">bk.</term>    <term name="chapter" form="short">chap.</term>    <term name="column" form="short">col.</term>    <term name="figure" form="short">fig.</term>    <term name="folio" form="short">f.</term>    <term name="issue" form="short">no.</term>    <term name="opus" form="short">op.</term>    <term name="page" form="short">      <single>p.</single>      <multiple>pp.</multiple>    </term>    <term name="paragraph" form="short">para.</term>    <term name="part" form="short">pt.</term>    <term name="section" form="short">sec.</term>    <term name="sub verbo" form="short">      <single>s.v.</single>      <multiple>s.vv.</multiple>    </term>    <term name="verse" form="short">      <single>v.</single>      <multiple>vv.</multiple>    </term>    <term name="volume" form="short">    \t<single>vol.</single>    \t<multiple>vols.</multiple>    </term>    <term name="edition">edition</term>    <term name="edition" form="short">ed.</term>        <!-- SYMBOL LOCATOR FORMS -->    <term name="paragraph" form="symbol">      <single>¶</single>      <multiple>¶¶</multiple>    </term>    <term name="section" form="symbol">      <single>§</single>      <multiple>§§</multiple>    </term>        <!-- LONG ROLE FORMS -->    <term name="author">      <single></single>      <multiple></multiple>    </term>    <term name="editor">      <single>editor</single>      <multiple>editors</multiple>    </term>    <term name="translator">      <single>translator</single>      <multiple>translators</multiple>    </term>        <!-- SHORT ROLE FORMS -->    <term name="author" form="short">      <single></single>      <multiple></multiple>    </term>    <term name="editor" form="short">      <single>ed.</single>      <multiple>eds.</multiple>    </term>    <term name="translator" form="short">      <single>tran.</single>      <multiple>trans.</multiple>    </term>        <!-- VERB ROLE FORMS -->    <term name="editor" form="verb">edited by</term>    <term name="translator" form="verb">translated by</term>    <term name="recipient" form="verb">to</term>    <term name="interviewer" form="verb">interview by</term>        <!-- SHORT VERB ROLE FORMS -->    <term name="editor" form="verb-short">ed.</term>    <term name="translator" form="verb-short">trans.</term>        <!-- LONG MONTH FORMS -->    <term name="month-01">January</term>    <term name="month-02">February</term>    <term name="month-03">March</term>    <term name="month-04">April</term>    <term name="month-05">May</term>    <term name="month-06">June</term>    <term name="month-07">July</term>    <term name="month-08">August</term>    <term name="month-09">September</term>    <term name="month-10">October</term>    <term name="month-11">November</term>    <term name="month-12">December</term>        <!-- SHORT MONTH FORMS -->    <term name="month-01" form="short">Jan.</term>    <term name="month-02" form="short">Feb.</term>    <term name="month-03" form="short">Mar.</term>    <term name="month-04" form="short">Apr.</term>\t<term name="month-05" form="short">May</term>    <term name="month-06" form="short">Jun.</term>    <term name="month-07" form="short">Jul.</term>    <term name="month-08" form="short">Aug.</term>    <term name="month-09" form="short">Sep.</term>    <term name="month-10" form="short">Oct.</term>    <term name="month-11" form="short">Nov.</term>    <term name="month-12" form="short">Dec.</term>  </terms></locale>';
    });

    sys.retrieveItem =(function(id){
        var item = simpleCart.find(id);
        var jsonobj = JSON.parse(item.get('citestr'));
        //console.log(id);
        //console.log(jsonobj);
        jsonobj.id = id;
        return jsonobj;
    });

    //var bibtex = '<?xml version="1.0" encoding="utf-8"?> <style xmlns="http://purl.org/net/xbiblio/csl" class="in-text" version="1.0" demote-non-dropping-particle="sort-only" default-locale="en-US"> <info> <title>BibTeX generic citation style</title> <id>http://www.zotero.org/styles/bibtex</id> <link href="http://www.zotero.org/styles/bibtex" rel="self"/> <link href="http://www.bibtex.org/" rel="documentation"/> <author> <name>Markus Schaffner</name> </author> <contributor> <name>Richard Karnesky</name> <email>karnesky+zotero@gmail.com</email> <uri>http://arc.nucapt.northwestern.edu/Richard_Karnesky</uri> </contributor> <category citation-format="author-date"/> <category field="generic-base"/> <updated>2012-09-14T21:22:32+00:00</updated> <rights license="http://creativecommons.org/licenses/by-sa/3.0/">This work is licensed under a Creative Commons Attribution-ShareAlike 3.0 License</rights> </info> <macro name="zotero2bibtexType"> <choose> <if type="bill book graphic legal_case legislation motion_picture report song" match="any"> <text value="book"/> </if> <else-if type="chapter paper-conference" match="any"> <text value="inbook"/> </else-if> <else-if type="article article-journal article-magazine article-newspaper" match="any"> <text value="article"/> </else-if> <else-if type="thesis" match="any"> <text value="phdthesis"/> </else-if> <else-if type="manuscript" match="any"> <text value="unpublished"/> </else-if> <else-if type="paper-conference" match="any"> <text value="inproceedings"/> </else-if> <else-if type="report" match="any"> <text value="techreport"/> </else-if> <else> <text value="misc"/> </else> </choose> </macro> <macro name="citeKey"> <group delimiter="_"> <text macro="author-short" text-case="lowercase"/> <text macro="issued-year"/> </group> </macro> <macro name="author-short"> <names variable="author"> <name form="short" delimiter="_" delimiter-precedes-last="always"/> <substitute> <names variable="editor"/> <names variable="translator"/> <choose> <if type="bill book graphic legal_case legislation motion_picture report song" match="any"> <text variable="title" form="short"/> </if> <else> <text variable="title" form="short"/> </else> </choose> </substitute> </names> </macro> <macro name="issued-year"> <date variable="issued"> <date-part name="year"/> </date> </macro> <macro name="issued-month"> <date variable="issued"> <date-part name="month" form="short" strip-periods="true"/> </date> </macro> <macro name="author"> <names variable="author"> <name sort-separator=", " delimiter=" and " delimiter-precedes-last="always" name-as-sort-order="all"/> <label form="long" text-case="capitalize-first"/> </names> </macro> <macro name="editor-translator"> <names variable="editor translator" delimiter=", "> <name sort-separator=", " delimiter=" and " delimiter-precedes-last="always" name-as-sort-order="all"/> <label form="long" text-case="capitalize-first"/> </names> </macro> <macro name="title"> <text variable="title"/> </macro> <macro name="number"> <text variable="issue"/> <text variable="number"/> </macro> <macro name="container-title"> <choose> <if type="chapter paper-conference" match="any"> <text variable="container-title" prefix=" booktitle={" suffix="}"/> </if> <else> <text variable="container-title" prefix=" journal={" suffix="}"/> </else> </choose> </macro> <macro name="publisher"> <choose> <if type="thesis"> <text variable="publisher" prefix=" school={" suffix="}"/> </if> <else-if type="report"> <text variable="publisher" prefix=" institution={" suffix="}"/> </else-if> <else> <text variable="publisher" prefix=" publisher={" suffix="}"/> </else> </choose> </macro> <macro name="pages"> <text variable="page"/> </macro> <macro name="edition"> <text variable="edition"/> </macro> <citation et-al-min="10" et-al-use-first="10" disambiguate-add-year-suffix="true" disambiguate-add-names="false" disambiguate-add-givenname="false" collapse="year"> <sort> <key macro="author"/> <key variable="issued"/> </sort> <layout delimiter="_"> <text macro="citeKey"/> </layout> </citation> <bibliography hanging-indent="false" et-al-min="10" et-al-use-first="10"> <sort> <key macro="author"/> <key variable="issued"/> </sort> <layout> <text macro="zotero2bibtexType" prefix=" @"/> <group prefix="{" suffix="}" delimiter=", "> <text macro="citeKey"/> <text variable="publisher-place" prefix=" place={" suffix="}"/> <!--Fix This--> <text variable="chapter-number" prefix=" chapter={" suffix="}"/> <!--Fix This--> <text macro="edition" prefix=" edition={" suffix="}"/> <!--Is this in CSL? <text variable="type" prefix=" type={" suffix="}"/>--> <text variable="collection-title" prefix=" series={" suffix="}"/> <text macro="title" prefix=" title={" suffix="}"/> <text variable="volume" prefix=" volume={" suffix="}"/> <!--Not in CSL<text variable="rights" prefix=" rights={" suffix="}"/>--> <text variable="ISBN" prefix=" ISBN={" suffix="}"/> <text variable="ISSN" prefix=" ISSN={" suffix="}"/> <!--Not in CSL <text variable="LCCN" prefix=" callNumber={" suffix="}"/>--> <text variable="archive_location" prefix=" archiveLocation={" suffix="}"/> <text variable="URL" prefix=" url={" suffix="}"/> <text variable="DOI" prefix=" DOI={" suffix="}"/> <text variable="abstract" prefix=" abstractNote={" suffix="}"/> <text variable="note" prefix=" note={" suffix="}"/> <text macro="number" prefix=" number={" suffix="}"/> <text macro="container-title"/> <text macro="publisher"/> <text macro="author" prefix=" author={" suffix="}"/> <text macro="editor-translator" prefix=" editor={" suffix="}"/> <text macro="issued-year" prefix=" year={" suffix="}"/> <text macro="issued-month" prefix=" month={" suffix="}"/> <text macro="pages" prefix=" pages={" suffix="}"/> <text variable="collection-title" prefix=" collection={" suffix="}"/> </group> </layout> </bibliography> </style>';
    var bibtex = '<style xmlns="http://purl.org/net/xbiblio/csl" class="in-text" version="1.0" demote-non-dropping-particle="sort-only" default-locale="en-US"> <info> <title>BibTeX generic citation style</title> <id>http://www.zotero.org/styles/bibtex</id> <link href="http://www.zotero.org/styles/bibtex" rel="self"/> <link href="http://www.bibtex.org/" rel="documentation"/> <author> <name>Markus Schaffner</name> </author> <contributor> <name>Richard Karnesky</name> <email>karnesky+zotero@gmail.com</email> <uri>http://arc.nucapt.northwestern.edu/Richard_Karnesky</uri> </contributor> <category citation-format="author-date"/> <category field="generic-base"/> <updated>2012-09-14T21:22:32+00:00</updated> <rights license="http://creativecommons.org/licenses/by-sa/3.0/">This work is licensed under a Creative Commons Attribution-ShareAlike 3.0 License</rights> </info> <macro name="zotero2bibtexType"> <choose> <if type="bill book graphic legal_case legislation motion_picture report song" match="any"> <text value="book"/> </if> <else-if type="chapter paper-conference" match="any"> <text value="inbook"/> </else-if> <else-if type="article article-journal article-magazine article-newspaper" match="any"> <text value="article"/> </else-if> <else-if type="thesis" match="any"> <text value="phdthesis"/> </else-if> <else-if type="manuscript" match="any"> <text value="unpublished"/> </else-if> <else-if type="paper-conference" match="any"> <text value="inproceedings"/> </else-if> <else-if type="report" match="any"> <text value="techreport"/> </else-if> <else> <text value="misc"/> </else> </choose> </macro> <macro name="citeKey"> <group delimiter="_"> <text macro="author-short" text-case="lowercase"/> <text macro="issued-year"/> </group> </macro> <macro name="author-short"> <names variable="author"> <name form="short" delimiter="_" delimiter-precedes-last="always"/> <substitute> <names variable="editor"/> <names variable="translator"/> <choose> <if type="bill book graphic legal_case legislation motion_picture report song" match="any"> <text variable="title" form="short"/> </if> <else> <text variable="title" form="short"/> </else> </choose> </substitute> </names> </macro> <macro name="issued-year"> <date variable="issued"> <date-part name="year"/> </date> </macro> <macro name="issued-month"> <date variable="issued"> <date-part name="month" form="short" strip-periods="true"/> </date> </macro> <macro name="author"> <names variable="author"> <name sort-separator=", " delimiter=" and " delimiter-precedes-last="always" name-as-sort-order="all"/> <label form="long" text-case="capitalize-first"/> </names> </macro> <macro name="editor-translator"> <names variable="editor translator" delimiter=", "> <name sort-separator=", " delimiter=" and " delimiter-precedes-last="always" name-as-sort-order="all"/> <label form="long" text-case="capitalize-first"/> </names> </macro> <macro name="title"> <text variable="title"/> </macro> <macro name="number"> <text variable="issue"/> <text variable="number"/> </macro> <macro name="container-title"> <choose> <if type="chapter paper-conference" match="any"> <text variable="container-title" prefix=" booktitle={" suffix="}"/> </if> <else> <text variable="container-title" prefix=" journal={" suffix="}"/> </else> </choose> </macro> <macro name="publisher"> <choose> <if type="thesis"> <text variable="publisher" prefix=" school={" suffix="}"/> </if> <else-if type="report"> <text variable="publisher" prefix=" institution={" suffix="}"/> </else-if> <else> <text variable="publisher" prefix=" publisher={" suffix="}"/> </else> </choose> </macro> <macro name="pages"> <text variable="page"/> </macro> <macro name="edition"> <text variable="edition"/> </macro> <citation et-al-min="10" et-al-use-first="10" disambiguate-add-year-suffix="true" disambiguate-add-names="false" disambiguate-add-givenname="false" collapse="year"> <sort> <key macro="author"/> <key variable="issued"/> </sort> <layout delimiter="_"> <text macro="citeKey"/> </layout> </citation> <bibliography hanging-indent="false" et-al-min="10" et-al-use-first="10"> <sort> <key macro="author"/> <key variable="issued"/> </sort> <layout> <text macro="zotero2bibtexType" prefix=" @"/> <group prefix="{" suffix="}" delimiter=", "> <text macro="citeKey"/> <text variable="publisher-place" prefix=" place={" suffix="}"/> <!--Fix This--> <text variable="chapter-number" prefix=" chapter={" suffix="}"/> <!--Fix This--> <text macro="edition" prefix=" edition={" suffix="}"/> <!--Is this in CSL? <text variable="type" prefix=" type={" suffix="}"/>--> <text variable="collection-title" prefix=" series={" suffix="}"/> <text macro="title" prefix=" title={" suffix="}"/> <text variable="volume" prefix=" volume={" suffix="}"/> <!--Not in CSL<text variable="rights" prefix=" rights={" suffix="}"/>--> <text variable="ISBN" prefix=" ISBN={" suffix="}"/> <text variable="ISSN" prefix=" ISSN={" suffix="}"/> <!--Not in CSL <text variable="LCCN" prefix=" callNumber={" suffix="}"/>--> <text variable="archive_location" prefix=" archiveLocation={" suffix="}"/> <text variable="URL" prefix=" url={" suffix="}"/> <text variable="DOI" prefix=" DOI={" suffix="}"/> <text variable="abstract" prefix=" abstractNote={" suffix="}"/> <text variable="note" prefix=" note={" suffix="}"/> <text macro="number" prefix=" number={" suffix="}"/> <text macro="container-title"/> <text macro="publisher"/> <text macro="author" prefix=" author={" suffix="}"/> <text macro="editor-translator" prefix=" editor={" suffix="}"/> <text macro="issued-year" prefix=" year={" suffix="}"/> <text macro="issued-month" prefix=" month={" suffix="}"/> <text macro="pages" prefix=" pages={" suffix="}"/> <text variable="collection-title" prefix=" collection={" suffix="}"/> </group> </layout> </bibliography> </style>';

    var citeproc = new CSL.Engine(sys, bibtex, 'en');
    //itemIds.forEach(function(id,index) {
        //var citObj ={
            //"citationItems": [{ "id": id }],
            //"properties": {"noteIndex": index}
        //}
        //console.log(citObj);
        //citeproc.appendCitationCluster(citObj);
    //});

    //citeproc.updateItems( itemIds, true );
    citeproc.updateUncitedItems( itemIds, true );

    console.log("items updated");
    var output = citeproc.makeBibliography();
    if (output && output.length && output[1].length){
        output = output[0].bibstart + output[1].join("<br />") + output[0].bibend;
        $('#citation').html('<h4><em>Demo-Feature:</em> The above in BibTeX:</h4>'+ output);
    } else {
        $('#citation').html('<h4>Ooops, somehow citeproc-js failed to produce output</h4>');
    }
});

/*
 * some parts taken from the citeproc-js demo page. license as follows:
 *
 * Copyright (c) 2009, 2010 and 2011 Frank G. Bennett, Jr. All Rights
 * Reserved.
 *
 * The contents of this file are subject to the Common Public
 * Attribution License Version 1.0 (the “License”); you may not use
 * this file except in compliance with the License. You may obtain a
 * copy of the License at:
 *
 * http://bitbucket.org/fbennett/citeproc-js/src/tip/LICENSE.
 *
 * The License is based on the Mozilla Public License Version 1.1 but
 * Sections 14 and 15 have been added to cover use of software over a
 * computer network and provide for limited attribution for the
 * Original Developer. In addition, Exhibit A has been modified to be
 * consistent with Exhibit B.
 *
 * Software distributed under the License is distributed on an “AS IS”
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See
 * the License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is the citation formatting software known as
 * "citeproc-js" (an implementation of the Citation Style Language
 * [CSL]), including the original test fixtures and software located
 * under the ./std subdirectory of the distribution archive.
 *
 * The Original Developer is not the Initial Developer and is
 * __________. If left blank, the Original Developer is the Initial
 * Developer.
 *
 * The Initial Developer of the Original Code is Frank G. Bennett,
 * Jr. All portions of the code written by Frank G. Bennett, Jr. are
 * Copyright (c) 2009 and 2010 Frank G. Bennett, Jr. All Rights Reserved.
 *
 * Alternatively, the contents of this file may be used under the
 * terms of the GNU Affero General Public License (the [AGPLv3]
 * License), in which case the provisions of [AGPLv3] License are
 * applicable instead of those above. If you wish to allow use of your
 * version of this file only under the terms of the [AGPLv3] License
 * and not to allow others to use your version of this file under the
 * CPAL, indicate your decision by deleting the provisions above and
 * replace them with the notice and other provisions required by the
 * [AGPLv3] License. If you do not delete the provisions above, a
 * recipient may use your version of this file under either the CPAL
 * or the [AGPLv3] License.”
 *//*
 * Copyright (c) 2009, 2010 and 2011 Frank G. Bennett, Jr. All Rights
 * Reserved.
 *
 * The contents of this file are subject to the Common Public
 * Attribution License Version 1.0 (the “License”); you may not use
 * this file except in compliance with the License. You may obtain a
 * copy of the License at:
 *
 * http://bitbucket.org/fbennett/citeproc-js/src/tip/LICENSE.
 *
 * The License is based on the Mozilla Public License Version 1.1 but
 * Sections 14 and 15 have been added to cover use of software over a
 * computer network and provide for limited attribution for the
 * Original Developer. In addition, Exhibit A has been modified to be
 * consistent with Exhibit B.
 *
 * Software distributed under the License is distributed on an “AS IS”
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See
 * the License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is the citation formatting software known as
 * "citeproc-js" (an implementation of the Citation Style Language
 * [CSL]), including the original test fixtures and software located
 * under the ./std subdirectory of the distribution archive.
 *
 * The Original Developer is not the Initial Developer and is
 * __________. If left blank, the Original Developer is the Initial
 * Developer.
 *
 * The Initial Developer of the Original Code is Frank G. Bennett,
 * Jr. All portions of the code written by Frank G. Bennett, Jr. are
 * Copyright (c) 2009 and 2010 Frank G. Bennett, Jr. All Rights Reserved.
 *
 * Alternatively, the contents of this file may be used under the
 * terms of the GNU Affero General Public License (the [AGPLv3]
 * License), in which case the provisions of [AGPLv3] License are
 * applicable instead of those above. If you wish to allow use of your
 * version of this file only under the terms of the [AGPLv3] License
 * and not to allow others to use your version of this file under the
 * CPAL, indicate your decision by deleting the provisions above and
 * replace them with the notice and other provisions required by the
 * [AGPLv3] License. If you do not delete the provisions above, a
 * recipient may use your version of this file under either the CPAL
 * or the [AGPLv3] License.”
 */
