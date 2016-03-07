/*!
 * Hide certain elements on kiosk terminals; settings under /admin/settings.php
 * in menu "Kiosk"
 *
 * @author Tobias Zeumer <tzeumer@verweisungsform.de>
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */

$(document).ready(function() {
    /*!
     * Stuff for the main page
     */
    // Hide RSS meta button
    if ($('#kioskPolicy_NoRSS').text() == 1) {
        $('.meta_rss').hide();
    }


    /*!
     * Stuff for the checkout page
     */
    // Hide printing
    if ($('#kioskPolicy_NoPrint').text() == 1) {
        // Checkout: Hide menu entry "View/Print Articles"
        $('#printArticles').hide();
        // Checkout: Hide printer icon
        $('.printArticles').hide();
    }

    // Hide "Send to library to get PDFs"
    if ($('#kioskPolicy_NoSendLib').text() == 1) {
        // Checkout: Hide printer icon
        $('#sendArticlesToLib').hide();
    }


});
