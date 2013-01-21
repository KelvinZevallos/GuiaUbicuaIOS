<?php
if (defined("GUWS_INTERNAL") || die("must run from webservice"));


// Upon creation of the object, it connects to the database, configuration 
// information is stored in config.inc.php file
class guws_database{
    private $db;
    
    public function __construct(){
        try {
            $dbconn = 'mysql:host=' . DBHOST . ';dbname=' . DBDATA ; 
            $this->db = new PDO($dbconn , DBUSER , DBPASS , array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
            // set the error mode to exceptions
            $this->db->setAttribute(PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION);
        }// try
        catch(PDOException $e) {
            echo 'ERROR: ' . $e->getMessage();
        }// catch
    }
    
    public function getDB(){
        return $this->db;
    }
    
    // Put fetched actions for each POI into an associative array.
    //
    // Arguments:
    //   db ; The database connection handler. 
    //   poi ; The POI array.
    //
    // Returns:
    //   array ; An associative array of received actions for this POI.Otherwise,
    //   return an empty array. 
    // 
    private function getPoiActions($poi) {
        // Define an empty $actionArray array. 
        $actionArray = array();

        // A new table called 'POIAction' is created to store actions, each action
        // has a field called 'poiID' which shows the POI id that this action belongs
        // to. 
        // The SQL statement returns actions which have the same poiID as the id of
        // the POI($poiID).
        $sql_actions = $this->db->prepare(' 
            SELECT LABEL, 
                    URL,
            FROM ACCION_LAYAR
            WHERE poiID = :id ');
        // Binds the named parameter marker ':id' to the specified parameter value
        // '$poiID.                 
        $sql_actions->bindParam(':id', $poi['id'], PDO::PARAM_STR);
        // Use PDO::execute() to execute the prepared statement $sql_actions. 
        $sql_actions->execute();
        // Iterator for the $actionArray array.
        $count = 0; 
        // Fetch all the poi actions. 
        $actions = $sql_actions->fetchAll(PDO::FETCH_ASSOC);

        /* Process the $actions result */
        // if $actions array is not empty. 
        if ($actions) {
            // Put each action information into $actionArray array.
            // For now, actions are just links to webpages.
            foreach ($actions as $action) {
                $action['activityType'] = 1; //default
                $action['autoTriggerRange'] = 0; //default
                $action['autoTriggerOnly'] = false; //default
                $action['params'] = array(); //default
                // Assign each action to $actionArray array. 
                $actionArray[$count] = $action;
                $count++; 
            }// foreach
        }//if
        return $actionArray;
    }//getPoiActions
    
    // Put received POIs into an associative array. The returned values are
    // assigned to $reponse['hotspots'].
    //
    // Arguments:
    //   value , array ; An array which contains all the needed parameters
    //   retrieved from GetPOI request. 
    //
    // Returns:
    //   array ; An array of received POIs.
    //
    public function getHotspots($value) {
        // Define an empty $hotspots array.
        $hotspots = array();
        /* Create a SQL query to retrieve POIs which meet the criterion of filter settings returned from GetPOI request. 
        Returned POIs are sorted by distance and the first 50 POIs are selected. 
        - The distance is caculated based on the Haversine formula. 
            Note: this way of calculation is not scalable for querying large database.
        - searchbox filter, find POIs with title that contains the search term. 
            If the searchbox is empty, all POIs are returned. 
        - radiolist filter, find POIs with value from "Radiolist" column that equals to the prepared
            radiolist value from GetRadioValue function. 
        - checkbox filter, find POIs which don't return 0 after comparing the value from "Checkbox" column
            and prepared checkbox value (from GetCheckboxValue function) using Bitwise operations. 
            http://en.wikipedia.org/wiki/Bitwise_operation. if CHECKBOX parameter is empty, then no POIs are returned. 
        - custom_slider filter, find POIs with value from "Custom_Slider" column that is not bigger than
            the CUSTOM_SLIDER parameter value passed in the GetPOI request. 
        */

        // Use PDO::prepare() to prepare SQL statement. This statement is used due to
        // security reasons and will help prevent general SQL injection attacks.
        // ':lat1', ':lat2', ':long' and ':radius' are named parameter markers for
        // which real values will be substituted when the statement is executed.
        // $sql is returned as a PDO statement object. 

        $sql_string = 'SELECT ID_PUNTO, 
                                URL_IMGS, 
                                NOMBRE, 
                                DESCRIPCION,
                                WEB,
                                LAT, 
                                LONG,
                                (((acos(sin((:lat1 * pi() / 180)) * sin((LAT * pi() / 180)) +
                                cos((:lat2 * pi() / 180)) * cos((LAT * pi() / 180)) * 
                                cos((:long  - LONG) * pi() / 180))
                                ) * 180 / pi()
                                )* 60 * 1.1515 * 1.609344 * 1000
                                ) as distance,
                        FROM PUNTO
                        ';

        if ( isset( $value['SEARCHBOX'] ) ) { $sql_string .= ' AND NOMBRE REGEXP :search'; }

        if ( isset( $value['CHECKBOXLIST'] ) and ($value['CHECKBOXLIST'] != '') ) {
            $sql_string .= ' AND (';
            if ( strstr( $value['CHECKBOXLIST'] , ',') ) {
                $checkbox_array = explode( ',' , $value['CHECKBOXLIST'] );
                for( $i=0; $i<count($checkbox_array); $i++ ){
                    $sql_string .= '(filter_value = :checkbox'.$i.')';
                    if ( isset( $checkbox_array[$i+1] ) ){
                        $sql_string .= ' OR ';
                    }
                }
                $sql_string .= ')';
            } else {
                $sql_string .= 'filter_value = :checkbox)';
            }
        }
        //  if ( isset( $value['CUSTOM_SLIDER'] ) ) { $sql_string .= ' AND Custom_Slider <= :slider';  }
        //  if ( isset( $value['RADIOLIST'] ) )    { $sql_string .= ' AND radio_list = :radiolist'; }

        $sql_string .= ' HAVING distance < :radius 
                        ORDER BY distance ASC';

        $sql = $this->db->prepare( $sql_string );

        // PDOStatement::bindParam() binds the named parameter markers to the
        // specified parameter values. 
        $sql->bindParam(':lat1', $value['lat'], PDO::PARAM_STR);
        $sql->bindParam(':lat2', $value['lat'], PDO::PARAM_STR);
        $sql->bindParam(':long', $value['lon'], PDO::PARAM_STR);
        $sql->bindParam(':radius', $value['radius'], PDO::PARAM_INT);

        // Custom filter settings parameters. The four Get functions can be
        // customized.
//        if ( isset( $value['SEARCHBOX'] ) )     { $sql->bindParam(':search', getSearchValue($value['SEARCHBOX']), PDO::PARAM_STR); }
//        if ( isset( $value['CHECKBOX'] ) )      { $sql->bindParam(':checkbox', getCheckboxValue($value['CHECKBOXLIST']), PDO::PARAM_INT); }
        //  if ( isset( $value['CUSTOM_SLIDER'] ) ) { $sql->bindParam(':slider', getSliderValue($value['CUSTOM_SLIDER']), PDO::PARAM_INT); }
        //  if ( isset( $value['RADIOLIST'] ) )    { $sql->bindParam(':radiolist', getRadioValue($value['RADIOLIST']), PDO::PARAM_STR); }

        //  $sql->bindParam(':search', getSearchValue($value['SEARCHBOX']), PDO::PARAM_STR);

        // Use PDO::execute() to execute the prepared statement $sql. 
        $sql->execute();
        
        // Iterator for the response array.
        $i = 0; 
        // Use fetchAll to return an array containing all of the remaining rows in the result set.
        // Use PDO::FETCH_ASSOC to fetch $sql query results and return each row as an array indexed by column name.
        $rawPois = $sql->fetchAll(PDO::FETCH_ASSOC);

        /* Process the $pois result */
        // if $rawPois array is not  empty 
        if ($rawPois) {

            // Put each POI information into $hotspots array.
            foreach ( $rawPois as $rawPoi ) {
                $poi = array();
                $poi['id'] = $rawPoi['ID_PUNTO'];
                $poi['imageURL'] = $rawPoi['URL_IMGS'];
                // Get anchor object information
                $poi['anchor']['geolocation']['lat'] = changetoFloat($rawPoi['LAT']);
                $poi['anchor']['geolocation']['lon'] = changetoFloat($rawPoi['LONG']);
                // get text object information
                $poi['text']['title'] = $rawPoi['NOMBRE'];
                $poi['text']['description'] = $rawPoi['DESCRIPCION'];
                $poi['text']['footnote'] = "Grupo AVATAR-PUCP";
                //User function getPOiActions() to return an array of actions associated with the current POI
                $poi['actions'] = array();
                if ( $poi['WEB'] != "" ){
                    $poi['actions'][] = array(
                        'label' => "Sitio Web",
                        'uri' => $poi['WEB'],
                        'activityType' => 1,
                        'autoTriggerRange' => 0,
                        'autoTriggerOnly' => false,
                        'params' => array(),
                    );
                }
                // Get object information from the webservice
                $poi['object'] = array(
                    'contentType' => "image/vnd.layar.generic",
                    'url' => "",
                    'size' => 23,
                    'reducedURL' => "", 
                );
                
                // Give only the defaults as these parameters are not used in the webservice.
                $poi['icon'] = $guws_default_icon;
                $poi['transform'] = $guws_default_transform;     
                // Put the poi into the $hotspots array.
                $hotspots[$i] = $poi;
                $i++;
            }//foreach
        }//if
        return $hotspots;
    }//getHotspots
}
?>
