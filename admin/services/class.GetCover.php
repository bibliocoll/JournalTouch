<?php
//$bla = new GetCover();
//$bla->get_cover($_GET["issn"]);
//$bla->test_downloads();
//echo $bla->log;

/**
 * @brief   Get cover for a specific issn
 *
 * - Images are always saved as issn.ext (e.g. 1234-5678.jpg); with hyphen
 * - Fixed images ($folder_fixed) are always preferred, so only move there what
 *   should not ever be downloaded.
 *
 * @todo
 * - xxx
 *
 * @author Tobias Zeumer <tzeumer@verweisungsform.de>
 */
class GetCover {
    /* Internal stuff */
    /// \brief \b BOOL  This class can be perfectly well without JournalTouch. By default it uses some settings from JT's config.php
    private $standalone;

    /* Setting concerning cover location and extensions */
    /// \brief \b ARY List of allowed image extensions
    private $cover_extensions = array('jpg', 'gif', 'png');
    /// \brief \b STR Folder where manually downloaded covers are saved. These prevent download via api
    public $folder_fixed    = '../../data/covers/';
    /// \brief \b STR Folder where covers downloaded by this class are saved
    public $folder_dl       = '../../data/covers/api/';

    /// \brief \b STR The ISSN of the current journal
    private $issn;
    /// \brief \b STR The ISSN of the current journal without hyphen
    private $issn_str;
    /// \brief \b STR The publisher name - important to use specific download source
    private $publisher;

    /* Setting concerning handling the logic */
    public $recheck_api_age = 7; //Age of cover file in days - check for new cover if older
    public $custom_api_url  = ''; //Something like http://myservice.net/issn= - if one got something like that (where everything can be fetched)...

    /// \brief \b ARY List of generic cover sources that can be enabled for checking via this class; change order if you prefer on over the other
    public $src_genric  = array('STMcovers'     => 1,
                                'JournalTocs'   => 1,
                                'Lehmanns'      => 1,
                                'SubscribeToJournals' => 1);

    /// \brief \b ARY List of Publishers for cover download; Names are as JournalTocs returns them
    public $src_publisher = array(  'DeGruyter' => 1,
                                    'Elsevier'  => 1,
                                    'Sage'      => 1,
                                    'Springer'  => 1);

    /* Stuff to be used by user */
    public $log = '';

    /// \brief \b STR Type of cover (see $cover_extensions)
    public $cover_type      = '';
    /// \brief \b STR Path to existing cover
    public $cover_path      = '';
    /// \brief \b STR Size of cover file in byte
    public $cover_size      = '';
    /// \brief \b STR Age of existing cover file
    public $cover_age       = '';

    /* Download related */
    /// \brief \b STR Binary content
    public $cover_binary    = '';
    /// \brief \b STR Cover url
    public $cover_url       = '';



    /**
     * @brief   Load config (also loads bootstrap), set properties
     *
     * @return \b void
     */
    public function __construct($standalone = false) {
        $this->standalone = $standalone;

        if ($this->standalone == false) {
            require(__DIR__.'/../../sys/bootstrap.php');
            // Check for the super special MPI case with folders being elsewhere
            if ($cfg->sys->data_covers == 'data/covers/') {
                $this->folder_fixed = __DIR__.'/../../data/covers/';
            } else {
                $this->folder_fixed = $cfg->sys->data_covers;
            }
            $this->folder_dl = $this->folder_fixed.'api/';

            $this->custom_api_url   = $cfg->covers->api;
            $this->src_genric       = $cfg->covers->src_genric;
            $this->src_publisher    = $cfg->covers->src_publisher;
        }
    }


    /**
     * @brief   Deconstructor. Shows script time...
     */
    function __destruct() {
        // Nothing yet
    }


    /**
     * @brief   Make sure the issn is as required by a specific service
     *          It's no real validation!
     *
     * @param $issn    \b STR  An issn string
     *
     * @return \b true if issn is ok, false if not
     */
    private function _normalize_issn($issn) {
        // 8 chars with hyphen
        if (preg_match('/[Xx0-9]{4}-[Xx0-9]{4}$/', $issn)) {
            $this->issn     = $issn;
            $this->issn_str = str_replace('-', '', $this->issn);
            return true;
        }
        // 8 chars without hyphen
        elseif (preg_match('/[Xx0-9]{8}$/', $issn)) {
            $this->issn     = substr($issn, 0, 4).'-'.substr($issn, 4, 4);
            $this->issn_str = $issn;
            return true;
        }
        else {
            return false;
        }
    }


    /**
     * @brief   Reset class properties usually set by _save_image(). Prevents
     *          wrong entries in case get_covers() is called in batch mode
     *
     * @return \b true if issn is ok, false if not
     */
    private function _reset_properties() {
        // Reset some properties in case this method is called multiple times
        $this->cover_type = $this->cover_path = $this->cover_age = $this->cover_binary = $this->cover_url = '';

        // Better way might be using "good" names to batch reset like this,
        // without resetting custom option
        // foreach (get_class_vars(get_class($this)) as $name => $default) $this -> $name = $default;

        return true;
    }


    /**
     * @brief   Get cover for a specific issn. Kind of a controller
     *
     * @param $issn         \b STR  An issn string - REQUIRED
     * @param $publisher    \b STR  Name of publisher for the issn; optional for specific download
     * @param $directlink   \b STR  If you got a directlink for a cover download
     *
     * @return \b true if issn is ok, false if not
     */
    public function get_cover($issn, $publisher = '', $directlink = '') {
        $this->_reset_properties();
        $this->log = '';

        // If publisher is set, clean the JournalTocs names to names used in this class
        if      (stripos($publisher, 'gruyter') !== false)  $this->publisher = 'degruyter';
        elseif  (stripos($publisher, 'elsevier')!== false)  $this->publisher = 'elsevier';
        elseif  (stripos($publisher, 'wiley')   !== false)  $this->publisher = 'wiley';
        elseif  (stripos($publisher, 'sage')    !== false)  $this->publisher = 'sage';
        elseif  (stripos($publisher, 'springer')!== false)  $this->publisher = 'springer';

        // Check if issn is ok and "normalize" it
        $status = ($this->_normalize_issn($issn)) ? $this->issn.' '.$this->issn_str : false;
        $this->log .= 'Cover download: Checking  for ISSN: '.$issn;
        $this->log .= ($status) ? ' (valid issn)<br>' : ' (invalid issn)';
        if (!$status) return $status;

        // Check if a fixed cover exists
        if ($this->check_local_manual()) {
            $this->log .= '&gt; Found manually provided cover<br>';
            return true;
        } else {
            $this->log .= '&gt; No manually provided local cover found<br>';
        }

        // Still going? Check if one was already downloaded
        $status = $this->check_local_api();

        // Got a hit? Check if we shall download a new one because it's too old
        if ($status) {
            $this->log .= '&gt; Cover already downloaded by api<br>';
            if ((time() - $this->cover_age) > ($this->recheck_api_age * 60*60*24)) {
                $this->log .= '&gt; Cover is older than recheck_api_age value<br>';
                $status = false; //we keep going, even though a cover existed
            }
        } else {
            $this->log .= '&gt; Cover not downloaded by api already<br>';
        }

        // Ok, got no manual cover, no api cover (or it is too old), so use
        // the directlink if provided
        if (!$status && $directlink) {
            $status = _save_image($directlink);
            $this->log .= ($status) ? '&gt; Sucessfully downloaded via provided direct download link<br>' : '&gt; Failed downloading via provided direct download link<br>';
        }


        // Go for the downloading
        // If a custom url was provided, try this first
        if (!$status && $this->custom_api_url) {
            $status = get_user_cover_api();
            $this->log .= ($status) ? '&gt; Sucessfully downloaded via user cover api<br>' : '&gt; Failed downloading via user cover api<br>';
        }

        // Got no cover yet, try specific publisher, if provided
        // @note: currently we provide not way to just try each issn with each publisher - this can easily become a problem
        if (!$status && $this->publisher) {
            foreach ($this->src_publisher AS $publisher => $enabled) {
                if ($this->publisher == strtolower($publisher) && $enabled == 1) {
                    $status = call_user_func(array($this, 'get_publisher_'.$publisher));
                    $this->log .= ($status) ? '&gt; Sucessfully downloaded cover from publisher '.$publisher.'<br>' : '&gt; Failed downloading cover from publisher '.$publisher.'<br>';
                    if ($status) break;
                }
            }
        }

        //OK, still no cover? Let's try the generic sources
        if (!$status) {
            foreach ($this->src_genric AS $source => $enabled) {
                if ($enabled == 1) {
                    $status = call_user_func(array($this, 'get_generic_'.$source));
                    $this->log .= ($status) ? '&gt; Sucessfully downloaded cover from generic: '.$source.'<br>' : '&gt; Failed downloading cover from generic: '.$source.'<br>';
                    if ($status) break;
                }
            }
        }

        $this->log .= $this->cover_url;
    }



    /**
     * @brief   Check if manually added cover exists
     *
     * @return \b true if cover is found, else false
     */
    public function check_local_manual() {
        foreach ($this->cover_extensions as $ext) {
            $img = $this->folder_fixed . $this->issn.".$ext";
            if(file_exists($img)) {
                $this->cover_type   = $ext;
                $this->cover_path   = $img;
                $this->cover_binary = file_get_contents($img);
                $this->cover_age    = filemtime($img);

                return true;
                break;
            }
        }

        // Wer are here, so nothing was found
        $this->_reset_properties();
        return false;
    }


    /**
     * @brief   Check if downloaded cover exists already
     *
     * @todo    Maybe merge it with check_local_manual()
     *
     * @return \b true if cover is found, else false
     */
    public function check_local_api() {
        foreach ($this->cover_extensions as $ext) {
            $img = $this->folder_dl . $this->issn.".$ext";
            if (file_exists($img)) {
                $this->cover_type   = $ext;
                $this->cover_path   = $img;
                $this->cover_binary = file_get_contents($img);
                $this->cover_age    = filemtime($img);

                return true;
                break;
            }
        }

        // Wer are here, so nothing was found
        $this->_reset_properties();
        return false;
    }


    /**
     * @brief   Any api that works with the issn appended to the url
     *
     * It's kind of replacement for $cfg->covers->api  in original Journaltouch
     * config file. Most likely such a thing does not exist anyway...
     *
     * @todo    Just assumes it is a jpg...
     *
     * @return \b true if cover is found, else false
     */
    public function get_user_cover_api($ext = 'jpg') {
        $url = $this->custom_api_url.$this->issn;

        // Just save, nothing much to do
        $status = $this->_save_image($url, $ext);

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   SCRAPE cover from "Association for Computing Machinery ACM"
     *
     * @note    2015-10-25: broken - best use: https://dl.acm.org/pubs.cfm
     *
     * @return \b true if cover is found, else false
     */
    public function unavailable_get_publisher_ACM() {
        //$issn = '0730-0301';
        //$url = "http://dl.acm.org/results.cfm?h=1&dl=ACM&dim=3215&dimoreprod=3215&dimgroup=3215&dimtab=3215&query=";
    }


    /**
     * @brief   Direct download cover from De Gruyter
     *
     * @note    2015-10-27: Works fine
     *
     * @return \b true if cover is found, else false
     */
    public function get_publisher_degruyter() {
        // De Gruyter. Test case: 0341-4183 - http://www.degruyter.com/doc/cover/s03414183.jpg
        $base_url   = 'http://www.degruyter.com/doc/cover/';
        $file_name  = 's'.$this->issn_str;
        $ext        = 'jpg';

        $url = $base_url.$file_name.'.'.$ext;

        $status = $this->_save_image($url, $ext);

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   Direct download cover from Elsevier
     *
     * @note    2015-10-27: Works fine
     *
     * @return \b true if cover is found, else false
     */
    public function get_publisher_elsevier() {
        // Elesevier. Test case: 0361-3682 - http://ars.els-cdn.com/content/image/S03613682.gif
        $base_url   = 'http://ars.els-cdn.com/content/image/';
        $file_name  = 'S'.$this->issn_str;
        $ext        = 'gif';

        $url = $base_url.$file_name.'.'.$ext;

        $status = $this->_save_image($url, $ext);

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   SCRAPE cover from "Institute of Electrical and Electronics Engineers IEEE"
     *
     * @note    2015-10-27: currently broken
     *
     * @return \b true if cover is found, else false
     */
    public function unavailable_get_publisher_IEEE() {
        //$issn = '1540-7977';
        //$url = "http://ieeexplore.ieee.org/search/searchresult.jsp?matchBoolean%3Dtrue%26searchField%3DSearch_All%26queryText%3D%28%28p_ISSN%3A";
        //$url .= $issn;
        //$url .= "%29+AND+p_Title%3Afront+cover%29&sortType=desc_p_Publication_Year&pageNumber=1&resultAction=SORT";
    }


    /**
     * @brief   SCRAPE cover from "Emerald"
     *
     * @note    2015-10-27: currently broken
     *
     * @return \b true if cover is found, else false
     */
    public function unavailable_get_publisher_Emerald() {
        //$issn = '0307-4803';
        //$url = "https://www.emeraldinsight.com/rss/";
        //$url .= $issn.'.xml';
    }


    /**
     * @brief   SCRAPE cover from "Sage"
     *
     * @note    2015-10-27: Works fine
     *
     * @return \b true if cover is found, else false
     */
    public function get_publisher_sage() {
        //$issn = '0003-1224';
        //http://www.sagepub.com/productSearch.nav?siteId=sage-us&prodTypes=Journals&q=0090-5747
        $base_url   = 'https://uk.sagepub.com/en-gb/eur/product/';
        $url = $base_url.$this->issn;
        $ext        = 'jpg';

        $raw = file_get_contents($url);
        $newlines = array("\t","\n","\r","\x20\x20","\0","\x0B");
        $content = str_replace($newlines, "", html_entity_decode($raw));

        $base = '<div class="media-master">';
        $start = strpos($content, $base);
        $end   = 300;
        $img_tag_part = substr($content, $start, $end);
        $pattern = '/^.*<img.*sage-thumbnail.*data-original=\"(.*).jpg/';
        preg_match($pattern, $img_tag_part, $matches);

        $status = false;
        if (isset($matches[1])) {
            $img = $matches[1];
            $img_url = 'https://www.sagepub.com/'.$img.'.jpg';
            $status = $this->_save_image($img_url, $ext);
        }

        if (!$status) $this->_reset_properties();
        return $status;
    }



    /**
     * @brief   SCRAPE cover from "Springer"
     *
     * @note    2015-10-27: Works fine
     *
     * @return \b true if cover is found, else false
     */
    function get_publisher_springer() {
        //$issn = '0001-5903';
        $base_url = "https://www.springer.com/?SGWID=0-102-24-0-0&searchType=ADVANCED_CDA&isbnIssn=";
        $url = $base_url.$this->issn;
        $ext        = 'jpg';

        $raw = file_get_contents($url);
        $newlines = array("\t","\n","\r","\x20\x20","\0","\x0B");
        $content = str_replace($newlines, "", html_entity_decode($raw));

        $img_base = 'https://images.springer.com/sgw/journals/small/';
        $start = strpos($content, $img_base);
        $end   = strlen($img_base) + 20;
        $img_tag_part = substr($content, $start, $end);

        $pattern = '/^.*https:\/\/images.springer.com\/sgw\/journals\/small\/(.*)" .*/';
        preg_match($pattern, $img_tag_part, $matches);

        $status = false;
        if (isset($matches[1])) {
            $img = $matches[1];
            $img_url = 'https://images.springer.com/sgw/journals/medium/'.$img;
            $status = $this->_save_image($img_url, $ext);
        }

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   SCRAPE cover from "Wiley"
     *
     * @note    2015-10-27: Would work well, but Google doesn't like bots...
     *                      You could use any other engine (metagr worked well), yet...
     *
     * @return \b true if cover is found, else false
     */
    function unavailable_get_publisher_Wiley() {
        //$issn = '0138-4988';
        //http://www.wiley-vch.de/publish/en/journals/alphabeticIndex/
        //http://www.google.com/cse?cx=012924211411175064432%3Apr36ll8e0my&q=0138-4988&sa=Website+Search&cof=FORID%3A0#gsc.tab=0&gsc.page=1&gsc.q=0138-4988
        // BESSER: https://www.google.com/cse?cx=012924211411175064432:pr36ll8e0my&q=0138-4988&sa=Website+Search&cof=FORID:0&nojs=1
        //$base_url = "https://www.google.com/cse?cx=012924211411175064432:pr36ll8e0my&sa=Website+Search&cof=FORID:0&nojs=1&q=";
        $base_url = "https://www.metager.de/meta/meta.ger3?pers=yes&sprueche=on&lang=all&mm=and-stop&ui=de&QuickTips=yes&langfilter=yes&encoding=utf8&maps=on&focus=web&eingabe=http%3A%2F%2Fwww.wiley-vch.de%2F+";
        $url = $base_url.$this->issn;
        $ext      = 'gif';

        $raw = file_get_contents($url);
        $newlines = array("\t","\n","\r","\x20\x20","\0","\x0B");
        $content = str_replace($newlines, "", html_entity_decode($raw));

        $img_base = 'http://www.wiley-vch.de/publish/en/journals/alphabeticIndex/';
        $start = strpos($content, $img_base);
        $end   = strlen($img_base) + 4;
        $img_tag_part = substr($content, $start, $end);

        $pattern = '/^.*http:\/\/www.wiley-vch.de\/publish\/en\/journals\/alphabeticIndex\/(.*)/';
        preg_match($pattern, $img_tag_part, $matches);

        $status = false;
        if (isset($matches[1])) {
            $img = $matches[1];
            $img_url = 'http://www.wiley-vch.de/vch/journals/'.$img.'/'.$img.'.gif';
            $status = $this->_save_image($img_url, $ext);
        }

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   SCRAPE cover from JournalTocs
     *
     * @note    2015-10-27: Works fine
     *
     * @return \b true if cover is found, else false
     */
    public function get_generic_JournalTocs() {
        $base_url   = "http://www.journaltocs.ac.uk/index.php?action=tocs&issn=";
        $url        = $base_url.$this->issn;

        // Open toc page
        $toc    = implode('', file($url));
        $pos1   = strpos($toc, 'Journal Cover');
        $sub    = substr($toc, 0, $pos1);

        $p2     = strrpos($sub, 'src=');
        $sub    = substr($sub, $p2+4, strlen($sub)-2);
        $sub    = str_replace("\"", ' ', $sub);
        list($cover_url) = sscanf($sub, "%s");

        // JournalTocs has no cover
        if ($cover_url == 'http://www.journaltocs.ac.uk/images/no_cover.jpg') {
            return false;
        }

        $status = $this->_save_image($cover_url);

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   SCRAPE cover from Lehmanns
     *
     * @note    2015-10-27: Works fine
     *
     * @return \b true if cover is found, else false
     */
    public function get_generic_Lehmanns() {
        //$issn = '0018-3830';
        $base_url = "http://size.lehmanns.de/index.php?request=handler_public_ZSSuche.titelsuche&anzeigemenge=1&action=suchen&issn=";
        $url = $base_url.$this->issn; // bindestrich muss erhalten bleiben!

        $raw = file_get_contents($url);
        $newlines = array("\t","\n","\r","\x20\x20","\0","\x0B");
        $content = str_replace($newlines, "", html_entity_decode($raw));

        if (strpos($content, '/styles/Pics/placeholdercover-listing.jpg')) {
            return false;
        }

        $img_base = '/pics/artikel/';
        $start = strpos($content, $img_base);
        $end   = strlen($img_base) + 20;
        $img_tag_part = substr($content, $start, $end);

        $pattern = '/^.*\/pics\/artikel\/(.*)"/';
        preg_match($pattern, $img_tag_part, $matches);

        $status = false;
        if (isset($matches[1])) {
            $img = $matches[1];
            $img_url = 'http://size.lehmanns.de'.$img_base.$img;
            preg_match('/^.*\.(.*)/', $img, $matches);
            $ext = $matches[1];
            $status = $this->_save_image($img_url, $ext);
        }

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   Download cover from Subscribe to Journals
     *
     * @note    2015-10-27: Works fine. Pretty ok, but large covers are square
     *          (white border), yet cropped pretty good
     *
     * @return \b true if cover is found, else false
     */
    public function get_generic_SubscribeToJournals($large = false) {
        // small: http://subscribetojournalsonline.com/image/cache/data/0364_0094-140x180.jpg
        // large: http://subscribetojournalsonline.com/image/cache/data/0963_9268-600x600.jpg
        $base_url   = "http://subscribetojournalsonline.com//image/cache/data/";
        $file_name  = str_replace('-', '_', $this->issn);
        $file_name .= ($large) ? '-600x600' : '-140x180';
        $ext        = 'jpg';

        $url        = $base_url.$file_name.'.'.$ext;

        $status = $this->_save_image($url, $ext);

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   Download cover from Journal Covers Database (STM)
     *
     * @note    2015-10-27: Works fine
     *
     * @return \b true if cover is found, else false
     */
     public function get_generic_STMcovers() {
        $base_url   = "http://www.stmcovers.com/in/";
        $ext        = 'jpg';

        $url = $base_url.$this->issn_str.'.'.$ext;

        $status = $this->_save_image($url, $ext);

        if (!$status) $this->_reset_properties();
        return $status;
    }


    /**
     * @brief   Check if we got a valid url. Don't download what does not exist :)
     *
     * @return \b true if file is found, else false
     */
    private function _verify_http_response_code($url) {
        // Hmm, sometimes an empty url is passed, but why...
        if (!$url) return false;

        $headers = get_headers($url);
        $code = substr($headers[0], 9, 3);

        if ($code == '200' || $code == '301') {
            return true;
        } else {
            return false;
        }
    }



    /**
     * @brief   Save downloaded cover
     *
     * @param $url          \b STR  Cover url
     * @param $ext          \b STR  opt: Extension of cover. Tries to guess extension if not provided, defaults to jpg
     * @param $custom_path  \b STR  Umm, not really used yet
     *
     * @return \b true if file is found, else false
     */
    private function _save_image($url, $ext = '', $custom_path = '') {
        $path       = ($custom_path) ? $custom_path : $this->folder_dl;

        // Try to guess extension if not provided
        if (!$ext) {
            $pattern = '/.*\.(jpg|gif|png)/';
            preg_match($pattern, $url, $matches);
            $ext = (isset($matches[1])) ? $matches[1] : 'jpg';
        }

        $img_path   = $path.$this->issn.'.'.$ext;

        if($this->_verify_http_response_code($url)) {
            $this->cover_type   = $ext;
            $this->cover_path   = $img_path;
            $this->cover_binary = file_get_contents($url);
            $this->cover_size   = round(strlen($this->cover_binary) / 1024, 2);
            $this->cover_age    = time();

            // Don's save really small files (less than 1 KB)
            if ($this->cover_size < 1) {
                $status = false;
            } else {
                $status = file_put_contents($img_path, $this->cover_binary);
            }

            if (!$status) $this->log .= 'Saving file failed - path problem?<br>';

            return true;
        } else {
            return false;
        }
    }


    /**
     * @brief   Check Imagick version
     *
     * @return \b STR Version number
     */
    private function _get_imagick_version() {
        // the API version number will be returned (currently 6.8.8), or 0 on failure
        // the module version number is a different value (currently 3.1.2)

        $imagick_v = 0;

        if (extension_loaded('imagick') && class_exists('Imagick')) {
            $imagick = new Imagick();
            $imagick_info = $imagick->getVersion();

            $imagick_vs = $imagick_info['versionString'];
            preg_match('/ImageMagick ([\d]*\.[\d]*\.[\d]*)/', $imagick_vs, $imagick_vs_return);
            $imagick_v = $imagick_vs_return[1];
        }

        return $imagick_v;
    }


    /**
     * @brief   Resize cover via Imagick (if avialable)
     *
     * @todo    This is not yet used...
     *
     * @param $imagePath   \b STR  Path to image to resize
     *
     * @return \b true if issn is ok, false if not
     */
    public function resize_image($imagePath) {
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            $thumb = new Imagick();
            $thumb->readImageBlob($this->cover_binary);
            $thumb->resizeImage(170,254,Imagick::FILTER_LANCZOS,1);
            $thumb->writeImage($this->folder_dl.'mythumb.gif');
            $thumb->clear();
            $thumb->destroy();

            return $this->folder_dl.'mythumb.gif';
        }
    }



    /**
     * @brief   This is only for quick testing all sources. Development...
     *
     * @return Shows source, cover and size for each source
     */
    public function test_downloads() {
        $this->folder_dl = '../../data/covers/api/test/';

        // Test JournalTocs
        $this->_normalize_issn('0098-8847');
        $this->get_generic_JournalTocs();
        echo 'JournalTocs ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();

        // Test Lehmanns
        $this->_normalize_issn('0958-1669');
        $this->get_generic_Lehmanns();
        echo 'Lehmanns ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();

        // Test STMcovers
        $this->_normalize_issn('1765-4629');
        $this->get_generic_STMcovers();
        echo 'STMcovers ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();

        // Test SubscribeToJournals
        $this->_normalize_issn('0963-9268');
        $this->get_generic_SubscribeToJournals();
        echo 'SubscribeToJournals ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();

        // Test DeGruyter
        $this->_normalize_issn('0341-4183');
        $this->get_publisher_DeGruyter();
        echo 'DeGruyter ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();

        // Test Elsevier
        $this->_normalize_issn('0361-3682');
        $this->get_publisher_Elsevier();
        echo 'Elsevier ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();

        // Test Sage
        $this->_normalize_issn('0001-8392');
        $this->get_publisher_Sage();
        echo 'Sage ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();

        // Test Springer
        $this->_normalize_issn('0001-5903');
        $this->get_publisher_Springer();
        echo 'Springer ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();

        // Test Wiley
        $this->_normalize_issn('0138-4988');
        $this->unavailable_get_publisher_Wiley();
        echo 'Wiley ('.$this->cover_size.' KB)<br><img src="'.$this->cover_path.'"><br>';
        $this->_reset_properties();
    }
}



?>
