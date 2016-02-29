/*!
 * jQuery conduit.js
 * Interface actions for JournalTouch
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @author Alexander Krug <krug@coll.mpg.de>
 * @copyright 2014-2015 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 *
 * Time-stamp: "2014-04-10 14:38:04 zimmel"
 *
 */

function updateCartItemWithCitation( item, cit, cart ) {
	item.set('cite', cit);
	item.set('citestr', JSON.stringify(cit));
	var modified = false;

	if (cit.hasOwnProperty('author') && cit.author.length > 0) {
		var newtitle = '';
		newtitle = cit.author[0].family;
		newtitle += (cit.author.length > 1? ', et al.: ' : ': ')+ cit.title;
		if (cit.hasOwnProperty('container-title')) {
			newtitle += ' ('+cit['container-title']+')';
		}
		item.set('title', newtitle);
		modified = true;
	}
	modified && cart.update();
}


// dynamically create iframe for toc and links
function createModalFrame(href) {
	var dHeight = $(window).height() -280;

	// If the iframe is part of the html, browsers add any click to the browser history. Bad idea, so create it dynamically.
	$('#externalPopover').append('<iframe src="" id="externalFrame" name="externalFrame" scrollbars="yes"></iframe>');
	$("#externalFrame").attr('src',href);
	$("#externalFrame").height(dHeight);

	// Save current history length in data attribute. This way the back button only works for the iframe
	// Todo: Hide it initially and only display button if user follows some link in frame
	$("#frameBack").data("history", history.length);
	//$("#frameBack").hide();
}


// modify alphabet; currently limited to grid view
function showActiveLettersOnly() {
	if ($('#journalList.gridview').is(':visible')) {
		Letters = new Array();
		$('div.listitem .title h5').each(function() {
			var title = $(this).attr('title');
			// push the first letters of all displayed journals into an array
			Letters.push(title.slice(0,1));
		});
		$('#alphabet li a').each(function() {
			var letter = $(this).text();
			if ($.inArray(letter, Letters) === -1) {
				$(this).hide();
			}
		});
	}
}


/*  Highlight letter in viewport (depends on Waypoints jQuery plugin)
	works only in grid view! */
function setLetterBox() {
	// set up a letterbox with default letter 'A'
	$('#letterbox').remove();
	$('#journalList').append('<div id="letterbox" class="secondary radius button disabled">A</div>');
	// animate box
	if ($('#journalList.gridview').is(':visible')) {
		$('div.listitem h5').waypoint(function(direction) {
			// get the first letter
			var cL = $(this).attr('title').slice(0,1);
			$('#letterbox').text(cL);
			//alert('Top of thing hit top of viewport.');
		}, { offset: '50%' });
	}
}


function fetch_metabuttons_toc(issn) {
    // Append meta (ugly hack, or maybe not that bad...?)
    var custom_button = '<a onlick="window.history.go(-100)" class="button"><i class=""></i> Home</a> ';
    $('#externalPopover h3').html($("div[data-issn='"+issn+"'] h5").clone()); // get title
    $('#externalPopover h3').after($("div[data-issn='"+issn+"'] .metaInfo").clone()); // show button
    $('#externalPopover .metaInfo').removeClass('hidden'); // remove the hidden class (if the buttons are not shown in the list)
    $('#externalPopover .metaInfo a').removeClass('popup'); // remove popup class from buttons
    $('#externalPopover .metaInfo a').attr('target', 'externalFrame'); // on click show content in frame
    $('#externalPopover .metaInfo').prepend(custom_button); // add a home button

    // Add inline back button
    backlink = '<a id="frameBack_inline" class="button round" data-history="0" onclick="if ($(this).data(\'history\') < history.length) history.go(-1)"><i class="fi-arrow-left"></i></a> ';
    $('#externalPopover .metaInfo').prepend(backlink);

    return true;
}


$(document).ready(function() {
	// run unveil plugin on page load
	setTimeout(function() {$("img.getTOC").unveil();}, 1);

	// Alphabet button bar;  currently for grid view only
	$('#alphabet a').click(function() {
		var char = $(this).text();
		$('html,body').animate({scrollTop: $('.listitem').filter(':visible').find('h5[title^="'+char+'"]:first').parent().parent().offset().top - 80},'slow');
	});

	// Prepare alphabet buttons
	showActiveLettersOnly();
	setLetterBox();

	// Time frame buttons
	$('#timewarps a').click(function() {
		var timestamp = $(this).data('timestamp');
		$('html,body').animate({scrollTop: $('.listitem').filter(':visible').filter( function(i,e){ return ($(e).data('pubdate') < timestamp)} ).first().offset().top - 80 },'slow');
	});

	// Switching between alphabet and time frame buttons (whatever is the default view)
	if ($('#journalList').hasClass('azsorted')) {
		$('#alphabet').toggle();
	} else {
		$('#timewarps').toggle();
	}

	// Anything that should be done on closing a modal
	// Currently only removing iframe
	$(document).on('close.fndtn.reveal', '[data-reveal]', function () {
		// Remove iframe to remove its content from browsing history
		$('#externalFrame').remove();
        $('#externalPopover .metaInfo').remove();
	});

	// animate the GoUp button
	$('#stickyGoUp').click(function() {
		$('html, body').animate({ scrollTop: 0 }, 'fast');
	});

	// click on a journal
	$('.getTOC, .cr_getTOC').click(function(event) {
        event.preventDefault();
		$('#fillTOC').remove(); // clean up
		$('#tocAlertBox, #tocNotFoundBox').hide(); // clean up

        // Classic case JournalTocs
        if ($(this).hasClass('getTOC')) {
    		// Get data for specific journal
    		var issn	= $(this).prevAll('span').attr('data-issn').trim();
    		var pubdate = $(this).prevAll('span').attr('data-pubdate');
        }
        // 2015-11-28: For CrossRef we can't show the toc on crossref.org (like
        // we do for JournalTocs by clicking on the Toc button). Show always
        // parsed toc - @todo find way to show CR in iframe?
        else {
    		var issn	= $(this).parent().prevAll('span').attr('data-issn').trim();
    		var pubdate = $(this).parent().prevAll('span').attr('data-pubdate');
        }

		// Check if cache is enabled
		var para_caching = ($('body').attr('data-caching') === '1') ? '&cache=1' : '';
		var para_pubdate = (pubdate !== '') ? '&pubdate='+ pubdate : '';

		// append current issn to error boxes by default
		$('#tocModal div.alert-box a').each(function() {
			var _href = $(this).attr('href');
			// cut any ISSN value from end of string (9 chars) and append new ISSN
			$(this).attr('href', _href.substring(0, _href.length - 9) + issn);
			//TODO: GET AGE SOMEHOW TOO
		});

		// Show loading animation
        $('#tocModal').foundation('reveal', 'open');
		$('.toc.preloader').show();

        // Load meta buttons above toc (if visible)
        fetch_metabuttons_toc(issn);

		// get Journal TOC in iframe
		createModalFrame('sys/ajax_toc.php?issn='+ issn + para_caching + para_pubdate);
	});

	// special click on close icon (Orbit Toc only)
	$(document).on("click","#fi-x-orbit",function() {
		$('#fillTOC').remove();
		$(this).remove();
	});

	// filter
	$('a.filter').click(function(){
		//[> remove any open modal <]
		$('.reveal-modal').foundation('reveal', 'close');
		//[> reset alphabet <]
		$('#alphabet li a').show();
		var curFilter = $(this).attr('id');
		//[> highlight current filter <]
		$(this).parent().siblings().children().removeClass('filterSelected');
		$(this).addClass('filterSelected');
		if (curFilter === "filter-reset") {
			$('.listitem').show(); //[> show everything <]
			//fix for unveil.js so all visible elements will get their appropriate image content
			//(because lazy load works only on scroll, we will scroll a bit)
			setTimeout(function() {window.scrollBy(0,1);}, 500);
			//[> hide on panel <]
			$('#filterPanel').fadeOut();
		} else {
			$('.listitem').not('.'+curFilter).hide();
			$('.'+curFilter).show();
			//[> special handling for some data (we should really do this in the data, not here) <]
			if (curFilter === "filter-wir") {$('.filter-fin').show();}
			//[> trigger for unveil.js: show all filtered images <]
			$('.'+curFilter+ '> img').trigger('unveil');
			//[> show on panel <]
			$('#filterPanel').fadeIn();
			$('#filterPanelFilter').text($(this).text());
		}
		//[> reload values <]
		showActiveLettersOnly();
		setLetterBox();
	});

	// filter: deactivate panel on click
	$('#filterPanel').click(function() {
		$('.listitem').show();
		$('#alphabet li a').show(); //[> show everything <]
		showActiveLettersOnly();
		setLetterBox();
		//[> hide on panel <]
		$('#filterPanel').fadeOut();
	});

	// switch sorting
	$('#switch-sort').click(function(){
		$('.alert-box').hide(); // clean up

		// Switch menu name (with multilanguage support)
		old_lang = $('#sort-alt').text();
		new_lang = $('#sort-alt').attr('data-lang');
		$('#sort-alt').text(new_lang);
		$('#sort-alt').attr('data-lang', old_lang);

		$('#journalList').toggleClass('datesorted azsorted');
		if ($('#journalList').hasClass('datesorted')) {
			tinysort('div#journalList>div.listitem',{data:'pubdate', order:'desc', place:'start'});
			$('#alphabet').hide();
			$('#letterbox').hide();
			if ($('#journalList').hasClass('gridview')) {
				//show navigation stuff for grid view
				$('#timewarps').show();
			}
		} else {
			tinysort('div#journalList>div.listitem',{data:'title', order:'asc', place:'start'});
			$('#timewarps').hide();
			if ($('#journalList').hasClass('gridview')) {
				//show navigation stuff for grid view
				$('#alphabet').show();
				$('#letterbox').show();
			}
		}

		setTimeout(function() {window.scrollBy(0,1);}, 500);
	})

	// switch views
	$('#switch-view').click(function(){
		// Switch menu name (with multilanguage support)
		old_lang = $('#list-alt').text();
		new_lang = $('#list-alt').attr('data-lang');
		$('#list-alt').text(new_lang);
		$('#list-alt').attr('data-lang', old_lang);

		$('.alert-box').hide(); // clean up
		$('#journalList').toggleClass('listview gridview');
		$(this).children('i').toggleClass('fi-list fi-thumbnails');
		if ($(this).children('i').hasClass('fi-thumbnails')) {
			//we are in list view
//			$(this).children('span').html('&nbsp;grid view');
			//hide navigation stuff for list view
			$('#timewarps').hide();
			$('#alphabet').hide();
			$('#letterbox').hide();
		} else {
//			$(this).children('span').html('&nbsp;list view');
			//show navigation stuff for grid view
			if ($('#journalList').hasClass('datesorted')) {
				$('#timewarps').show();
			} else {
				$('#alphabet').show();
				$('#letterbox').show();
			}
		}
		/* fix for unveil.js so all visible elements will get their appropriate image content
		 * (because lazy load works only on scroll, we will scroll a bit)
		 * @todo: the initial view on page loading is scrolled down a little. I
		 * just can't find where it is done. But that's why it does not work on switching.
		 */
		setTimeout(function() {window.scrollBy(0,1);}, 500);
	});

	// check if there are any items in cart on opening
	$('#myArticles').click(function(){
		if (simpleCart.quantity() === 0) {
			$("#shelfIsEmpty").show();
			$('#checkOutButton, #emptyCartButton, #emptyConfirmButton').hide();
		} else {
			$("#shelfIsEmpty").hide();
			$('#checkOutButton, #emptyCartButton, #emptyConfirmButton').show();
		}
	});

	// checkout functions (checkout.php)
	$('#actions .button:not(#emptyCartConfirm)').click(function() {
		$('.alert-box').hide(); // cleanup
		var clickedId = $(this).attr('id');
		var thisText = $(this).text();
		var aGr = $('#actionGreeter h1').text();
		var aGrChop = aGr.substring(0, aGr.length - 3);

		// fill in a hidden field with the chosen action for POST
		$('#cartAction').val(clickedId);

		// display chosen action as a heading
		$('#actionGreeter h1').text(thisText);

		// hide other buttons
		$(this).siblings().hide(); $(this).hide();

		// show reset buttons
		$('#resetActions, #emptyCart').show();

		// hide everything inside our box without the class
		//	$('div#actionsResultBox').find('div').show();
		$('.'+clickedId).find("*").not(".error").show();
		$('div#actionsResultBox').show();

		// show everything which has the clicked id as a class
		$('.'+clickedId).show('fast');

		// show the mailForm if it's in the class
		if ($(this).hasClass('mailForm')) { $('#mailForm').show('fast'); }

		// scroll on small screens
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

	// empty cart in checkout.php
	$('#emptyCart').click(function() {
		simpleCart.empty();
		$('#myArticles').removeClass('full');
		$('.alert-box').show();
		$('#emptyCartSuccess').fadeIn("slow");
		$('#actionsResultBox, #actions').fadeOut("fast");
		// fade out and redirect after short time
		setTimeout(function(){$('#emptyCartSuccess').fadeOut();window.location.replace("index.php")},3000);
	});

	// empty cart in index.php
	$('#emptyCartButton').click(function() {
		$('#myArticles').removeClass('full');
	});

	// checkout: check form for valid entries
	$('form').submit(function () {
		// Get the Login Name value and trim it
		var name = $.trim($('*[name="username"]').val());

		// Check if empty of not
		if (name	=== '-please select your account-' || name === '') {
			$('#errorUsername').prev('label').addClass('error');
			$('#errorUsername').text('please choose a name').toggle();
			return false;
		}

		// Check if full domain is given (default: domain gets added by system)
		if(name.indexOf('@') > -1 && $('#mail_domain').length > 0) {
			$('#errorUsername').prev('label').addClass('error');
			$('#errorUsername').text('please enter your account name only (without the @domain)').toggle();
			return false;
		}
	});

	// checkout: reset any error messages on change
	$('form select').change(function() {
		$('#errorUsername').hide();
	});

	// screensaver-like thing with timeout (large screens only, see media.css)
	var s_saver, clear_basket;
	$('body').mousedown(function() {
		clearTimeout(s_saver);
		clearTimeout(clear_basket);
		s_saver = setTimeout(function(){
			$('#screensaver').fadeIn(900);
		}, 300000);
		clear_basket = setTimeout(function(){
			simpleCart.empty();
			$('#myArticles').removeClass('full');
			$('#externalPopover').foundation('reveal', 'close');
		}, 900000);
		$('#screensaver').fadeOut(100);
	});

	// timestamp setup: render timestamps for all 'time' elements with class 'datetime' that has an ISO 8601 timestamp
	$.timeago.settings.allowFuture = true;
	$('time.timeago').timeago();

	// quicksearch setup
	$('input#search').quicksearch('.search-filter', {
		// trigger for unveil.js so _all_ elements will get their appropriate image content
		// (note: 'show' works, but breaks: why?
		// 'show': function () { $(this).addClass('show'); },
		// 'hide': function () { $(this).removeClass('show'); }
		'minValLength': 2,
		'onValTooSmall': function (val) {
			$('h3.view-heading').toggle();
			$('#filterPanel').fadeOut();
		},
		'noResults': '#search-form #noresults',
		'onAfter': function() {
			$('.search-filter img').trigger('unveil');}
	});

	// Open web link in popup ('on' works only from Reveal box!)
	$(document).on("click","a.popup",function(event) {
        event.preventDefault();
		//		$('a.popup').click(function(event) {
		var url = $(this).attr("href");

        // Load meta buttons above toc (if visible)
		issn = $(this).parents('.listitem').attr('data-issn').trim();
        fetch_metabuttons_toc(issn);

		createModalFrame(url);
		$('#externalPopover').foundation('reveal', 'open');
		return false;
	});

	//listen for messages from #externalFrame ~~krug 05.08.2015
	$(window).on("message", function(event){
		var myloc = document.location.protocol +"//"+ document.location.host;
		if (event.originalEvent.origin === myloc) {
			var message = event.originalEvent.data;
			//console.log(message)
			if (message.hasOwnProperty('ready')) {
				// ready: issn-number of the frame -> frame ready. ready: false -> some kind of failure
				if (message.ready) {
					//TODO: transfer info on cartItems and mark them in the TOC list
					var cartinfo = { "tocItems": 0, "items": [] };
					simpleCart.each(simpleCart.find({'issn': message.ready}), function(item){
						var iteminfo = {};
						iteminfo.name = item.get('name');
						iteminfo.doi = item.get('doi');
						cartinfo.items.push(iteminfo);
					});
					cartinfo.tocItems = cartinfo.items.length;
					var eFrm = document.getElementById('externalFrame');
					eFrm.contentWindow.postMessage(cartinfo, myloc);
					$('.toc.preloader').hide();
                    // Workaround for IE - without timeout toc.preloader sticks sometimes...
                    setTimeout(function() {
                        $('#externalPopover').foundation('reveal', 'open');
                    }, 100);
				} else {
					$('.toc.preloader').hide();
					//$('#tocModal').foundation('reveal', 'open');
					$('#tocNotFoundBox').fadeIn('slow');
				}
			} else if (message.hasOwnProperty('add')) {
				var added_item = simpleCart.add(message.add);
				added_item.set('title', '');
				if (message.add.doi !== '') {
					//console.log("doi: "+ message.add.doi);
					$.ajax({
						dataType: "json",
						url: "sys/ajax_cite.php?doi=" + message.add.doi
					})
					.done(function( returnval ){
						if (returnval.hasOwnProperty('DOI') && returnval.DOI === message.add.doi) {
							updateCartItemWithCitation(added_item, returnval, simpleCart);
							console.log("found citation data on crossref for "+returnval.DOI);
						} else {
							console.log("crossref citation data lookup failed I");
						}
					})
					.fail(function(){
						console.log("crossref citation data lookup failed II");
					});
				} else {
					console.log("no crossref doi, but maybe a title");
					console.log("title: "+ message.add.name);
				}
			} else if (message.hasOwnProperty('del')) {
				var found_items;
				if (message.del.doi !== '') {
					found_items = simpleCart.find({ "doi": message.del.doi});
				} else {
					//TODO: remove by title
					found_items = simpleCart.find({ "name": message.del.name});
				}
				for (var i =0; i<found_items.length; i++) {
					found_items[i].remove();
				}
			}
		} else {
			console.log("ignoring messsage from: "+ event.originalEvent.origin);
		}
	});
});
