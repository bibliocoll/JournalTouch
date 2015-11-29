function readyFrame(loc){
    var myloc = loc.protocol + "//" + loc.host;
    var myissn = (loc.search.indexOf('issn=') > 0)? loc.search.substr(loc.search.indexOf('issn=')+5, 9) : false;

    $(window).on("message", function(event){
        if (event.originalEvent.origin === myloc) {
            var message = event.originalEvent.data;
            //console.log(message)
            if (message.hasOwnProperty('tocItems')) {
                for (var i=0; i<message.tocItems; i++) {
                    var item = message.items[i];
                    if (item.doi !== '') {
                        $('.row[data-doi="'+item.doi+'"]')
                            .find('.item_add')
                                .addClass('item_added')
                                .removeClass('item_add')
                                .children('i').removeClass("fi-plus").addClass("fi-check");
                    } else if (item.name !=='') {
                        $('.row[data-name="'+item.name+'"]')
                            .find('.item_add')
                                .addClass('item_added')
                                .removeClass('item_add')
                                .children('i').removeClass("fi-plus").addClass("fi-check");
                    }
                }
            }
        }
    });

    $('time.timeago').timeago();

    /* toggle abstracts - we need "on" to access the ajax loaded content, "element.click" will not work */
    $(document).on("click","a.abstract",function() {
        //	$(this).parent().prev().children("div.abstract").toggle("easeOutCubic");
        $(this).parent().next("div.abstract").fadeToggle();
    });

    // QR code and DOI on click
    $(document).on("click","a.title_links",function() {
        var link = '';

        // Use doi for qr if available. Otherwiese the regular link
        var doi  = $(this).parents('.tocItem').attr('data-doi');
        if (doi != '') {
            link = 'https://dx.doi.org/'+doi;
        } else{
            link = $(this).parents('.tocItem').attr('data-link');
        }

        //alert($(this).parents().children("div.title_links_layer").children("span.lnkQR").text());

        $(this).parents().children("div.title_links_layer").fadeToggle();

    	$(this).parents().children("div.title_links_layer").children("span.lnkQR").empty().qrcode({
            render: 'canvas',
    		text	: link,
            size: 150
    	});

    });


    $(document).on("click",".item_add",function(){
        $(this)
            .removeClass("item_add")
            .addClass("item_added")
            .children('i').removeClass("fi-plus").addClass("fi-check");
        var data = $(this).parents('.row').data();
        data.issn = myissn;
        window.parent.postMessage({"add": data}, myloc);
    });

    $(document).on("click",".item_added",function(){
        $(this)
            .removeClass("item_added")
            .addClass("item_add")
            .children('i').removeClass("fi-check").addClass("fi-plus");
        var data = $(this).parents('.row').data();
        data.issn = myissn;
        window.parent.postMessage({"del": data}, myloc);
    });

    //all set, tell parent to lift the curtain :)
    window.parent.postMessage({"ready": myissn}, myloc);
};

//$(document).off('ready', doFrameStuff());
$(document).ready(readyFrame(document.location));
