<?php
/**
 * List Journals
 *
 * Read data from a file and put it in an array
 * Default is CSV, add a function for other formats
 *
 * Time-stamp: "2014-08-19 11:03:34 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @author Tobias Zeumer <tobias.zeumer@tuhh.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 *
 * @todo (Ideas)
 * - Maybe fetch more infos per http://amsl.technology/issn-resolver/
 * -- Maybe automatically fetch publish frequency and create "paging" for journals?
 * - Create some kind of (daily) cached version of the front page so it loads
 *     faster (not having to generate everything every time)
 */
class ListJournals
{
    /// \brief \b OBJ @see config.php
    protected $csv_file;
    /// \brief \b OBJ @see config.php
    protected $csv_col;
    /// \brief \b OBJ @see config.php
    public $covers;
    /// \brief \b OBJ @see config.php
    protected $api_all;
    /// \brief \b OBJ @see config.php
    public $jt;
    /// \brief \b ARY @see config.php
    public $filters;
    /// \brief \b OBJ @see config.php
    public $prefs;

    /// \brief \b ARY All tags, key = tagname, value = total count of tag usage
    public $tagcloud = array();



    /**
     * @brief     set properties, be merry
     *
     * @note        The mapping is unnecessary, but maybe improves readability above
     *                    just $this->cfg = $cfg.
     *
     * @return \b void
     */
    public function __construct($cfg)
    {
        $this->csv_file = $cfg->csv_file;
        $this->csv_col  = $cfg->csv_col;
        $this->covers   = $cfg->covers;
        $this->api_all  = $cfg->api->all;
        $this->jt       = $cfg->api->jt;
        $this->prefs    = $cfg->prefs;
        $this->filters  = (!empty($this->csv_col->filter)) ? $cfg->filters : false;
        $this->sys      = $cfg->sys;
    }


    /**
     * @brief   Helper function for compare
     *
     * @todo    Put in helper class with other things
     *
     * @return \b BOL True if needle is found
     */
    function search_array($needle, $haystack) {
        if(in_array($needle, $haystack)) return true;

        foreach($haystack as $element) {
            if(is_array($element) && $this->search_array($needle, $element))
                return true;
        }
        return false;
    }


    /**
     * @brief   Date comparison
     *
     * @todo    Hmm, checking just by date is pretty pointless, because it could
     *          easily done via javascript in index.php by just returning the date
     *          from input.csv.
     *          This method should just override this date, if there is a json
     *          list - but I guess this won't be the usual case...
     *
     * @return \b DAT A date, if it is defined as 'current'
     * @return \b BOL Else return false
     */
    function isCurrent($date,$issn) {
        /* unless we use PHP 5.3 (with DateTime::sub), we need to add a timespan for comparison */
        $td = strtotime($date);
        $cdate = date("Y-m-d", strtotime("+{$this->api_all->is_new_days} day", $td));
        /* if there is a $date (e.g. from csv), compare with current date */
        $curDate = new DateTime(); // today
        $myDate     = new DateTime($cdate);

        if ($myDate >= $curDate) {
            return $date;
            /* if csv_date is empty, check with a json list of current journals */
        }
        elseif ($this->jt->upd_show && file_exists($this->jt->outfile)) {
            $json = $this->jt->outfile;
            $arrCmp = json_decode(file_get_contents($json), true);
            if ($this->search_array($issn, $arrCmp)) {
                foreach ($arrCmp as $k1=>$v) {

                    foreach ($v as $k2 => $r) {
                        if($r == $issn) {
                            $date = next($v);
                        }
                    }
                }
                return $date;
            } else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    /* get updates from json list */
    function getJournalUpdates() {
        if (file_exists($this->jt->outfile)) {
            $json = $this->jt->outfile;
            $journalUpdates = json_decode(file_get_contents($json), true);
            return $journalUpdates;
        } else {
            return false;
        }
    }


    /**
     * @brief   Get cover file for specified issn
     *
     * @note    As of version 0.4.0 covers are downloaded via the updater.
     *          Therefore the api call is removed here
     *
     * @return \b STR Path to cover file
     */
    function getCover($issn) {
        $extensions = array('jpg', 'gif', 'png');

        // First check folder with manually added covers
        foreach ($extensions as $ext) {
            $img = $this->sys->data_covers.$issn.'.'.$ext;
            if (file_exists($img)) return $img;
        }

        // Second: check downloaded covers
        foreach ($extensions as $ext) {
            $img = $this->sys->data_covers.'/api/'.$issn.'.'.$ext;
            if (file_exists($img)) return $img;
        }

        // All failed, return transparent.gif to cancel loading animation
        // Placeholder is set via css - should be more efficient with loading
        return 'img/transparent.gif';
    }


    /**
     * @brief   Returns all tags as tagcloud (prepared HTML)
     *
     * @todo    $cssClasses and especially $ignoreTag aren't good parameters.
     *          And further: only $limit is configurable in config.php...
     *
     * @param $limit \b INT Minimum count that a tag has to be used to
     *                                                        show in the cloud
     * @param $cssClasses \b INT Number of font sizes to show
     * @param $ignoreTag \b INT Tag that should not be shown in the cloud.
     *                          Usually NoTag that is set in
     *                          ListJournals::getJournals(). If you really
     *                          want to show it (or hide something else),
     *                          change it. Showing NoTag is useful for
     *                          "editorial" work :)
     * @return \b STR <p>aragraph with tagcloud
     */
    function getTagcloud($limit = 0, $cssClasses = 5, $ignoreTag = 'NoTag') {
        // if called directly with a limit setting use it. Otherwise use setting from config.php
        if ($limit == 0 && $this->prefs->min_tag_freq) $limit = $this->prefs->min_tag_freq;

        if (!empty($this->tagcloud)) {
            $countcloud = $this->tagcloud;
            if ($limit) {
                foreach ($countcloud AS $tag => $count) {
                    if ($count < $limit) unset($countcloud[$tag]);
                }
            }
            if (isset($countcloud[$ignoreTag])) unset($countcloud[$ignoreTag]);

            $tag_min    = min($countcloud);
            $tag_max    = max($countcloud);

            $cloud = '';
            foreach ($this->tagcloud AS $tag => $count) {
                if ($count >= $limit) {
                    if ($tag == $ignoreTag) {
                        unset($this->tagcloud[$tag]);
                    }
                    else {
                        $multiplier = $this->GetTagSizeLogarithmic($count, $tag_min, $tag_max, 1, $cssClasses+1);
                        $css = 'tagcloud'.$multiplier;
                        $cloud .= '<span class="'.$css.'"><a class="filter" id="tag-'.$tag.'" href="javascript:;">'.$tag.'</a> ('.$count.')</span> ';
                    }
                }
            }
            $cloud = "<p align=\"center\">$cloud</p>";
            return $cloud;
        } else {
            return '';
        }
    }


    /**
     * @brief   Gets a logarithmic value for given values.
     *
     * @param $count        \b INT    Total count of tag
     * @param $mincount     \b INT    Lowest overall tag count
     * @param $maxcount     \b INT    Highest overall tag count
     * @param $minsize      \b INT    Minimum (font) size
     * @param $maxsize      \b INT    Maximum (font) size
     * @param $tresholds    \b INT    Which $minsize values to ignore
     *
     * @return \b INT A value between $minsize and $maxsize
     */
    function GetTagSizeLogarithmic( $count, $mincount, $maxcount, $minsize, $maxsize, $tresholds = 1 ) {
        if( !is_int($tresholds) || $tresholds<2 ) {
            $tresholds = $maxsize-$minsize;
            $treshold = 1;
        }
        else {
            $treshold = ($maxsize-$minsize)/($tresholds-1);
        }
        $a = $tresholds*log($count - $mincount+2)/log($maxcount - $mincount+2)-1;
        return round($minsize+round($a)*$treshold);
    }


    /**
     * @brief   Reads all csv columns into an array.
     *
     * @todo
     * - Tagcloud: don't use underscores for spaces. Multi array or something instead
     *
     * @return
     * - \b ARY array('id' => ISSN, 'title' => x, 'filter' => x,
     *                  'topJ' => x, 'date' => x, 'img' => x, 'new' => x,
     *                  'tags' => x)
     * - \b ARY ListJournals::$tagcloud
     */
    function getJournals() {
        $row = 1;
        $journals = array();

        if (($handle = fopen($this->csv_file->path, "r")) !== FALSE) {
            $tagcloud = array();
            $no_date = '1970-01-01'; // Just use the first date from unixtime - makes it easy to check anywhere else for a "non-date", without breaking anything
            while (($data = fgetcsv($handle, 1000, $this->csv_file->separator)) !== FALSE) {
                $num = count($data);

                /* check for alternative ISSN if strlen is < 1 */
                //$myISSN = (strlen($data[$this->csv_col->p_issn] < 1) ? $data[$this->csv_col->e_issn] : $data[$this->csv_col->p_issn]);

                $myISSN = '';
                if (valid_issn($data[$this->csv_col->p_issn], TRUE) === TRUE) {
                    $myISSN = $data[$this->csv_col->p_issn];
                } elseif (valid_issn($data[$this->csv_col->e_issn], TRUE) === TRUE) {
                    $myISSN = $data[$this->csv_col->e_issn];
                } else {
                    //no valid ISSN, lets skip this one
                    continue;
                }

                $row++;
                $date = ($data[$this->csv_col->date]) ? $data[$this->csv_col->date] : $no_date;
                $filter = (!empty($data[$this->csv_col->filter]) ? strtolower($data[$this->csv_col->filter]) : "any");
                $topJ = (!empty($data[$this->csv_col->important]) ? "topJ" : "");
                $img = $this->getCover($myISSN);
                $new = $this->isCurrent($data[$this->csv_col->date],$myISSN);

                // Meta
                $metaPrint = $metaOnline = $metaGotToc = $metaShelfmark = $metaWebsite = '';
                if ($this->prefs->show_metainfo_list == true || $this->prefs->show_metainfo_toc) {
                    $metaPrint    = (!empty($data[$this->csv_col->metaPrint]) ? 'fi-page-copy' : '');
                    $metaOnline = (!empty($data[$this->csv_col->metaOnline]) ? 'fi-download' : '');
                    $metaGotToc = $data[$this->csv_col->metaGotToc];
                    $metaShelfmark = (!empty($data[$this->csv_col->metaShelfmark]) ? $data[$this->csv_col->metaShelfmark] : '');
                    $metaWebsite = (!empty($data[$this->csv_col->metaWebsite]) ? $data[$this->csv_col->metaWebsite] : '');
                }

                // Tagcloud
                $tags = '';
                if ($this->prefs->menu_show_tagcloud == true) {
                    $tags_row = array();
                    if (!empty($data[$this->csv_col->tags])) {
                        // remove space between comma and tag; better readable but...
                        $tags_row = str_replace(', ', ',', $data[$this->csv_col->tags]);
                        // ...multi word tags have to be "js ready"
                        $tags_row = str_replace(' ', '_', $tags_row);
                        $tags_row = explode(',', $tags_row);
                    } else {
                        $tags_row[] = 'NoTag';
                    }
                    // move row tags to our "big cloud" (tags from all rows)
                    $tagcloud = array_merge($tagcloud, $tags_row);
                    $tags = implode(' tag-', $tags_row);
                    $tags = 'tag-'.$tags;
                }

                $journals[] = array(
                    'id' => $myISSN,
                    'title' => $data[$this->csv_col->title],
                    'filter' => $filter,
                    'topJ' => $topJ,
                    'date' => $date,
                    'img' => $img,
                    'new' => $new,
                    'metaPrint' => $metaPrint,
                    'metaOnline' => $metaOnline,
                    'metaGotToc' => $metaGotToc,
                    'metaShelfmark' => $metaShelfmark,
                    'metaWebsite' => $metaWebsite,
                    'issn' => $myISSN,
                    'tags' => $tags
                );
            }
            fclose($handle);

            if ($this->prefs->default_sort_date) {
                usort($journals, array($this, "_sort_nested_array_by_date"));
            }

            // set our "big cloud" as class property (and sort alphabetically)
            if (isset($tagcloud)) {
                $this->tagcloud = array_count_values($tagcloud);
                ksort($this->tagcloud);
            }
        }
        return $journals;
    }


    /**
     * @brief   usort sorting method: by date
     *
     * @note:   We _could_ add another one to sort by alphabet, so it wouldn't
     *          matter if journals.csv is sorted wrong initially...
     *
     * @return \b ARY Sorted by child node 'date'
     */
    private function _sort_nested_array_by_date($a, $b) {
        $a = strtotime($a['date']); $b = strtotime($b['date']);

        return ($a == $b) ? 0 : ($a>$b ? - 1 : 1);
    }
}
?>
