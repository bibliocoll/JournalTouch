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

function createModalFrame(href) {
    var dHeight = $(window).height() -280;

    // If the iframe is part of the html, browsers add any click to the browser history. Bad idea, so create it dynamically.
    $('#externalFrame').remove();
    $('#externalPopover').append('<iframe src="" id="externalFrame" scrollbars="yes"></iframe>');
    $("#externalFrame").attr('src',href);
    $("#externalFrame").height(dHeight);
}

$(document).ready(function() {

    /* run unveil plugin on page load */
    setTimeout(function() {$("img.getTOC").unveil();}, 1);
    //$("img").unveil();

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
        $('#journalList').append('<div id="letterbox" class="secondary radius button disabled">A</div>');
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

        /* fill in $meta information and toggle */
        //$(this).siblings('span.metaInfo').clone().appendTo('#fillTOC').toggle();

        /* get Journal TOC in iframe */
        createModalFrame('sys/ajax_toc_fullhtml.php?issn='+ issn);


        //$.ajax({
        //url: 'sys/ajax_toc.php', [> first call <]
        //data: {'issn' : issn},
        //timeout: 7000 [> set default timeout of 7 sec. (because on fail always crossref is queried too) <]
        //}).done(function(returnData) {
        //$('.toc.preloader').fadeOut('slow');
        //if ($(returnData).filter('#noTOC').length > 0) {
        //$('#tocNotFoundBox').fadeIn('slow');
        //}
        ////TODO: either this or the externalPopover below
        //$('#fillTOC').append(returnData).fadeIn('slow');
        //[> timestamp setup: render timestamps for all 'time' elements with class 'datetime' that has an ISO 8601 timestamp <]
        ////TODO: re-include timeago script
        ////$('time.timeago').timeago();
        //if (accordion) {
        //$("html,body").animate({scrollTop: $('#fillTOC').offset().top},'slow');
        //}
        /*  Instead of filling a div, the toc might tbe loaded into an iframe
            Advantage 1: Scrolling on long tocs wouldn't scroll away from
            the journal position on the main page
            Advantage 2: Following links wouldn't take you away from the
            JournalTouch site (much more comfortable to check
            multiple articles); add something like history.back()
            Advantage 3: It might be easier to add meta info above
Prerequisites: Make $('a.popup') more generic and maybe add a toc.php with the content of the #fillTOC div
*/
        //var dHeight = $(window).height() -280;
        //$('#externalPopover').foundation('reveal', 'open');
        //$("#externalFrame").height(dHeight);
        //$("#externalFrame").attr('src','sys/ajax_toc_fullhtml.php?issn='+ issn); // clear previously loaded page;
        //}).fail(function(j,t,m) {
        //$('#tocModal .preloader').hide();
        //$('#tocAlertBox').fadeIn('slow');
        //});
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

    //check on each Ajax-Call (gettoc) if item is already in cart
    //$(document).ajaxComplete(function(){
    //$(".item_name").each(function() {
    //htmlLink = $(this).text();
    //curItem = $(this);
    //simpleCart.each(function(item){
    //var cartItem = item.get('name');
    //if (htmlLink === cartItem) {
    //var myButton = $(curItem).parent().parent().next().children('a.item_add');
    //$(myButton).removeClass('item_add').addClass('item_added');
    //$(myButton).children('i').removeClass("fi-plus").addClass("fi-check");
    //}
    //});
    //});
    //});

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
    //$(document).on("click","a.abstract",function() {
    ////	$(this).parent().prev().children("div.abstract").toggle("easeOutCubic");
    //$(this).parent().next("div.abstract").fadeToggle();
    //});

    //[> add to cart fancy schmancy - we need "on" to access the ajax loaded content, "element.click" will not work <]
    //$(document).on("click",".item_add",function(){
    //$(this).removeClass("item_add");
    //$(this).addClass("item_added");
    //$(this).children('i').removeClass("fi-plus").addClass("fi-check");
    //});

    //[> remove from cart in HTML - we need "on" to access the ajax loaded content, "element.click" will not work <]
    //$(document).on("click",".item_added",function(){
    //$(this).removeClass("item_add");

    //htmlLink = $(this).parent().prev().children(".toctitle").children(".item_name").text();

    //simpleCart.each(function(item){
    //var cartItem = item.get('name');
    //if (htmlLink === cartItem) {
    //item.remove();
    //}
    //});
    //$(this).removeClass("item_added");
    //$(this).addClass("item_add");
    //$(this).children('i').removeClass("fi-check").addClass("fi-plus");
    //});

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
        if (curFilter === "filter-reset") {
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
            if (curFilter === "filter-wir") {$('.filter-fin').show();}
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
        } else if ($(this).children('i').hasClass('fi-thumbnails')) {
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
        if (simpleCart.quantity() === 0) {
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
        if ($(this).hasClass('mailForm')) { $('#mailForm').show('fast'); }
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

    /* empty cart in checkout.php */
    $('#emptyCart').click(function() {
        simpleCart.empty();
        $('#myArticles').removeClass('full');
        $('.alert-box').show();
        $('#emptyCartSuccess').fadeIn("slow");
        $('#actionsResultBox, #actions').fadeOut("fast");
        /* fade out and redirect after short time */
        setTimeout(function(){$('#emptyCartSuccess').fadeOut();window.location.replace("index.php")},3000);
    });

    /* empty cart in index.php */
    $('#emptyCartButton').click(function() {
        $('#myArticles').removeClass('full');
    });

    /* checkout: check form for valid entries */
    $('form').submit(function () {
        // Get the Login Name value and trim it
        var name = $.trim($('*[name="username"]').val());

        // Check if empty of not
        if (name  === '-please select your account-' || name === '') {
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
            $('h3.view-heading').toggle();
            $('#filterPanel').fadeOut();
        },
        'noResults': '#search-form #noresults',
        'onAfter': function() {
            $('.search-filter img').trigger('unveil');}
    });

    /* Open web link in popup ('on' works only from Reveal box!)*/
    $(document).on("click","a.popup",function() {
        //    $('a.popup').click(function(event) {
        var url = $(this).attr("href");

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
                    $('#externalPopover').foundation('reveal', 'open');
                } else {
                    $('.toc.preloader').hide();
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
