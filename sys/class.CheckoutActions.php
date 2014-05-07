<?php
/**
 * Checkout Actions
 *
 * Actions for the checkout
 *
 * Time-stamp: "2014-04-10 15:03:40 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */
class CheckoutActions
{
    
    protected $html;
    protected $endnote;
    public $contents;

    protected $toAddress;
    protected $fromAddress;
    protected $fromName;
    protected $subjectToUser;
    protected $subjectToLib;
    protected $subjectFB;
    protected $domain;
    protected $bodySalutation;
    protected $bodyMessage;
    protected $bodyOrder;
    protected $bodyClosing;

    public function __construct()
    /* load some configuration */
    {
        $config = parse_ini_file('config/config.ini', TRUE);
        $this->toAddress = $config['mailer']['toAddress'];
        $this->fromAddress = $config['mailer']['fromAddress'];
        $this->fromName = $config['mailer']['fromName'];
        $this->subjectToUser = $config['mailer']['subjectToUser'];
        $this->subjectToLib = $config['mailer']['subjectToLib'];
        $this->subjectFB = $config['mailer']['subjectFB'];
        $this->domain = $config['mailer']['domain'];
        $this->bodySalutation = $config['mailer']['bodySalutation'];
        $this->bodyMessage = $config['mailer']['bodyMessage'];
        $this->bodyOrder = $config['mailer']['bodyOrder'];
        $this->bodyClosing = $config['mailer']['bodyClosing'];
    }

    function getArticlesAsHTML($file) {
        
/* this is most likely to break, please rewrite (works only because $i++ is in the last if-item in array order) */
        $this->html = ''; // clear if we have called it before (e.g. from mailer)
        $this->html.='<div class="panel">';

        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "#")) !== FALSE) {
                $num = count($data);
                $row++;

                $this->html.= '<h5><a href="'.$data[1].'">'.$data[0].'</a></h5>';
                $this->html.=$data[2].'<br/><hr/>';
            }
            fclose($handle);
        
        }

        $this->html.='</div>';

        return $this->html;

    }

    function saveArticlesAsCSV($mylist) {

        /* fputcsv does not really work, because the simpleCart js array is one huge array ($i=count) */

        $i = 1;

        foreach ($mylist as $key => $value) {
        
/* this is most likely to break, please rewrite (works only because $i++ is in the last if-item in array order) */
        
            if (strpos($key, 'item_name_'.$i) !== false) {
                $this->contents .= $value."#";
            }
        
            elseif (strpos($key, 'item_link_'.$i) !== false) {
                $this->contents .= $value . "#";
            
            }
        
            elseif (strpos($key, 'item_options_'.$i) !== false) {
                $this->contents .= $value;
                $this->contents .= "\r\n";
                $i++;
            } 
        
        }


      /* save cart in a hashed filename */
        $length = 12;
        $timestamp = date("m.d.y h:i:s"); 
        $hash = substr(hash('md5', $timestamp), 0, $length); // Hash it
        $rand_no = rand(0,500); // add a random number to make sure we do not write to the same file (e.g. two page requests in the same second)

        global $file;
        $file = "export/".$hash."-".$rand_no.".csv";
        file_put_contents($file, $this->contents, LOCK_EX) or die("could not write to file!"); 

    }

    function sendArticlesAsMail($file, $email) {

        try {
            $message = (isset($_POST['message']) && !empty($_POST['message']) ? '<p>'.$this->bodyMessage.': '.$_POST['message'].'</p>' : '');
            $email->CharSet = 'utf-8';
            $email->Encoding = '8bit';
            $email->isHTML(true);
            $file = isset($_POST['file']) ? $_POST['file'] : '';
            $fileBody = (!empty($file)) ? $this->getArticlesAsHTML($file) : '';
            $user = isset($_POST['username']) ? $_POST['username'] : $this->fromAddress;
            if (isset($_POST['action']) && $_POST['action'] == "sendArticlesToLib") {
                $email->FromName  = isset($_POST['username']) ? $_POST['username'].'@'.$this->domain : $this->fromAddress;
                $email->From      = isset($_POST['username']) ? $_POST['username'].'@'.$this->domain : $this->fromAddress;
                $email->Subject   = $this->subjectToLib . ' (from '.$user.')';
                $email->Body     = '<h2>'.$this->bodyOrder.'</h2><p>'.$message.'</p><hr/>'.$fileBody;
                $email->AddAddress($this->toAddress);
            } else {
                $email->FromName  = $this->fromName;
                $email->From      = $this->fromAddress;
                $email->Subject   = $this->subjectToUser;
                $email->Body     = '<h2>'.$this->bodySalutation.'</h2>'.$message.'<hr/>'.$fileBody.'<p>'.$this->bodyClosing.'</p>';
                $email->AddAddress($user.'@'.$this->domain);
            }
            
            
    
	/* add attachment (export only) TODO */
       if (isset($_POST['attachFile'])) {
         if ($_POST['attachFile'] == "endnote") {
            $this->saveArticlesAsEndnote($file);
            $file_to_attach = $file.".ris";
            $filename = "citations_endnote.ris";
         } else if ($_POST['attachFile'] == "csv") {
            $file_to_attach = $file;
            $filename = "citations.csv";
         }  else { /*default*/
            $file_to_attach = $file;
            $filename = "citations.csv";
         }
         $email->AddAttachment( $file_to_attach , $filename );
    }

    $email->Send();
    return "OK";
    /* error handling */
        } catch (phpmailerException $e) {
            return $e->errorMessage(); //Pretty error messages from PHPMailer
        } catch (Exception $e) {
            return $e->getMessage(); //Boring error messages from anything else!
        }
        
    }

    function sendFeedback($email) {

        try {
            $message = (isset($_POST['message']) && !empty($_POST['message']) ? $_POST['message'] : '');
            $email->CharSet = 'utf-8';
            $email->Encoding = '8bit';
            $email->isHTML(false);
            $email->Body     = $message;
            $user = isset($_POST['username']) ? $_POST['username'] : $this->fromAddress;
            $email->Subject   = $this->subjectFB . ' (from '.$user.')';
            $email->From      = isset($_POST['username']) ? $_POST['username'].'@'.$this->domain : $this->fromAddress;
            $email->FromName  = isset($_POST['username']) ? $_POST['username'].'@'.$this->domain : $this->fromAddress;
            $email->AddAddress($this->toAddress); 

            $email->Send();
            return "OK";
            /* error handling */
        } catch (phpmailerException $e) {
            return $e->errorMessage(); //Pretty error messages from PHPMailer
        } catch (Exception $e) {
            return $e->getMessage(); //Boring error messages from anything else!
        }
        
    }

    function saveArticlesAsEndnote($file) {

        $this->endnote = ''; // clear if we have called it before (e.g. from mailer)

        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, "#")) !== FALSE) {
            
                /* strip our csv again - ugly-ugly but the alternative is adding functions to simpleCart.js (only limited fields available) */
                /* BEWARE! This will not match all data (too heterogenous), but at least some of it */
                preg_match('/[^:]*/',$data[0],$au); /* author */
                preg_match('/^.*?:\s(.*)/',$data[0],$ti); /* title */
                preg_match('/source:\s([^,]*)/',$data[2],$jo); /* journal title */
                preg_match('/Vol.?\s(\d+)/',$data[2],$vo); /* volume */
                preg_match('/(\d+)\s-\s(\d+)/',$data[2],$pp); /* pages (start & end, grouping) */
                $this->endnote.= 'TY  - JOUR'.PHP_EOL;
                $this->endnote.= 'AU  - '.$au[0].PHP_EOL;
                $this->endnote.= 'TI  - '.$ti[1].PHP_EOL;
                $this->endnote.= 'JO  - '.$jo[1].PHP_EOL;
                $this->endnote.= 'VL  - '.$vo[1].PHP_EOL;
                $this->endnote.= 'SP  - '.$pp[1].PHP_EOL;
                $this->endnote.= 'EP  - '.$pp[2].PHP_EOL;
                $this->endnote.= 'UR  - '.$data[1].PHP_EOL;
                $this->endnote.= 'ER  - '.PHP_EOL; /* end of record */
            }
            fclose($handle);
        }

        $fileEndnote = $file.".ris";

        file_put_contents($fileEndnote, $this->endnote, LOCK_EX) or die("could not write Endnote to file!"); 

    }

}
?>