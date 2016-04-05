<?php
require('sys/bootstrap.php');
$mylist = $_POST;
/* do we have GET parameters? (currently only used for contact) */
$myaction = $_GET;
/* load classes */
require_once($cfg->sys->basepath.'sys/class.CheckoutActions.php');
/* setup methods & objects */
$action = new CheckoutActions($cfg);
?>
<!doctype html>
<!--[if IE 9]><html class="lt-ie10" lang="en" > <![endif]-->
<html class="no-js" lang="en" data-useragent="Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo strip_tags($cfg->translations['main_tagline'][$cfg->prefs->current_lang])  ?> - <?php echo __('Checkout') ?></title>
    <link rel="stylesheet" href="css/foundation.css" />
    <link rel="stylesheet" href="css/local.css" />
    <link rel="stylesheet" href="css/local-print.css" media="print" />
    <link rel="stylesheet" href="css/media.css" />
    <link rel="stylesheet" href="css/foundation-icons/foundation-icons.css" />
    <script src="js/vendor/modernizr.js"></script>
    <script src="js/vendor/jquery.js"></script>
    <script src="js/foundation.min.js"></script>
    <script src="js/local/simpleCart.custom.js"></script>
    <script src="js/vendor/jquery.unveil.min.js"></script>
    <script src="js/vendor/jquery.timeago.js"></script>
    <script src="js/vendor/jquery.quicksearch.min.js"></script>
    <script src="js/vendor/citeproc-js/xmldom.js"></script>
    <script src="js/vendor/citeproc-js/citeproc.js"></script>
    <script src="js/local/conduit.js"></script>
    <script src="js/local/cite.js"></script>
  </head>
  <body>

    <!-- Navigation -->

        <nav class="top-bar" data-topbar>
            <ul class="title-area">
                <!-- Title Area -->
                <li class="name">
                    <h1><a href="index.php"><?php echo $cfg->translations['main_tagline'][$cfg->prefs->current_lang] ?></a></h1>
                </li>
                <li class="toggle-topbar"><a class="i fi-arrow-left" href="index.php?lang=<?php echo $action->prefs->current_lang ?>">&nbsp;Back</a></li>
            </ul>

            <section class="top-bar-section">
                <!-- Right Nav Section -->
                <ul class="right">
                    <li class="divider"></li>
                    <li><a class="i fi-arrow-left" href="index.php?lang=<?php echo $action->prefs->current_lang ?>">&nbsp;<?php echo __('Back to journal selection') ?></a></li>
                </ul>
            </section>
        </nav>

        <!-- End Top Bar -->

    <!-- Contact form (only when called with GET-parameter -->
        <?php if($_GET && $_GET['action'] == 'contact') { ?>
        <div class="row">
            <div class="small-12 columns" style="padding-top:20px">
                <h1><?php echo __('Send your feedback to the library') ?></h1>
            </div>
        </div>
            <form name="Feedback" method="post" action="checkout.php">

                <div class="row">
                    <div class="small-12 columns">
                        <label><?php echo __('Your e-mail') ?>

<?php
$userHandle = new GetUsers($cfg);
$users = $userHandle->getUsers();

// If domain is empty, allow full emails; see also conduit.js
$allowed = ($cfg->mail->domain) ? 'mail_domain' : 'mail_all';

if ($users == false) {
    $placeholder = ($cfg->mail->domain) ? __('your username') : __('Your e-mail');
    print '<input name="username" id="'.$allowed.'" placeholder="'.$placeholder.'" type="text"/>';
} else {
    print '<select name="username">';
    foreach ($users as $name=>$pw) {
        print '<option>'.$name.'</option>';
    }
    print'	</select>';
}
?>

                        </label>
                        <small id="errorUsername" class="error" style="display:none"><?php echo __('please choose a name') ?></small>

                    </div>
                </div>

                <div class="row">
                    <div class="small-12 columns">
                        <label><?php echo __('Your feedback message') ?>
                            <textarea name="message" placeholder="<?php echo __('if you have any comments for us please put them here!'); ?>"><?php if (isset($_GET['message'])) { print $_GET['message']; } ?></textarea>
                        </label>
                    </div>
                </div>

                <div class="row">
                    <div class="small-12 columns">
                        <!-- flag for POST (first page view contains POST values from cart; BEWARE: sending the form overwrites the values -->
                        <input type="hidden" name="mailer" value="true"/>
                        <input type="hidden" name="feedback" value="true"/>
                        <input type="hidden" name="lang" value="<?php echo $action->prefs->current_lang ?>">
                        <input class="radius button large right submit" type="submit" value="<?php echo __('Submit') ?>">
                    </div>
                </div>

            </form>
        <?php } else { ?>
        <!-- End Contact form -->

        <div class="row" id="actionGreeter">
            <div class="small-12 columns" style="padding-top:20px">
                <h1><span id="topMenu"><?php echo __('I want to...') ?></span><span id="subMenu"></span></h1>
            </div>
        </div>

        <!-- End Header and Nav -->

        <div id="actions" class="row">
            <div class="small-12 text-center columns">
                <a id="printArticles" href="#" class="radius button large"><i class="fi-print"></i> <?php echo __('View &amp; Print') ?></a>
                <!--<a id="saveArticles" href="#" class="radius button large disabled"><i class="fi-save"></i> Save/Export</a>-->
                <?php if(empty($_POST['mailer'])) { ?>
              <a id="sendArticlesToUser" href="#" class="button radius large mailForm"><i class="fi-mail"></i> <?php echo __('Send to my mailbox') ?></a>
                    <?php if ($cfg->prefs->allow_ask_pdf) { ?>
                        <a id="sendArticlesToLib" href="#" class="button radius large mailForm"><i class="fi-mail"></i> <?php echo __('Send to library to get PDFs') ?></a>
                    <?php } ?>
                <?php } else { ?>
                <a id="sendDone" href="#" class="radius button large success"><i class="fi-check"></i> <?php echo __('You already sent your files') ?> </a>
                <?php } ?>
                <a id="resetActions" href="#" class="radius button large reset" style="display:none"><i class="fi-arrow-left"></i> <?php echo __('choose another option') ?></a>
                <!--<a id="emptyCart" href="#" class="radius button large alert"><i class="fi-arrows-out"></i> Clear Data and Logout</a>-->
                <a id="emptyCartConfirm" class="radius large alert button" data-reveal-id="emptyConfirm"><i class="fi-arrows-out"></i> <?php echo __('Clear Data and Logout') ?></a>
            </div>
        </div>

        <!-- Security confirmation on delete -->
        <div id="emptyConfirm" class="reveal-modal" data-reveal>
            <h3><?php echo __('Do you really want to empty your basket?') ?></h3>
            <a id="emptyCart" href="#" class="radius small alert button close-reveal-modal"><i class="fi-trash"></i> <?php echo __('OK, empty my basket!') ?></a>
            <a id="DoNotemptyCartButton" class="radius small success button close-reveal-modal"><i class="fi-trash"></i> <?php echo __('No, keep basket!') ?></a>
        </div>

        <?php } /* end GET query */ ?>

        <div id="emptyCartSuccess" class="row invisible">
            <div class="small-12 text-center columns">
                <div data-alert class="alert-box success radius">
                    <i class="fi-check"></i> <?php echo __('Your articles have been successfully deleted! You will automatically be taken to the start page.') ?>
                    <a href="#" class="close">&times;</a>
                </div>
            </div>
        </div>

        <div id="actionsResultBox">
<!-- Start Mailer Response -->
<?php

$file = '';
if(isset($_POST['mailer']))
{
  // looks like we need to initialize PHPMailer
  require_once($cfg->sys->basepath.'sys/PHPMailer/PHPMailerAutoload.php');
  $mail = new PHPMailer(true);
  //$mail->SMTPDebug = 3; // Enable verbose debug output
  if ($cfg->mail->useSMTP) {
    $mail->isSMTP(); // Set mailer to use SMTP
    $mail->Host = $cfg->mail->smtpServer;  // Specify main and backup SMTP servers
    $mail->Port = $cfg->mail->smtpPort;
    if ($cfg->mail->useSMTPAuth) {
      $mail->SMTPAuth = true; // Enable SMTP authentication
      $mail->Username = $cfg->mail->smtpUser; // SMTP username
      $mail->Password = $cfg->mail->smtpPass; // SMTP password
      if (!empty($cfg->mail->smtpSec)) { $mail->SMTPSecure = strtolower($cfg->mail->smtpSec); } // Enable TLS encryption, `ssl` also accepted
    }
  }
    // if we have already sent an e-mail, read again from POST
    $file = (empty($file) && isset($_POST['file'])) ? $file = $_POST['file'] : '';

        /* pass the PHPMailer object & save the return value (success or failure?) */
        /* is it feedback? */
        if (isset($_POST['feedback'])) {
            $mailerResponse = $action->sendFeedback($mail);
        } else {
            $mailerResponse = $action->sendArticlesAsMail($file, $mail);
        }
    /* error handling */
    if ($mailerResponse == "OK") {
        /* default, everything is alright */
?>

   <div class="row">
       <div class="small-12 text-center columns">
           <div data-alert class="alert-box success radius">
                 <i class="fi-check"></i>&nbsp; <?php echo __('Your message has been successfully sent!') ?>  <a href="#" class="close">&times;</a>
           </div>
         </div>
     </div>

<?php
    } else {
        /* something went wrong */
?>
    <div id="actions" class="row">
            <div class="small-12 text-center columns">
                <div data-alert class="alert-box warning radius">
          <i class="fi-x"></i>&nbsp; <?php print $mailerResponse;?>  <a href="#" class="close">&times;</a>
                </div>
          </div>
      </div>


<!-- End Mailer Response -->

<!-- Start Mailer  -->

<?php
    }

} else { /* if no mail has been sent yet */

    /* Mailer: show Form */

    /* save selection by default */
    if (empty($_GET) || (count($_GET) == 1 && isset($_GET['lang']))) { // do not show with any GET parameters
        $action->saveArticlesAsCSV($mylist);
    }
}

?>

    <div id="mailForm" style="display:none">
            <form name="Request" method="post" action="checkout.php">

                <div class="row sendArticlesToLib sendArticlesToUser">
<?php
if (isset($cfg->dbusers->userlist) && $cfg->dbusers->userlist === TRUE) {
  require_once($cfg->sys->basepath.'sys/class.GetUsers.php');
  $userHandle = new GetUsers($cfg);
  $users = $userHandle->getUsers();
} else {
  $users = FALSE;
}
// if GetUsers failed or was turned off, allow entering an address
if ($users === FALSE) {
    $placeholder = ($cfg->mail->domain) ? __('your username') : __('Your e-mail');
    $postfix     = ($cfg->mail->domain) ? '@'.$cfg->mail->domain : '';
    $coladd      = ($cfg->mail->domain) ? 3 : 0;

    // If domain is empty, allow full emails; see also conduit.js
    $allowed = ($cfg->mail->domain) ? 'mail_domain' : 'mail_all';

    echo'
      <div class="row collapse">
        <label for="'.$allowed.'">'.__('Your e-mail').'</label>
        <div class="small-'.(12 - $coladd).' columns">
          <input name="username" id="'.$allowed.'" placeholder="'.$placeholder.'" type="text" />
        </div>';
    // Add the allowed user mailing domain at the end ("employees only")
    if ($coladd) {
        echo '  <div class="small-'.$coladd.' columns">
                    <span class="postfix">'.$postfix.'</span>
                </div>';
    }
    echo '</div>';
} else {
    print '<select name="username">';
    foreach ($users as $name=>$pw) {
        print '<option>'.$name.'</option>';
    }
    print'	</select>';
}
?>

                        </label>
                        <small id="errorUsername" class="error" style="display:none"><?php echo __('please choose a name') ?></small>

                    </div>
                </div>

                <div class="row sendArticlesToUser">
                    <div class="small-12 columns">
                        <label><?php echo __('Attach citations?') ?></label><!--<small class="error">beware: experimental feature</small>-->
                        <input type="radio" id="attachFileEndnote" name="attachFile" value="endnote"><label for="attachFileEndnote">Endnote</label>
                        <!-- <input type="radio" id="attachFileBibTeX" name="attachFile" value="bibtex" disabled="disabled"><label for="attachFileBibTeX">BibTeX</label> -->
                        <input type="radio" id="attachFileCSV" name="attachFile" value="csv"><label for="attachFileBibTeX">CSV</label>
                    </div>
                </div>

                <div class="row sendArticlesToLib">
                    <div class="small-12 columns">
                        <label><?php echo __('Your message') ?>
                            <textarea name="message" placeholder="<?php echo __('if you have any comments for us please put them here!'); ?>"></textarea>
                        </label>
                    </div>
                </div>

                <div class="row sendArticlesToLib sendArticlesToUser">
                    <div class="small-12 columns">
                        <!-- flag for POST (first page view contains POST values from cart; BEWARE: sending the form overwrites the values -->
                        <input type="hidden" name="mailer" value="true"/>
                        <input type="hidden" name="file" value="<?php print $file; ?>"/>
                        <input type="hidden" name="lang" value="<?php echo $action->prefs->current_lang ?>"/>
                        <input type="hidden" name="action" id="cartAction" value=""/><!-- this one is important and is set from conduit.js! -->
                        <input class="radius button large right submit" type="submit" value="<?php echo __('Submit') ?>">
                    </div>
                </div>

            </form>



<!-- End Mailer -->

<!-- Start View -->

        <div id="viewBox" class="printArticles" style="display:none">

            <div class="row">
                <div class="small-12 columns print">
                    <a href="javascript:window.print();" class="radius button large"><i class="fi-print"></i></a>
                </div>
            </div>

            <div class="row">
                <div class="small-12 columns">

<?php if (empty($_GET) || (count($_GET) == 1 && isset($_GET['lang']))) { // do not show with any GET parameters
    // if we have already sent an e-mail, read again from POST
    if (empty($file)) {$file = $_POST['file'];}
        print $action->getArticlesAsHTML($file);
}
?>
                </div>
            </div>

<!-- start external link -->
<div id="externalPopover" class="reveal-modal" data-reveal="">
  <h3><?php echo __('External Source') ?></h3>
  <a id="frameBack" class="button round" data-history="0" onclick="if ($(this).data('history') < history.length) history.go(-1)"><i class="fi-arrow-left"></i></a>
  <a class="close-reveal-modal button radius">×</a>
  <!-- For preventing browser history for the iframe "externalFrame" it is dynamically created in conduit.js -->
</div>
<!-- end external link -->
<!-- End View -->

<!-- Start Save/Export -->
<!-- not in use -->
<!--
            <div id="saveDialog" style="display:none">

                <div class="row">
                    <div class="small-12 columns">

                    </div>
                </div>
-->
<!-- End Save/Export -->
            </div>

        </div>

<script>
$(document).foundation();

var doc = document.documentElement;
doc.setAttribute('data-useragent', navigator.userAgent);
</script>

<!-- START Kiosk policies -->
<?php echo $cfg->sys->kioskPolicy_HTML ?>
<!-- END Kiosk policies -->
  </body>
</html>
