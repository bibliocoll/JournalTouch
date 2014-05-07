<?php
/**
 * List Journals
 *
 * Read data from a file and put it in an array
 * Default is CSV, add a function for other formats
 * 
 * Time-stamp: "2014-04-25 12:18:51 zimmel"
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
    protected $csv_week;
    protected $csv_separator;
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
        $this->csv_week = $config['csv']['week'];
        if (!empty($this->csv_filter)) {
        $this->filters = $config['filter'];
        }

        $this->placeholder = $config['img']['placeholder'];
        $this->coverAPI = $config['img']['api'];
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

    function isCurrent($week,$issn) {
        $timespan = (!empty($week) ? $week+1 : "0");
        $curWeek = date("W");
        if ($timespan >= $curWeek) {
            return true;
        } else { 
            /* extra compare loop; compare with a json issn list of current journals */
            /* uncomment this if you do not want to compare with a list or you do not have a file */
            if (file_exists($this->updates)) {
            $json = $this->updates;
            $arrCmp = json_decode(file_get_contents($json), true);
            if ($this->search_array($issn, $arrCmp)) {
                return true;
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
            $img = "http://www.vub.de/cover/data/".$issn."/max/true/de/mpi/cover.png";
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
                $week = $data[$this->csv_week];
                $filter = (!empty($data[$this->csv_filter]) ? strtolower($data[$this->csv_filter]) : "any");
                $topJ = (!empty($data[$this->csv_important]) ? "topJ" : "");
                $img = $this->getCover($myISSN);
                $new = ($this->isCurrent($week,$myISSN) ? true : false);

                $journals[] = array(
                    'id' => $myISSN,
                    'title' => $data[$this->csv_col_title],
                    'filter' => $filter,
                    'topJ' => $topJ,
                    'week' => $week,
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