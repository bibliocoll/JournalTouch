// Time-stamp: "2014-03-11 14:19:05 zimmel"
$(document).ready(function() {

/* show options on journal click */

		$('.journal').click(function() {
				$('#journalOptions, #checkbox, #button').remove(); // clean up
				issn = $(this).attr('id').trim(); 
				//var issn="1464-374X";
				// check where the user is coming from, accordion or orbit slider
			//	if ($(this).is('.active')) { $('#journalOptions').remove();return; } // close item if active, then exit
				clickedElement = $(this); 

				$(clickedElement).next('div.content').append('<form id="my_form"><div class="small-2 columns" id="journalOptions"><label>aktualisiert in KW</label><input type="number" name="week" min="1" max="53"></input></div><div id="checkbox" class="small-4 columns"><label>check</label><input type="checkbox"></input> </div><div id="button" class="small-6 columns"><a id="save" href="#" class="button large">Save</a>&nbsp;<a id="remove" href="#" class="button large">Remove</a></div></form>');
				
		});

	$(document).on("click","#save",function() {
			/* prevent scroll to top */
			event.preventDefault();
			week = $('input[name="week"]').val();

				$.ajax({
						url: "../admin/save.php",
						type: "GET",
						data: { week : week, issn : issn},

						dataType: "text",
						success: function(data, textStatus, jqXHR)
						{
								/* updater */
								$('#kw'+issn).text(week);
							
								//
						},
						error: function (jqXHR, textStatus, errorThrown)
						{
								alert("error");
						}
				});
			
	});
		
});
