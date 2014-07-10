<?php
/**
 * List Journals
 *
 * Read data from a file and put it in an array
 * Default is CSV, add a function for other formats
 *
 * Time-stamp: "2014-05-28 12:07:48 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 *
 * @todo (Ideas)
 * - Maybe fetch more infos per http://amsl.technology/issn-resolver/
 * -- Maybe automatically fetch publish frequency and create "paging" for journals?
 * - Switch config.ini > alink automatically if in network with fulltext access
 * - Use SFX to directly download article (or link to print)
 */
class ListJournals
{

    protected $csv_file;
    protected $csv_col_title;
    protected $csv_col_issn;
    protected $csv_col_issn_alt;
    protected $csv_important;
    protected $csv_filter;
    protected $csv_date;
    protected $csv_separator;
    protected $updates_display;
    protected $placeholder;
    protected $coverAPI;

    /// \brief \b STR Keeps value of csv's column 10
    protected $csv_tags;
    /// \brief \b ARY All tags, key = tagname, value = total count of tag usage
    public $tagcloud = array();

    public function __construct()
    /* load some configuration */
    {
        $config = parse_ini_file('config/config.ini', TRUE);
        $this->csv_file = $config['csv']['file'];
        $this->csv_col_title = $config['csv']['title'];
        $this->csv_col_issn = $config['csv']['issn'];
        $this->csv_col_issn_alt = $config['csv']['issn_alt'];
        $this->csv_separator = $config['csv']['separator'];
        $this->csv_important = $config['csv']['important'];
        $this->csv_filter = $config['csv']['filter'];
        $this->csv_date = $config['csv']['date'];
        if (!empty($this->csv_filter)) {
        $this->filters = $config['filter'];
        }
        $this->placeholder = $config['img']['placeholder'];
        $this->coverAPI = $config['img']['api'];
        $this->updates_display = $config['updates']['display'];
        $this->updates = $config['updates']['outfile'];


        $this->prefs = $config['prefs'];
        $this->csv_metaPrint = $config['csv']['metaPrint'];
        $this->csv_metaOnline = $config['csv']['metaOnline'];
        $this->csv_metaGotToc = $config['csv']['metaGotToc'];
        $this->csv_metaShelfmark = $config['csv']['metaShelfmark'];
        $this->csv_metaWebsite = $config['csv']['metaWebsite'];

        $this->csv_tags = $config['csv']['tags'];
    }

    /* helper function for compare (TODO: put in helper class with other things) */
    function search_array($needle, $haystack) {
        if(in_array($needle, $haystack)) {
            return true;
        }
        foreach($haystack as $element) {
            if(is_array($element) && $this->search_array($needle, $element))
                return true;
        }
        return false;
    }

    /* date comparison; return a date, if it is defined as 'current', else return false */
    function isCurrent($date,$issn) {
        /* unless we use PHP 5.3 (with DateTime::sub), we need to add a timespan for comparison */
        $td = strtotime($date);
        $cdate = date("Y-m-d", strtotime("+1 month", $td));
        /* if there is a $date (e.g. from csv), compare with current date */
        $curDate = new DateTime(); // today
        $myDate   = new DateTime($cdate);

        if ($myDate >= $curDate) {
             return $date;
        /* if csv_date is empty, check with a json list of current journals */
        } else {
            if ($this->updates_display) {
                if (file_exists($this->updates)) {
                    $json = $this->updates;
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
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    function getCover($issn) {
        if ($this->coverAPI) {
            $img = ""; // fill in your API URL here or from config
        } else {
            $png = 'img/'.$issn.'.png';
            $jpg = 'img/'.$issn.'.jpg';
            $gif = 'img/'.$issn.'.gif';
            //  $img = (file_exists($png) ? $png : file_exists($jpg) ? $jpg : $this->placeholder);
            if (file_exists($png)) { $img = $png; } else if (file_exists($jpg)) {$img = $jpg;} else if (file_exists($gif)) {$img = $gif;} else {$img= $this->placeholder;};
        }
        return $img;
    }

    function getFilters() {
        if (!empty($this->filters)) {
            return $this->filters;
        } else {
            return false;
        }
    }


    /**
     * @brief   Returns all tags as tagcloud (prepared HTML)
     *
     * @param $limit      \b INT  Minimum count that a tag has to be used to
     *                            show in the cloud
     * @param $cssClasses \b INT  Number of font sizes to show
     * @param $ignoreTag  \b INT  Tag that should not be count as maximum,
     *                            usually NoTag that is set in
     *                            ListJournals::getJournals()
     * @return \b STR <p>aragraph with tagcloud
     */
    function getTagcloud($limit = 0, $cssClasses = 5, $ignoreTag = 'NoTag') {
        if (!empty($this->tagcloud)) {
            $countcloud = $this->tagcloud;
            if ($limit) {
              foreach ($countcloud AS $tag => $count) {
                if ($count < $limit) unset($countcloud[$tag]);
              }
            }
            if (isset($countcloud[$ignoreTag])) unset($countcloud[$ignoreTag]);

            $tag_min  = min($countcloud);
            $tag_max  = max($countcloud);

            $cloud = '';
            foreach ($this->tagcloud AS $tag => $count) {
              if ($count >= $limit) {
                if ($tag == $ignoreTag) {
                  $multiplier = $cssClasses;
                }
                else {
                  $multiplier = $this->GetTagSizeLogarithmic($count, $tag_min, $tag_max, 1, $cssClasses+1);
                }
                $css = 'tagcloud'.$multiplier;
                $cloud .= '<span class="'.$css.'"><a class="filter" id="tag-'.$tag.'" href="javascript:;">'.$tag.'</a> ('.$count.')</span> ';
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
     * @param $count      \b INT  Total count of tag
     * @param $mincount   \b INT  Lowest overall tag count
     * @param $maxcount   \b INT  Highest overall tag count
     * @param $minsize    \b INT  Minimum (font) size
     * @param $maxsize    \b INT  Maximum (font) size
     * @param $tresholds  \b INT  Which $minsize values to ignore
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
     *            'topJ' => x, 'date' => x, 'img' => x, 'new' => x,
     *            'tags' => x)
     * - \b ARY ListJournals::$tagcloud
     */
    function getJournals() {
        $row = 1;
        $journals = array();

        if (($handle = fopen($this->csv_file, "r")) !== FALSE) {
            $tagcloud = array();
            while (($data = fgetcsv($handle, 1000, $this->csv_separator)) !== FALSE) {
                $num = count($data);

                /* check for alternative ISSN if strlen is < 1 */
                $myISSN = (strlen($data[$this->csv_col_issn] < 1) ? $data[$this->csv_col_issn_alt] : $data[$this->csv_col_issn]);

                $row++;
                $date = $this->isCurrent($data[$this->csv_date],$myISSN);
                $filter = (!empty($data[$this->csv_filter]) ? strtolower($data[$this->csv_filter]) : "any");
                $topJ = (!empty($data[$this->csv_important]) ? "topJ" : "");
                $img = $this->getCover($myISSN);
                $new = ($this->isCurrent($data[$this->csv_date],$myISSN) ? true : false);

                // Meta
                $metaPrint = $metaOnline = $metaGotToc = $metaShelfmark = $metaWebsite = '';
                if ($this->prefs['showMeta'] == true) {
                  $metaPrint  = (!empty($data[$this->csv_metaPrint]) ? 'fi-page-copy' : '');
                  $metaOnline = (!empty($data[$this->csv_metaOnline]) ? 'fi-download' : '');
                  $metaGotToc = (!empty($data[$this->csv_metaGotToc]) && $data[$this->csv_metaGotToc] != 'false') ? true : false;
                  $metaGotToc = ($metaGotToc) ? 'fi-like' : 'fi-dislike';
                  $metaShelfmark = (!empty($data[$this->csv_metaShelfmark]) ? $data[$this->csv_metaShelfmark] : '');
                  $metaWebsite = (!empty($data[$this->csv_metaWebsite]) ? $data[$this->csv_metaWebsite] : '');
                }

                // Tagcloud
                $tags = '';
                if ($this->prefs['showTagcloud'] == true) {
                  $tags_row = array();
                  if (!empty($data[$this->csv_tags])) {
                    // remove space between comma and tag; better readable but...
                    $tags_row = str_replace(', ', ',', $data[$this->csv_tags]);
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
                    'title' => $data[$this->csv_col_title],
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
            // set our "big cloud" as class property (and sort alphabetically)
            if (isset($tagcloud)) {
              $this->tagcloud = array_count_values($tagcloud);
              ksort($this->tagcloud);
            }
        }
        return $journals;
    }
}
?>