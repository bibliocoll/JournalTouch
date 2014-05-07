<?php
/**
 * Get Users
 *
 * Get a list of users from database and return as array
 *
 * Time-stamp: "2014-04-24 17:54:56 zimmel"
 *
 * @author Daniel Zimmel <zimmel@coll.mpg.de>
 * @copyright 2014 MPI for Research on Collective Goods, Library
 * @license http://www.gnu.org/licenses/gpl.html GPL version 3 or higher
 */
class GetUsers 
{

    protected $userlist;

    public function __construct()
    /* load some configuration */
    {
        $config = parse_ini_file('config/config.ini', TRUE);
        $this->userlist = $config['users']['userlist'];
        $this->dbuser = $config['users']['dbuser'];
        $this->dbpass = $config['users']['dbpass'];
    }

    private function _sql_query ($sql, $db)
    {
        $result=mysql_query($sql, $db);

        if (!$result) {
            echo "Could not successfully run query<br /> ($sql)<br />from DB: " . mysql_error() .":".mysql_errno();
            exit;
        }
        return $result;
    }


    public function getUsers() {

        if ($this->userlist == true) {

// Get data from DB
            $db_user=$this->dbuser;
            $db_pass=$this->dbpass;
            $db_ext='drupal_extern';
            $db_int='drupal_intern';
            
            $sql_int="SELECT node.nid AS nid,
   node.title AS name,
   content_type_team_intern.field_aleph_value AS aleph,
   content_type_team_intern.field_alephid_value AS alephid,
   content_type_team_intern.field_team_id_value AS id
   FROM node 
   LEFT JOIN content_type_team_intern ON node.vid = content_type_team_intern.vid
   WHERE (node.type in ('team_intern')) AND (content_type_team_intern.field_aleph_value IS NOT NULL)";
            $sql_ext="SELECT node.nid AS nid,
   content_type_team.field_email_value AS email,
   node.type AS type
   FROM node 
   LEFT JOIN content_type_team ON node.vid = content_type_team.vid
   WHERE node.nid = ";
            
            $db_extern = mysql_connect('localhost', $db_user, $db_pass);
            $db_intern = mysql_connect('localhost', $db_user, $db_pass);
            
            mysql_select_db($db_int, $db_intern);
            $result_intern=$this->_sql_query($sql_int, $db_intern);
            
            $users=array();
            $error="";
            
            mysql_select_db($db_ext, $db_extern);
            
            while ($row_intern=mysql_fetch_assoc($result_intern)) {
                
                $result_extern=$this->_sql_query($sql_ext."'".$row_intern['id']."'", $db_extern);
                $row_extern=mysql_fetch_assoc($result_extern);
                if ($row_extern['email'])
                    {
                        $users[strtolower($row_extern['email'])]=array($row_intern['alephid'], $row_intern['aleph']);
                    }
                else
                    {
                        $error.="No account for ".$row_intern['name']."|".$row_intern['id']."|".$row_intern['alephid']."<br />";
                    }
            }
            mysql_close($db_extern); 
            mysql_close($db_intern); 
            
            $users['-please select your account-']='';
            ksort($users);
            return $users;
            
        } else {
            return false;
        }
    }
}

?>