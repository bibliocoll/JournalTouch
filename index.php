<?php 
require_once 'sys/jt-gettext.php';
require 'sys/class.ListJournals.php';
/* setup methods & objects */
$lister = new ListJournals();
$journals = $lister->getJournals();
$filters = $lister->getFilters();
?>
<!doctype html>
<!--[if IE 9]><html class="lt-ie10" lang="en" > <![endif]-->
<html class="no-js" lang="en" data-useragent="Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo __('MPI JournalTouch') ?></title>
    <link rel="stylesheet" href="css/foundation.css" />
    <link rel="stylesheet" href="css/local.css" />
    <link rel="stylesheet" href="css/media.css" />
		<link rel="stylesheet" href="foundation-icons/foundation-icons.css" />
    <script src="js/vendor/modernizr.js"></script>
  </head>
  <body>

    <!-- Navigation -->	
		<div class="fixed">	
		<nav class="top-bar" data-topbar data-options="is_hover: false">
			<ul class="title-area">
				<!-- Title Area -->
				<li class="name">
					<h1><?php echo __('JournalTouch <em><strong>beta</strong></em> - a library service') ?></h1>
				</li>
				<li class="toggle-topbar menu-icon"><a href="#"><span><?php echo __('menu') ?></span></a></li>
			</ul>
			
			<section class="top-bar-section">
				<ul class="left hide-for-small">
					<li><a href="#" id="aboutLink" data-reveal-id="about" class="button radius"><i class="fi-info"></i> <?php echo __('about') ?></a></li>
				</ul>
				<!-- Right Nav Section -->
				<ul class="right">
					<li class="divider"></li>
          <?php if (count($lister->tagcloud) > 1) { ?>
					   <li><a href="#" id="myTags" data-reveal-id="tagsPopover"><i class="switcher fi-pencil"></i>&nbsp;<?php echo __('Tags') ?></a></li>
          <?php } ?>
                    <?php if (!empty($filters)) { /* show filters only if set */?>
					<li class="has-dropdown"><a id="filter-view" href="#"><i class="fi-filter"></i>&nbsp;<?php echo __('filter') ?></a>
						<ul class="dropdown">
							<li><a class="filter" id="filter-reset" href="#"><i class="fi-refresh"></i>&nbsp;<?php echo __('show all') ?></a></li>
							<li><a class="filter" id="topJ" href="#"><i class="fi-star"></i>&nbsp;<?php echo __('MPI favorites') ?></a></li>
                            <?php 
                            /* read all filters from the config file (see $lister->getFilters() )*/
                            foreach ($filters as $key=>$f) {
                            print '<li><a class="filter" id="filter-'.$key.'" href="#">'.$f.'</a></li>';
                            }
                            ?>
						</ul>
					</li>
                    <?php } ?>
					<li><a id="switch-view" href="#"><i class="switcher fi-list"></i><span>&nbsp;<?php echo __('list view') ?></span></a></li>
					<li><a href="#" id="myArticles" data-reveal-id="cartPopover"><i class="fi-shopping-bag"></i>&nbsp;<?php echo __('my basket') ?> (<span class="simpleCart_quantity"></span>)</a></li>
					<li class="divider"></li>
          <?php
            // Nasty and only works with two languages ;)
            if (isset($_GET['lang'])) {
              $href_lang = ($_GET['lang'] == 'de_DE') ?  'en_US' : 'de_DE';
            }
            else {
              $href_lang = 'en_US';
            }
            echo "<li><a id=\"switch-language\" href=\"index.php?lang=$href_lang\"><img src=\"locale/$href_lang.gif\" /></a></li>";
            ?>
				</ul>
			</section>
		</nav>
		</div>
		
		<!-- End Top Bar -->

		<!-- Make a sticky basket -->
		<a href="#" id="stickyBasket" class="button radius show-for-large-up" data-reveal-id="cartPopover"><i class="fi-shopping-bag"></i>&nbsp;<?php echo __('Send articles') ?> </a>

		<!-- Make a sticky GoUp -->
		<!-- for large screens -->
		<a href="#" id="stickyGoUpLarge" class="button round show-for-large-up"><i class="fi-arrow-up"></i></a>
    <!-- for small screens -->
		<a href="#" id="stickyGoUpSmall" class="button round hide-for-large-up"><i class="fi-arrow-up"></i></a>

		<!-- About page -->
		<div id="about" class="reveal-modal" data-reveal>
			<div class="row">
				<div class="small-12 medium-12 large-12 columns left">
					<h2><?php echo __('About') ?></h2>
					<p><?php echo __('<em>JournalTouch</em> is the <strong>MPI Collective Goods library\'s</strong> alerting service for newly published journal issues.') ?></p>
					<p>
          <?php echo __('It\'s easy &dash; select a journal and add interesting articles to your shopping basket. If there is an abstract
						available, it will be indicated with an extra button.
						When you are finished, click on your basket to check out.
						You can now send the article information to your e-mail address, send a
						request for the PDF files to the library, or view/save it as a
						list. Export for citation management systems like Endnote is also available. <br/>
						The list of journals is a selection of the journals licensed to the library.') ?>
					</p>
				</div>
			</div>

			<div class="row">
				<div class="small-8 medium-9 large-9 columns left">
					<p>
						<?php echo __('If a journal is missing, please let us know and we will add it to the list.<br/><br/> <strong><em>JournalTouch</em> is actively being developed by the library team</strong>.') ?>
						<br/>	<?php echo __('Tables of contents are provided by <strong>CrossRef</strong> and <strong>JournalTocs</strong>.') ?>
					</p>
				</div>
				
				<div class="small-4 medium-3 large-3 columns right">
					<ul class="inline-list right">
						<li><a class="button radius" href="checkout.php?action=contact"><i class="fi-comment"></i> <?php echo __('Get in touch!') ?></a></li>
					</ul>
				</div>
			</div>
			
				<a class="close-reveal-modal button radius alert">&#215;</a>
		</div>


		<!-- End Header and Nav -->

    <!-- start Tagcloud -->
    <?php if (count($lister->tagcloud) > 1) { ?>
    <div id="tagsPopover" class="reveal-modal" data-reveal>
      <h3><?php echo __('Tagcloud') ?></h3>
      <a class="close-reveal-modal button radius">×</a>
      <p><a class="filter" id="filter-reset" href="#"><i class="fi-refresh"></i>&nbsp;<?php echo __('show all') ?></a></p>
      <?php echo $lister->getTagcloud(); ?>
    </div>
    <?php } ?>
    <!-- end Tagcloud -->
		
		<div id="cartPopover" class="reveal-modal" data-reveal>
			<div class="simpleCart_items"></div>
			<div id="cartData" class="clearfix">
			</div>
			<div id="shelfIsEmpty" style="display:none"><i class="fi-alert"></i> <?php echo __('Your basket is empty.') ?></div>
			<div id="popoverButtons" class="clearfix">
				<a id="checkOutButton" href="javascript:;" class="simpleCart_checkout radius small success button"><i class="fi-share"></i> <?php echo __('Send/Save my articles') ?></a>
				<!--<a id="emptyCartButton" href="javascript:;" class="simpleCart_empty radius small alert button"><i class="fi-trash"></i> Empty my basket</a>-->
				<a id="emptyConfirmButton" class="radius small alert button" data-reveal-id="emptyConfirm"><i class="fi-trash"></i> <?php echo __('Empty my basket') ?></a>
				<a class="close-reveal-modal button radius">&#215;</a>
			</div>
		</div><!--End #cartPopover-->
		
		<!-- Security confirmation on delete -->
		<div id="emptyConfirm" class="reveal-modal" data-reveal>
			<h3><?php echo __('Do you really want to empty your basket?') ?></h3>
			<a id="emptyCartButton" href="javascript:;" class="simpleCart_empty radius small alert button close-reveal-modal"><i class="fi-trash"></i> <?php echo __('OK, empty my basket!') ?></a>
			<a id="DoNotemptyCartButton" class="radius small success button close-reveal-modal"><i class="fi-trash"></i> <?php echo __('No, keep basket!') ?></a>
		</div>

		<!-- First Band (Slider) -->

<!--
		<div id="view-orbit">
			<div class="row">

				<div class="large-12 columns" style="padding-top:20px">
					<h3><?php echo __('The newest editions') ?></h3>
					<?php // print date('M d Y',strtotime('-7 day'))." - " .date('M d Y',strtotime('today'));  ?>
				</div>
			</div>

			<div class="row">
				<hr/>	
				<div class="medium-3 large-2 columns hide-for-small logo">
					&nbsp;
				</div>

				<div class="medium-5 large-7 small-11 columns slideshow-wrapper">
					<div class="preloader"></div>
			
					<ul id="myorbit" data-orbit data-options="animation_speed: 1000;timer_speed: 3000;bullets: false">

          <?php

					/* /\* see Class setup *\/ */
					 	 foreach ($journals as $j) {
					 	   if (!empty($j['topJ'])) {
					 	     echo '<li data-orbit-slide="headline">';
					 	     echo '<img class="issn getTOC" id="'.$j['id'].'" src="'.$j['img'].'"/>';
					 	     echo '<div class="orbit-caption">'.$j['title'].'</div>';
					 	     echo '</li>';
					 	   }
					 	 }
					?>

					</ul>
				</div>
				<div class="medium-4 large-3 columns">
					<div class=" panel radius callout" id="filterPanel" style="display:none">
						<i class="fi-filter"></i>&nbsp; Filter active: <span id="filterPanelFilter"></span>
					</div>
				</div>
			</div>
			
			<hr/>
-->			
			<!-- Three-up Content Blocks -->
			
			<div class="row">
				<div id="TOCbox" class="small-12 columns">		

					<!-- A-Z button bar -->

					<div class="button-bar alphabet">
						<ul class="button-group radius">
							<?php
								 $alphas = range('A', 'Z');
								 foreach ($alphas as $letter) {
                                     echo '<li><a href="#" class="tiny button secondary">'.$letter.'</a></li>';
								 }
								 ?>
						</ul>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="panel radius callout" id="filterPanel" style="display:none">
					<i class="fi-filter"></i>&nbsp; <?php echo __('Filter active:') ?> <span id="filterPanelFilter"></span>
				</div>
			</div>

			<form id="search-form">
				<div class="row">
					<div class="small-12 columns">
							<fieldset>
								<label class="error">
									<input id="search" type="text" id="right-label" placeholder="<?php echo __('Search journal') ?>">
								</label>
								<small id="noresults" class="error" style="display:none"><?php echo __('No journals found') ?></small>
							</fieldset>
					</div>
				</div>
			</form>
		
		<!-- Version 2: List -->
		<div id="view-accordion" class="row invisible">
			<div class="small-12 columns">

				<h3><?php echo __('Browse all journals from A to Z (list view):') ?></h3>
				<dl class="accordion" data-accordion>
				<?php
        /* see Class setup */
           foreach ($journals as $j) {
               /* convert found date of last update in the data to a timestring (gets evaluated with jquery.timeago.js) */
               $timestring = date('c', strtotime($j['date'])); // 
               $wF = '<time class="timeago" datetime="'.$timestring.'">'.$timestring.'</time>';
					   echo '<dd class="search-filter filter-'.$j['filter'].' '.$j['tags'].' '.$j['topJ'].'">';
					   echo '<a id="'.$j['id'].'" class="getTOC accordion '.$j['id'].'" href="#issn'.$j['id'].'">';
					   echo (!empty($j['new']) ? ' <i class="fi-burst-new large"></i>' : "");
					   echo $j['title']; 
					   echo (!empty($j['new']) ? ' <span class="fresh">['.__("last update") .' '. $wF . ']</span>' : "");
					   echo '</a>';
					   echo '<div id="issn'.$j['id'].'" class="content"><div class="toc preloader"></div></div>';
					  echo '</dd>';
					 }
				?>
				</dl>
			</div>
		</div>
		
		<!-- Version 3: Grid -->
    <!-- Thumbnails -->
		<div id="view-grid">

			<div class="row">
				<div class="small-12 columns">
          <h3><?php echo __('Browse all journals from A to Z (grid view):') ?></h3>
				</div>
			</div>

      <div class="row">


				<?php
					 /* see Class setup */
					    foreach ($journals as $j) {
                            /* convert found date of last update in the data to a timestring (gets evaluated with jquery.timeago.js) */
                            $timestring = date('c', strtotime($j['date'])); // 
                            $wF = '<time class="timeago" datetime="'.$timestring.'">'.$timestring.'</time>';

					      echo '<div class="search-filter large-4 medium-5 small-12 columns div-grid filter-'.$j['filter'].' '.$j['tags'].' '.$j['topJ'].'">';
                          echo '<img class="getTOC grid '.$j['id'].'" id="'.$j['id'].'" src="img/lazyloader.gif" data-src="'.$j['img'].'">';
					      echo (!empty($j['new']) ? '<i class="fi-burst-new large"></i>' : "");
					      echo '<div id="issn'.$j['id'].'" class="getTOC grid panel content">'; 
					      echo '<h5 title="'.$j['title'].'">'.$j['title'].'</h5>';
					      echo (!empty($j['new']) ? '<h6 class="subheader"> <span class="fresh">['.__("last update") .' '. $wF . ']</span> </h6>' : "");
					      echo '</div></div>';
					    } 
					 ?>

      </div><!-- End Thumbnails -->


      <!-- Box für Toc-Inhalte, z.B. genutzt von Grid (s. js) -->
      <div id="tocModal" class="reveal-modal xlarge" data-reveal>
				<div class="toc preloader"></div>
				<!-- This alert box will be switched on if something goes wrong (see conduit.js) -->
				<div data-alert id="tocAlertBox" class="alert-box warning invisible">
					<span id="tocAlertText"><?php echo __('Something seems to be wrong with the network') ?></span>
					<a class="button radius" href="checkout.php?action=contact&message=Feed%20error%20report&body=Error%20report%20from%20JournalTouch%20for%20ISSN:%200000-0000" class="button">
						<i class="fi-mail"></i>&nbsp;<?php echo __('Please notify us!') ?>
					</a>
				</div>
				<!-- This alert box will be switched on if no tocs are found (see conduit.js) -->
				<div data-alert id="tocNotFoundBox" class="alert-box info invisible">
					<span id="tocAlertText"><?php echo __('No table of contents found! Are you interested in this title?') ?></span>
					<a class="button radius" href="checkout.php?action=contact&message=The%20table%20of%20contents%20for%20this%20journal%20seems%20to%20be%20missing%20for%20ISSN:%200000-0000">
						<i class="fi-comment"></i> <?php echo __('Please notify us!') ?>
					</a>
				</div>
				<a class="close-reveal-modal button radius alert">&#215;</a>
      </div>

		</div>


		<footer>
      <div class="row">
				<div class="small-12 columns">

					<div class="large-6 columns">
            <p><em>JournalTouch</em> &copy; 2014 MPI Collective Goods Library, Bonn</p>
						<p><?php echo __('Tables of contents provided by <a href="http://www.crossref.org">CrossRef</a> and <a href="http://www.journaltocs.ac.uk/">JournalTocs</a>') ?></p>

					</div>
					<div class="large-6 columns">
						<ul class="inline-list right">
							<li><a class="button radius" href="checkout.php?action=contact"><i class="fi-comment"></i> <?php echo __('Get in touch!') ?></a></li>
						</ul>
					</div>
				</div>
			</div> 
		</footer>

		<!-- a fancy screensaver when screen is idle (see css for switching) -->
<!-- TEMP DISABLE TZ
		<div id="screensaver" style="display:none">
						<div class="row">
				<div class="small-12 medium-12 large-12 columns left">
					<h1><?php echo __('Touch me!') ?></h1>
					<h2><?php echo __('What is this?') ?></h2>
					<p><?php echo __('<em>JournalTouch</em> is the <strong>MPI Collective Goods library\'s</strong> alerting service for newly published journal issues.') ?></p>
					<p>
          <?php echo __('It\'s easy &dash; select a journal and add interesting articles to your shopping basket. If there is an abstract
						available, it will be indicated with an extra button.
						When you are finished, click on your basket to check out.
						You can now send the article information to your e-mail address, send a
						request for the PDF files to the library, or view/save it as a
						list. Export for citation management systems like Endnote is also available. <br/>
						The list of journals is a selection of the journals licensed to the library.') ?>
					</p>
				</div>
			</div>

			<div class="row">
				<div class="small-8 medium-9 large-9 columns left">
					<p><?php echo __('If a journal is missing, please let us know and we will add it to the list.') ?><br/><br/> <?php echo __('<strong><em>JournalTouch</em> is actively being developed by the library team.</strong>') ?><br/>
						<?php echo __('Tables of contents are provided by <strong>CrossRef</strong> and <strong>JournalTocs</strong>.') ?>
					</p>
				</div>
			</div>

			<div class="row">
				<h1><?php echo __('Touch the screen to get started...') ?></h1>
			</div>

			<div class="row">
				<p class="text-center"><img src="img/bgcoll-logo.png"></img></p>
			</div>
-->
			<!-- end screensaver -->

    <script src="js/vendor/jquery.js"></script>
    <script src="js/foundation.min.js"></script>
    <script src="js/local/simpleCart.custom.js"></script>
    <script src="js/vendor/jquery.unveil.min.js"></script>
    <script src="js/vendor/waypoints.min.js"></script>
    <script src="js/vendor/jquery.timeago.js"></script>
    <script src="js/local/conduit.js"></script>
    <script src="js/vendor/jquery.quicksearch.min.js"></script>
    <script>
			simpleCart({
				checkout: {
						type: "SendForm",
						url: "checkout.php"
				},
				cartColumns: [
						{ attr: "name" , label: "Name" } ,
						{ view: "remove" , text: "<?php echo __('remove') ?> <i class=\"fi-trash\"></i>" , label: false }
				]
		});

      simpleCart.bind( 'beforeRemove' , function(){
 			  if ($(".row-1").length == false) {
				  $("#shelfIsEmpty").show();
				  $('#checkOutButton, #emptyCartButton, #emptyConfirmButton').hide();
		    } else {
				  $('#checkOutButton, #emptyCartButton, #emptyConfirmButton').show();
    		}
      });

		</script>
    <script>
      $(document).foundation();

      var doc = document.documentElement;
      doc.setAttribute('data-useragent', navigator.userAgent);
    </script>

  </body>
</html>
