# About

JournalTouch provides a touch-optimized interface for browsing current journal tables of contents in Responsive Design. Fun!

# License

@copyright 2015 MPI for Research on Collective Goods, Library (Contact: fruehauf@coll.mpg.de)

@author Daniel Zimmel <dnl@mailbox.org>

@author Tobias Zeumer <tzeumer@verweisungsform.de>

License: http://www.gnu.org/licenses/gpl.html GPL version 3 or higher

# Dependencies

- PHP 5 ([http://www.php.net])
- API key for JournalTocs ([http://www.journaltocs.ac.uk])

already included:

- Foundation 5 [http://foundation.zurb.com]
- jQuery 2+ [http://jquery.com]
- simpleCart js [http://simplecartjs.org] **slightly modified**
- Unveil.js [https://github.com/luis-almeida/unveil] for lazy loading
- waypoints.js [https://github.com/imakewebthings/jquery-waypoints]
- timeago.js [https://github.com/rmm5t/jquery-timeago]
- quicksearch.js [http://deuxhuithuit.github.io/quicksearch/]
- PHPMailer [https://github.com/PHPMailer/PHPMailer]
- php-gettext [https://launchpad.net/php-gettext]

# Live Demo

Try here: http://www.coll.mpg.de/bib/jtdemo-public/

# Installation & Setup

## General

The main page is *index.php* and reads the relevant journals from *input/*. Put a CSV file in here. Alternatively, set up a Google Drive Spreadsheet and read it directly from the web.
It must at least contain the journal titles and a valid ISSN; configure optional columns in the *config.php* (e.g. for filters). You can add any columns you like (extend the PHP classes).

### Configure sources for the TOCs

External (TOC) contents will be read from *index.php* with an AJAX call to *sys/ajax_toc.php*. This file outputs HTML to the caller which is ready to be inserted in *index.php*. You will need a JournalTocs API key for the query (= your JournalTocs user e-mail). Set it in *config.php*.
If you want to read from other sources, expand the handler.

By default the query goes to JournalTocs and as a fallback option, there is a query to the (experimental) CrossRef API. To change this precedence and for more details refer to the function *ajax_query_toc* in *sys/class.getJournalInfos.php*. Error handling is as follows: any network (HTTP) problems will be handled in the Javascript ajax function (jQuery "fail" event). 

Please note: while you could set up an alternative ISSN in the config.php, it is ignored by now.

Error handling example: 

*sys/class.getJournalInfos.php*:

  private function ajax_response_toc($toc, $max_authors = 3) {
    if (!isset($toc['sort'])) {
        /* write something we can read from our caller script */
        /* trigger error response from conduit.js; configure in index.php */
        return '<span id="noTOC"/>';
    }
    elseif (count($toc['sort']) < 1) {
        return '<span id="noTOC"/>';
    }

handle in *conduit.js*:

		if ($(returnData).filter('#noTOC').length > 0) {
		// ... fire a second event or write an error message...
		}

### Checkout options

Checkout options are handled in *checkout.php* and the imported classes.

Be sure to set writing rights to *export/*.

### User interaction
User interaction is handled in *js/local/conduit.js*.

### Other notes
Important note for Excel CSV exports: expect problems if your file is not UTF-8 encoded! Excel does not export to UTF-8 by default.

## Setup configuration file

The configuration is handled in *config.php*.
Configuration parameters are grouped and will be read in the PHP classes as a multidimensional array. For details refer to the PHP documentation for parse_ini_file().

**Example 1: read all configuration variables into the construct function in some class**

        require_once('config.php');
        $this->cfg = $cfg;

**Example 2: map only some variables into the construct function in some class**

        require_once('config.php');
        $this->jt = $cfg->api->jt;

## Touch devices

See the examples for special touchscreen customization below in *Customization/Responsiveness*.

This webapp is reported to run sluggishly on touch devices running Chrome and Windows 8. On this system configuration, better use current versions of Internet Explorer or Firefox.

# Usage

## Maintaining journal updates

The input file feeds the journal list. Be careful if you change the structure of your CSV file (you may need to reconfigure the csv group in *config.php*).

One of the not-so-trivial things is maintaining the marking of journals as "recently updated".
There is a basic experimental service that checks on new journal issues. It must be called separately (e.g. daily from a cronjob), and it works only if you have licensed access to the JournalTOCs Premium API. Put the RSS URLs in *config.php* (section updates).
Run the service *services/getLatestJournals.php* (e.g. on a daily basis). It will output an array of ISSNs that are written to *input/updates.json*. Adapt it to your needs. Currently, it runs a query to JournalTocs, compares the found ISSNs with the local holdings (= your CSV), and adds it to the file with the current date if the journal is in your CSV file. The output file will be read from *sys/class.ListJournals.php* in the function ``isCurrent()`` and add a 'new' marking to the journal array (which then you can read from index.php). 

Additionally, you may want to include the JSON file to display a list of recently updated journals. The function ``getJournalUpdates()`` in *sys/class.ListJournals.php* will give you an array you can read from index.php. See the exemplary code there.

Configure the file and your custom JournalTOCs URL in *config.php*.

Please note! This service slows down the initial loading of the page if the file is too big. 

Alternatively, you could set up alerting services for yourself (e-mail, feeds...) and keep the input file up-to-date manually:
for the image slider ("Current this week") above the list or grid view ("Orbit") you will need to fill and update a special column (*config.php*-default: *date*). Fill in the current date ("YYYY-mm-dd") when a new journal issue arrives; these journals will be displayed in the slider and get a special marking (default: "new" icon).

The default setting is that you can combine the two functions (automatic comparison and manual setting).

Please note: Orbit tends to slow things a lot on mobile devices if you have lots of images (any image will be preloaded by default). To change this, either do not use Orbit, or modify its source to implenent some sort of lazy loading.

You can configure a column (default: "important") to mark important journals (fill in what you like).

For date display, you can use the timeago jQuery plugin (display timespans instead of dates). For reference, see https://github.com/rmm5t/jquery-timeago. E.g., activation for all HTML5 'time' elements with attribute 'timeago' and date in ISO 8601 timestamp happens in *js/local/conduit.js*:

          		$('time.timeago').timeago();

## Cover images

If you have access to a cover service API, set the setting in *config.php* to ``true``, and configure your service in *sys/class.ListJournals.php* (``getCover()``).
By default, cover images will be loaded from *img/*, if there exists an image file named after the ISSN (e.g. *0123-4567.png*). If not, a placeholder will be used (configure in *config.php*).

All image content is preloaded from the input file. To make things load faster (e.g. on slow bandwidth), the jQuery plugin unveil.js is loaded by default. The preload image is in the *img/*-directory and is called *lazyloader.gif*. The placeholder image must be set in the *src* attribute of the journal listing. The actual cover image must be placed in the attribute *data-src*. See the listing part in index.php.

## PHP Classes

The following classes are required:

*sys/class.CheckoutActions.php*

*sys/class.GetUser.php*

*sys/class.ListJournals.php*

Access the methods in these classes from *index.php* and *checkout.php*

**Example: access the journals**

setup...

				 require 'sys/class.ListJournals.php'; 
				 $lister = new ListJournals();
				 $journals = $lister->getJournals();

...and do something with it:

		foreach ($journals as $j) {
			   if (!empty($j['new'])) { 
				     echo '<li data-orbit-slide="headline">';
				     echo '<img class="issn getTOC" id="'.$j['id'].'" src="'.$j['img'].'"/>';
				     echo '<div class="orbit-caption">'.$j['title'].'</div>';
				     echo '</li>';
			   }
		}

...this is handled in the method in *sys/class.ListJournals.php*
			 
			 function getJournals()

**Example: set up the Mailer**

setup...

				require 'sys/PHPMailer/PHPMailerAutoload.php';
				$email = new PHPMailer();
				$action = new CheckoutActions();

...and send the mail...

			 if($_POST && $_POST['mailer'])
       {
			 $action->sendArticlesAsMail($file, $email);
			 }

...this is handled in the method in *sys/class.CheckoutActions.php*
			 
			 function sendArticlesAsMail($file, $email)

(do not forget to pass the ``PHPMailer()`` object, here ``$email``)

# Getting the tables of contents

*index.php* contains a class named ``.getTOC`` which will trigger the AJAX call to fetch the TOC and insert a HTML snippet. The action is handled in *js/local/conduit.js*.

**Important!** Be careful when changing the HTML in the snippet! The DOM layout is essential for the jQuery functions to work as expected. If you need to make changes to the snippet, do not forget to change the jQuery selectors in *js/local/conduit.js* as well.

# Basket functionality

The basket is completely written in Javascript. For configuration, refer to the [simpleCart-JS documentation](http://simplecartjs.org).

Basic configuration is in *index.php*. The CSS classes and IDs for the
articles (``item_*``) need to exist in the HTML snippet that includes the table of contents (*ajax/getJournalTOC.php*).
Actions for adding/removing/displaying the basket are handled in *js/local/conduit.js*.

# Additional metadata

-- TODO -- see *config.ini* and *index.php* for details on how to provide extra metadata. By default it is not shown.

# Checkout options 

The default checkout main file is *checkout.php*. The click actions are configured in *js/local/conduit.js*.

The text for E-Mail notifications is configured in *config.php*.

Please note: the current mixing of GET/POST and jQuery bits is chaotic. Beware of bugs. Please rewrite.

## Export directory

By default, a time-hashed file will be written to *export/* on calling *checkout.php* (``$action->saveArticlesAsCSV($mylist);``). 
**These files will not be deleted by default** (extend the given methods to achieve this).

## Mailer

Mailing service configuration happens with PHPMailer. Configure your mail preferences in *config.php*.

By default, mailing is only possible for user accounts registered at a given host. Set the *domain* in the mailer group in *config.php*. The users only put in their user names.
For an alternative behavior, or if you want to allow free input, change the function ``sendArticlesAsMail()`` in *sys/classCheckoutActions.php*.

If you want to offer a predefined list of allowed user mail accounts, set the option *userlist* to *true* in *config.php*, and set up a function in *sys/class.GetUsers.php*.
Return an array of users. Get it for example from a database query (add a function to the class, see the example *class.GetUsers.php*).

# Customization

## Look and Feel

Built with the Foundation HTML5 framework. Please refer to the [documentation](http://foundation.zurb.com/docs/).

For CSS customizations use *css/local.css* .

### Responsiveness

Foundation is a responsive design framework. It will adapt itself to different devices and screens. However, if you need more precise positionings and conditions, there are nice customization features.
By default, there is a common stylesheet (*css/local.css*), and another one for special media queries (*css/media.css*).

**Example: insert a special CSS for large screens (e.g. larger buttons for large touchscreens)**

add the following media query to the stylesheet *media.css*:

		@media only screen and (min-width: 85.063em) {	
     ...CSS goes here...
    }


**Example: Hide some elements for small screens**

    <span class="hide-for-small">This element will be hidden on small screens</span>

Refer to the [Foundation Visibility classes](http://foundation.zurb.com/docs/components/visibility.html).

### Orbit Slider Configuration 

Orbit is an image slider shipped with Foundation. By default, it is inactive. It could be used to display most recent journal updates, or any other stuff you want to highlight. If you like fancy,
configure the image slider directly in index.php.

**Example: change setting**

     <ul id="myorbit" data-orbit data-options="animation_speed: 1000;timer_speed: 2000;animation: 'fade';bullets: false">

For all available options refer to the [Foundation Orbit Documentation](http://foundation.zurb.com/docs/components/orbit.html).

### Screensaver function

If you want a screensaver functionality, change the text in the *#screensaver*-div in *index.php*, and check out the function in *js/local/conduit.js*, which loads a block after a click/touch timeout.
By default it is only active on large screens (see CSS in *css/media.css*).

### Dynamic Alphabet

On scrolling, the current letter will be highlighted (*#letterbox* is appended dynamically via jQuery). For configuration, see *js/local/conduit.js*. For this to work, be sure to have the *waypoints.min.js*-Plugin included in your *index.php*.

### Configuring the tables of contents

The TOCs are injected with an HTML snippet. Configure in the *ajax* directory. If for some reason you do not want links to appear, set the *config.php* setting to false (group *toc*).
Please note: to make sure that you really have full text access, you might want to inject an OpenURL service like SFX beforehand. It should be easy at least with CrossRef -- the service is already sending a valid OpenUrl ("coins" field). Configure for example in the *ajax* directory.

## Icons

Default for an unified look are the Foundation icons [Foundation-Icons](http://zurb.com/playground/foundation-icon-fonts-3). 

**Example: insert a star icon**

				<i class="fi-star"></i>

## Filter

You can use the entries in a CSV column of your input file as filters. Set the column and filters in *config.php*, group csv.
Comment out the filter value in the csv group if you do not have/want filters.
Please note: anything in the "important" col. will get the special CSS-ID "topJ" (see
*sys/class.ListJournals.php*). The other column contents will be added as CSS-IDs to the DOM.

Filters will show up in the heading section of *index.php*, and their behavior on click is handled in *js/local/conduit.js*.

## Export options

Export for citation management software currently is very basic. To be
able to digest heterogeneous data from different sources (CrossRef,
JournalTOCs...), some essential metadata fields should be normalized already
 in the TOC snippet (*ajax/get...*). 

When a user clicks on the basket checkout, a csv file will automatically be generated in *export/*. The function is in *sys/class.CheckoutActions.php*: ``saveArticlesAsCSV($mylist)``. When a user wants to export data, all fields will be read from this csv file. Write your mapping into an export function. For example, see the
function ``saveArticlesAsEndnote()``.

Caveat: in the current implementation, mapping of the metadata is limited to the given *simpleCart* fields (``item_name``, ``item_link``, ``item_options_``), and will be re-read from the source string when exporting. This is by no means a clean implementation. It would be better to modify the *simpleCart* js for a cleaner mapping (it is not really a mapping right now). (TODO)

## Localization

-- TODO -- 

JournalTouch has multilanguage support by default. For details and customization see *locale/*.

# Android Hints

To port it to a native mobile environment, you can use Apache Cordova / PhoneGap.
For details on the setup see README.Android.md

# Admin Module

There is no administration module.
A comfortable way is using the Google Drive API for managing the Spreadsheet data; just publish it on the web and read the file directly from Google Drive. Set in *config.php*.

# TODO

More reliable integration of hotness

Wrap in Cordova iOS

Authentication/Personalization

Cleanup/Rewrite Checkout (GET/POST handling is a bit chaotic)

Mapping for export (extend simpleCart.js)
