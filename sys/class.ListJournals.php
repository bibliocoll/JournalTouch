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

    function getJournals() {

        $row = 1;
        $journals = array();
 
        if (($handle = fopen($this->csv_file, "r")) !== FALSE) {
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

                $journals[] = array(
                    'id' => $myISSN,
                    'title' => $data[$this->csv_col_title],
                    'filter' => $filter,
                    'topJ' => $topJ,
                    'date' => $date,
                    'img' => $img,
                    'new' => $new
                );

            } 
            fclose($handle);
        }
        return $journals;
    }
}
?>