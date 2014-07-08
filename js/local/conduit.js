/*!
 * jQuery conduit.js 
 * Interface actions for JournalTouch
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 *
 * Time-stamp: "2014-04-10 14:38:04 zimmel"
 *
 */

$(document).ready(function() {

/* run unveil plugin */
		
		$("img").unveil();


/* Alphabet button bar */
/* currently for grid view only */

		$('.alphabet a').click(function() {
				var char = $(this).text();
			//	$('html,body').animate({scrollTop: $('.getTOC').find('h5[title^="'+char+'"]').offset().top},'slow');	
				$('html,body').animate({scrollTop: $('#view-grid div.div-grid').filter(':visible').find('h5[title^="'+char+'"]:first').parent().parent().offset().top},'slow');
		});

		/* modify alphabet */
		/* currently limited to grid view */
		function showActiveLettersOnly() {
				if ($('#view-grid').is(':visible')) {
						Letters = new Array();
						$('dd:visible, div.div-grid:visible').each(function() {
								var title = $(this).find('.getTOC h5').attr('title');
								/* push the first letters of all displayed journals into an array */
								Letters.push(title.slice(0,1));
						});
						$('.alphabet li a').each(function() {
								var letter = $(this).text();
								if ($.inArray(letter, Letters) === -1) {
										$(this).hide();
								}
						});
				}

		}

		showActiveLettersOnly();

		/* Highlight letter in viewport (depends on Waypoints jQuery plugin) */
		/* works only in grid view! */
		function setLetterBox() {
				/* set up a letterbox with default letter 'A' */
				$('#letterbox').remove();
				$('#view-grid').append('<div id="letterbox" class="secondary radius button disabled">A</div>');
				/* animate box */
				if ($('#view-grid').is(':visible')) {
						$('div.div-grid h5').waypoint(function(direction) {
								// get the first letter 
								var cL = $(this).attr('title').slice(0,1);
								$('#letterbox').text(cL);
								//alert('Top of thing hit top of viewport.');
						}, { offset: '50%' });
				}
		}

		setLetterBox();
		
/* animate the GoUp button */

		$('#stickyGoUp').click(function() {
				$('html, body').animate({ scrollTop: 0 }, 'fast');
		});

/* click on a journal */
													 
		$('.getTOC').click(function() {

				$('#fillTOC').remove(); // clean up
				$('#tocAlertBox, #tocNotFoundBox').hide(); // clean up
				if ($(this).is('.panel')) { var issn = $(this).prevAll('img').attr('id').trim();
				} else {
				var issn = $(this).attr('id').trim();
				}
				// append current issn to error boxes by default
				$('#tocModal div.alert-box a').each(function() {
						var _href = $(this).attr('href');
						// cut any ISSN value from end of string (9 chars) and append new ISSN
						$(this).attr('href', _href.substring(0, _href.length - 9) + issn);
				});
				
				cur = $(this);
				// check where the user is coming from, accordion or orbit slider
				if ($(this).is('.accordion')) { accordion = true; grid = false; } 
				else if ($(this).is('.grid')) { grid = true; accordion = false; } 
				else {accordion = false; grid = false;} // set global for later
				$('#TOCboxIntro').remove(); 
				$('.toc.preloader').show();
				if (accordion) { 
					//	$('#fi-x').remove();
					//	$(this).append('<span id="fi-x">&nbsp;(<i class="fi-x" style="margin-right:0px"></i>)</span');
						/* add an unobtrusive marking for clicked items */
						$(this).css('font-style','italic');
						$(this).next('div.content').append('<div id="fillTOC" style="display:none"></div>');
						// if ($(this).parents('dd').hasClass('active')) {	
						// 		$(this).css('font-weight','normal');
						// 		//	$('#fi-x').remove();
						// }
				} else if (grid) {
						$('#tocModal').append('<div id="fillTOC" style="display:none"></div>');
						$('#tocModal').foundation('reveal', 'open');
				} else { /* then it's orbit */
						$('#fi-x-orbit').remove();
						$('#TOCbox').append('<div id="fillTOC" style="display:none"></div>');
						$('#fillTOC').append('<h4><span id="fi-x-orbit">&nbsp;(<i class="fi-x" style="margin-right:0px"></i>)</span></h4>');
				}

				/* get Journal TOC */

				$.ajax({
						url: 'ajax/getJournalTOC.php', /* first call */
						data: {'issn' : issn},
						timeout: 5000 /* set default timeout of 5 sec. */
				}).done(function(returnData) {


						/* fire a second call, if first call is empty */
						/* uncomment, if you do not want a second call; see also below for network failure events ("ajax.fail")*/
						if ($(returnData).filter('#noTOC').length > 0) {
								$.ajax({
										url: 'ajax/getCrossRefTOC.php', /* JournalTocs */
										data: {'issn' : issn}
								}).done(function(returnData) {
										$('.toc.preloader').fadeOut('slow');
										if ($(returnData).filter('#noTOC').length > 0) {
												$('#tocNotFoundBox').fadeIn('slow');
										}
										$('#fillTOC').append(returnData).fadeIn('slow');
										/* timestamp setup: render timestamps for all 'time' elements with class 'datetime' that has an ISO 8601 timestamp */
										$('time.timeago').timeago();
										if (accordion) {
												$("html,body").animate({scrollTop: $('#fillTOC').offset().top},'slow');	
										}	
								}).fail(function() {
										$('#tocModal .preloader').hide();
										$('#tocAlertBox').fadeIn('slow');
								});
						} else {	
						$('.toc.preloader').fadeOut('slow');
						}
						$('#fillTOC').append(returnData).fadeIn('slow');
						/* timestamp setup: render timestamps for all 'time' elements with class 'datetime' that has an ISO 8601 timestamp */
						$('time.timeago').timeago();
						if (accordion) {
						$("html,body").animate({scrollTop: $('#fillTOC').offset().top},'slow');	
						}	

						
				}).fail(function(j,t,m) {
						if(t==="timeout") {
								$.ajax({
										url: 'ajax/getCrossRefTOC.php', /* JournalTocs */
										data: {'issn' : issn}
								}).done(function(returnData) {
										$('.toc.preloader').fadeOut('slow');
										if ($(returnData).filter('#noTOC').length > 0) {
												$('#tocNotFoundBox').fadeIn('slow');
										}
										$('#fillTOC').append(returnData).fadeIn('slow');
										/* timestamp setup: render timestamps for all 'time' elements with class 'datetime' that has an ISO 8601 timestamp */
										$('time.timeago').timeago();
										if (accordion) {
												$("html,body").animate({scrollTop: $('#fillTOC').offset().top},'slow');	
										}	
								}).fail(function() {
										$('#tocModal .preloader').hide();
										$('#tocAlertBox').fadeIn('slow');
								});
								
						} else {
								
								$('#tocModal .preloader').hide();
								$('#tocAlertBox').fadeIn('slow');
						}
				});

				/* add a fancy paperclip as a reminder what the user has clicked in this session */ 
				// $('.'+issn).each(function() {
				// 		if (($(this).hasClass('accordion'))) {
				// 				$(this).children('.fi-paperclip').remove();
				// 				$(this).prepend('<i class="fi-paperclip large"></i>');		
				// 		} else {
				// 				$(this).prev('.fi-paperclip').remove();
				// 				$(this).before('<i class="fi-paperclip large"></i>');						 
				// 		}																	
				// });

		});

/* special click on close icon (Orbit Toc only) */
		$(document).on("click","#fi-x-orbit",function() {
				$('#fillTOC').remove();
				$(this).remove();
		});

/* check on each Ajax-Call (gettoc) if item is already in cart */
		$(document).ajaxComplete(function(){
				
				$(".item_name").each(function() {
						htmlLink = $(this).text();
						curItem = $(this);
						simpleCart.each(function(item){
								var cartItem = item.get('name');
								if (htmlLink == cartItem) {
										var myButton = $(curItem).parent().parent().next().children('a.item_add');
										$(myButton).removeClass('item_add').addClass('item_added');
										$(myButton).children('i').removeClass("fi-plus").addClass("fi-check");
								}
						});
				});
		});

/* click action for fulltext link from toc */
/* open links in popup (handy when you have a touchscreen in fullscreen mode, but not imperative!) */ 
		// $(document).on("click","a.item_name",function() {
		// 		event.preventDefault();
    //     event.stopPropagation();
    //     window.open(this.href, 'targetWindow', 'left=20,top=20,width=800,height=600,toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=no,resizable=0');
		// });

/* try to find an abstract on the link (dirty screenscraping, HIGHLY EXPERIMENTAL) */
		// $(document).on("click","a.item_name",function() {
		// 		event.preventDefault();
    //     event.stopPropagation();
		// 		var link = $(this).attr("href");alert(link);
		// 		blob = $(this);
		// 		$.ajax({
		// 				url: 'ajax/getAbstract.php', 
		// 				data: {'link' : link }
		// 		}).done(function(returnData) {
		// 				$(blob).append('<p>'+returnData+'</p>');
		// 				//
		// 		}).fail(function() {
		// 				//
		// 		});
		// });
		

/* toggle abstracts - we need "on" to access the ajax loaded content, "element.click" will not work */

		$(document).on("click","a.abstract",function() {
			//	$(this).parent().prev().children("div.abstract").toggle("easeOutCubic");
				$(this).parent().next("div.abstract").fadeToggle();
		});

/* add to cart fancy schmancy - we need "on" to access the ajax loaded content, "element.click" will not work */
		
		$(document).on("click",".item_add",function(){
				$(this).removeClass("item_add");
				$(this).addClass("item_added");
				$(this).children('i').removeClass("fi-plus").addClass("fi-check");
		});

/* remove from cart in HTML - we need "on" to access the ajax loaded content, "element.click" will not work */
		
		$(document).on("click",".item_added",function(){
				$(this).removeClass("item_add");

						htmlLink = $(this).parent().prev().children(".toctitle").children(".item_name").text();

						simpleCart.each(function(item){
								var cartItem = item.get('name');
								if (htmlLink == cartItem) {
										item.remove();
								}
						});
				$(this).removeClass("item_added");
				$(this).addClass("item_add");
				$(this).children('i').removeClass("fi-check").addClass("fi-plus");
		});

/* filter */

		$('a.filter').click(function(){
        /* remove any open modal */
        $('.reveal-modal').foundation('reveal', 'close');
				/* reset alphabet */
				$('.alphabet li a').show();
				var curFilter = $(this).attr('id');
				/* highlight current filter */
				$(this).parent().siblings().children().removeClass('filterSelected');
				$(this).addClass('filterSelected');
				if (curFilter == "filter-reset") {
						$('dd, div.div-grid').show(); /* show everything */
						/* fix for unveil.js so all visible elements will get their appropriate image content 
						 * (because lazy load works only on scroll, we will scroll a bit) */
						$('html,body').animate({scrollTop: $('#switch-view').offset().top},'slow');
						/* hide on panel */
						$('#filterPanel').fadeOut();
				} else {
						$('dd, div.div-grid').not('.'+curFilter).hide();
						$('.'+curFilter).show();
						/* special handling for some data (we should really do this in the data, not here) */
						if (curFilter == "filter-wir") {$('.filter-fin').show();}
						/* trigger for unveil.js: show all filtered images */
						$('.'+curFilter+ '> img').trigger('unveil');
						/* show on panel */
						$('#filterPanel').fadeIn();
						$('#filterPanelFilter').text($(this).text());
				}

				/* reload values */
				showActiveLettersOnly();
				setLetterBox();

		});

/* filter: deactivate panel on click */

		$('#filterPanel').click(function() {
				$('dd, div.div-grid').show();
				$('.alphabet li a').show(); /* show everything */
				showActiveLettersOnly();
				setLetterBox();
				/* hide on panel */
				$('#filterPanel').fadeOut();

		});

/* switch views */
		$('#switch-view').click(function(){
				$('.alert-box').hide(); // clean up
				$('#view-accordion,#view-grid').fadeToggle('linear');
				if ($(this).children('i').hasClass('fi-list')) { 
						$(this).children('i').removeClass('fi-list').addClass('fi-thumbnails');
						$(this).children('span').html('&nbsp;grid view');
						/* deactivate alphabet button bar */
						$('.alphabet').hide();
				}
				else if ($(this).children('i').hasClass('fi-thumbnails')) { 
						$(this).children('i').removeClass('fi-thumbnails').addClass('fi-list');
						$(this).children('span').html('&nbsp;list view');
						/* deactivate alphabet button bar */
						$('.alphabet').show();
						/* fix for unveil.js so all visible elements will get their appropriate image content 
						 * (because lazy load works only on scroll, we will scroll a bit) */
						$('html,body').animate({scrollTop: $('#switch-view').offset().top},'slow');
				}
		});

/* check if there are any items in cart on opening */

		$('#myArticles').click(function(){
				if ($(".row-0").length == false) {
						$("#shelfIsEmpty").show();
						$('#checkOutButton, #emptyCartButton, #emptyConfirmButton').hide();
				} else {
						$("#shelfIsEmpty").hide();
						$('#checkOutButton, #emptyCartButton, #emptyConfirmButton').show();
				}
		});


/* checkout functions (checkout.php) */

		$('#actions .button:not(#emptyCartConfirm)').click(function() {
				$('.alert-box').hide(); // cleanup 
				var clickedId = $(this).attr('id');
				/* fill in a hidden field with the chosen action for POST */
				$('form[name="Request"] input[name="action"]').val(clickedId);
				var thisText = $(this).text();
				var aGr = $('#actionGreeter h1').text();
				var aGrChop = aGr.substring(0, aGr.length - 3);
				/* display chosen action as a heading */
				$('#actionGreeter h1').text(thisText);
				/* hide other buttons */
				$(this).siblings().hide(); $(this).hide();
				/* show reset buttons */
				$('#resetActions, #emptyCart').show();
				/* hide everything inside our box without the class */;
			//	$('div#actionsResultBox').find('div').show();
				$('.'+clickedId).find("*").not(".error").show();
				$('div#actionsResultBox').show();
				/* show everything which has the clicked id as a class */
				$('.'+clickedId).show('fast');
				/* show the mailForm if it's in the class */
				if ($(this).hasClass('mailForm')) { $('#mailForm').show('fast');}
				/* scroll on small screens */
				if ($('div#actionsResultBox').is(":visible")) {
						$("html,body").animate({scrollTop: $('div#actionsResultBox').offset().top},'slow');
				}	
		});

		$('#resetActions').click(function() {
				$('.alert-box').hide(); // cleanup 
				$('#actionGreeter h1').text("I want to...");
				$('#actionsResultBox').find('div').hide();
				$('#actionsResultBox').hide(); // clean
				$(this).hide();
				$('#actions .button:not(.reset)').fadeIn("slow");
		});

		$('#emptyCart').click(function() {
				simpleCart.empty();
				$('.alert-box').show();
				$('#emptyCartSuccess').fadeIn("slow");
				$('#actionsResultBox, #actions').fadeOut("fast");
				/* fade out and redirect after short time */
				setTimeout(function(){$('#emptyCartSuccess').fadeOut();window.location.replace("index.php")},3000);
		});

		/* checkout: check form for valid entries */
		$('form').submit(function () {
				
				// Get the Login Name value and trim it
				var name = $.trim($('*[name="username"]').val());
				
				// Check if empty of not
				if (name  === '-please select your account-' || name == '') {
						$('#errorUsername').prev('label').addClass('error');
						$('#errorUsername').text('please choose a name').toggle();
						return false;
				}
				
				// Check if full domain is given (default: domain gets added by system)
				if(name.indexOf('@') > -1) {
						$('#errorUsername').prev('label').addClass('error');
						$('#errorUsername').text('please enter your account name only (without the @domain)').toggle();
						return false;
				}
		});

		/* checkout: reset any error messages on change */
		$('form select').change(function() {
				$('#errorUsername').hide();
		});
		

/* screensaver-like thing with timeout (large screens only, see media.css) */
		var s_saver;
		$('body').mousedown(function() {
				clearTimeout(s_saver);
				s_saver = setTimeout(function(){
										$('#screensaver').fadeIn(900);
				}, 30000);
				$('#screensaver').fadeOut(100);
		});



/* timestamp setup: render timestamps for all 'time' elements with class 'datetime' that has an ISO 8601 timestamp */
		$('time.timeago').timeago();


/* quicksearch setup */
		$('input#search').quicksearch('.search-filter', {
				/* trigger for unveil.js so _all_ elements will get their appropriate image content */
				/* (note: 'show' works, but breaks: why? */
				// 'show': function () { $(this).addClass('show'); },
				// 'hide': function () { $(this).removeClass('show'); }
				'minValLength': 2,
				'onValTooSmall': function (val) {
            $('#filterPanel').fadeOut();
            $('h3.view-heading').toggle();
				},
				'noResults': '#search-form #noresults',
				'onAfter': function() {
						$('.search-filter img').trigger('unveil');}
		});

/* Open web link in popup */
    $('a.popup').click(function(event) {
      var url = $(this).attr("href");
      var dHeight = $(window).height() -280;

      $('#externalPopover').foundation('reveal', 'open');
      $("#externalFrame").height(dHeight);
      $("#externalFrame").attr('src','about:blank'); // clear previously loaded page;
      setTimeout(function() {
        $("#externalFrame").attr('src',url);
      }, 100);
      return false;
    });

});