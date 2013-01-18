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
        error_log('message:' . $e->getMessage());
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
    function getPoiActions($db , $poi) {
        // Define an empty $actionArray array. 
        $actionArray = array();

        // A new table called 'POIAction' is created to store actions, each action
        // has a field called 'poiID' which shows the POI id that this action belongs
        // to. 
        // The SQL statement returns actions which have the same poiID as the id of
        // the POI($poiID).
        $sql_actions = $db->prepare(' 
            SELECT label, 
                    uri, 
                    contentType,
                    activityType,
                    autoTriggerRange,
                    autoTriggerOnly,
                    params
            FROM POIAction
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
            foreach ($actions as $action) { 
            // Change 'activityType' to Integer.
            $action['activityType'] = changetoInt($action['activityType']);
            $action['autoTriggerRange'] = changetoInt($action['autoTriggerRange']);
            $action['autoTriggerOnly'] = changetoBool($action['autoTriggerOnly']);
            $action['params'] = changetoArray($action['params'] , ',');
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
    //   db ; The handler of the database.
    //   value , array ; An array which contains all the needed parameters
    //   retrieved from GetPOI request. 
    //
    // Returns:
    //   array ; An array of received POIs.
    //
    function getHotspots( $db, $value ) {
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

        $sql_string = 'SELECT id, 
                                imageURL, 
                                title, 
                                description, 
                                footnote, 
                                lat, 
                                lon,
                                (((acos(sin((:lat1 * pi() / 180)) * sin((lat * pi() / 180)) +
                                cos((:lat2 * pi() / 180)) * cos((lat * pi() / 180)) * 
                                cos((:long  - lon) * pi() / 180))
                                ) * 180 / pi()
                                )* 60 * 1.1515 * 1.609344 * 1000
                                ) as distance,
                                iconID,
                                objectID,
                                transformID
                        FROM POI_RealEstate
                        WHERE poiType = "geo"
                        ';

        if ( isset( $value['SEARCHBOX'] ) )     { $sql_string .= ' AND title REGEXP :search'; }

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
        //      $sql_string .= ' AND ((filter_value & :checkbox) != 0)'; 
        }
        //  if ( isset( $value['CUSTOM_SLIDER'] ) ) { $sql_string .= ' AND Custom_Slider <= :slider';  }
        //  if ( isset( $value['RADIOLIST'] ) )    { $sql_string .= ' AND radio_list = :radiolist'; }

        $sql_string .= ' HAVING distance < :radius 
                        ORDER BY distance ASC';

        $sql = $db->prepare( $sql_string );

        // PDOStatement::bindParam() binds the named parameter markers to the
        // specified parameter values. 
        $sql->bindParam(':lat1', $value['lat'], PDO::PARAM_STR);
        $sql->bindParam(':lat2', $value['lat'], PDO::PARAM_STR);
        $sql->bindParam(':long', $value['lon'], PDO::PARAM_STR);
        $sql->bindParam(':radius', $value['radius'], PDO::PARAM_INT);

        // Custom filter settings parameters. The four Get functions can be
        // customized.
        if ( isset( $value['SEARCHBOX'] ) )     { $sql->bindParam(':search', getSearchValue($value['SEARCHBOX']), PDO::PARAM_STR); }
        if ( isset( $value['CHECKBOX'] ) )      { $sql->bindParam(':checkbox', getCheckboxValue($value['CHECKBOXLIST']), PDO::PARAM_INT); }
        //  if ( isset( $value['CUSTOM_SLIDER'] ) ) { $sql->bindParam(':slider', getSliderValue($value['CUSTOM_SLIDER']), PDO::PARAM_INT); }
        //  if ( isset( $value['RADIOLIST'] ) )    { $sql->bindParam(':radiolist', getRadioValue($value['RADIOLIST']), PDO::PARAM_STR); }

        //  $sql->bindParam(':search', getSearchValue($value['SEARCHBOX']), PDO::PARAM_STR);

        // Use PDO::execute() to execute the prepared statement $sql. 
        $sql->execute();
        // Iterator for the response array.
        $i = 0; 
        // Use fetchAll to return an array containing all of the remaining rows in
        // the result set.
        // Use PDO::FETCH_ASSOC to fetch $sql query results and return each row as an
        // array indexed by column name.
        $rawPois = $sql->fetchAll(PDO::FETCH_ASSOC);

        /* Process the $pois result */
        // if $rawPois array is not  empty 
        if ($rawPois) {

            // Put each POI information into $hotspots array.
            foreach ( $rawPois as $rawPoi ) {
            $poi = array();
            $poi['id'] = $rawPoi['id'];
            $poi['imageURL'] = $rawPoi['imageURL'];
            // Get anchor object information
            $poi['anchor']['geolocation']['lat'] = changetoFloat($rawPoi['lat']);
            $poi['anchor']['geolocation']['lon'] = changetoFloat($rawPoi['lon']);
            // get text object information
            $poi['text']['title'] = $rawPoi['title'];
            $poi['text']['description'] = $rawPoi['description'];
            $poi['text']['footnote'] = $rawPoi['footnote'];
            //User function getPOiActions() to return an array of actions associated
            //with the current POI
            $poi['actions'] = getPoiActions($db, $rawPoi);
            // Get object object information if iconID is not null
            if(count($rawPoi['iconID']) != 0) 
                $poi['icon'] = getIcon($db , $rawPoi['iconID']);
            // Get object object information if objectID is not null
            if(count($rawPoi['objectID']) != 0) 
                $poi['object'] = getObject($db, $rawPoi['objectID']);
            // Get transform object information if transformID is not null
            if(count($rawPoi['transformID']) != 0)
                $poi['transform'] = getTransform($db, $rawPoi['transformID']);
            // Put the poi into the $hotspots array.
            $hotspots[$i] = $poi;
            $i++;
            }//foreach
        }//if
        return $hotspots;
    }//getHotspots
    
    // Put fetched transform related parameters for each POI into an associative
    // array. The returned values are assigned to $poi[transform].
    //
    // Arguments:
    //   db ; The database connection handler. 
    //   transformID , integer ; The transform id which is assigned to this POI.
    //
    // Returns: associative array or NULL; An array of received transform related
    // parameters for this POI. Otherwise, return NULL. 
    // 
    function getTransform($db , $transformID) {
    // If no transform object is found, return NULL. 
    $transform = NULL;
    // A new table called 'Transform' is created to store transform related
    // parameters, namely 'rotate','translate' and 'scale'. 
    // 'transformID' is the transform that is applied to this POI. 
    // The SQL statement returns transform which has the same id as the
    // $transformID of this POI. 
    $sql_transform = $db->prepare('
        SELECT rel, 
                angle, 
                rotate_x,
                rotate_y,
                rotate_z,
                translate_x,
                translate_y,
                translate_z,
                scale
        FROM Transform
        WHERE id = :transformID 
        LIMIT 0,1 '); 

    // Binds the named parameter marker ':transformID' to the specified parameter
    // value $transformID                
    $sql_transform->bindParam(':transformID', $transformID, PDO::PARAM_INT);
    // Use PDO::execute() to execute the prepared statement $sql_transform. 
    $sql_transform->execute();
    // Fetch the poi transform. 
    $rawTransform = $sql_transform->fetch(PDO::FETCH_ASSOC);

    /* Process the $rawTransform result */
    // if $rawTransform array is not  empty 
    if ($rawTransform) {
        // Change the value of 'scale' into decimal value.
        $transform['scale'] = changetoFloat($rawTransform['scale']);
        // organize translate field
        $transform['translate']['x'] =changetoFloat($rawTransform['translate_x']);
        $transform['translate']['y'] = changetoFloat($rawTransform['translate_y']);
        $transform['translate']['z'] = changetoFloat($rawTransform['translate_z']);
        // organize rotate field
        $transform['rotate']['axis']['x'] = changetoFloat($rawTransform['rotate_x']);
        $transform['rotate']['axis']['y'] = changetoFloat($rawTransform['rotate_y']);
        $transform['rotate']['axis']['z'] = changetoFloat($rawTransform['rotate_z']);
        $transform['rotate']['angle'] = changetoFloat($rawTransform['angle']);
        $transform['rotate']['rel'] = changetoBool($rawTransform['rel']);
    }//if 

    return $transform;
    }//getTransform
    
    // Put fetched object parameters for each POI into an associative array.
    //
    // Arguments:
    //   db ; The database connection handler. 
    //   objectID, integer ; The object id assigned to this POI.
    //
    // Returns:
    //   associative array or NULL ; An array of received object related parameters
    //   for this POI. otherwise, return NULL. 
    // 
    function getObject($db , $objectID) {
    // If no object object is found, return NULL. 
    $object = NULL;

    // A new table called 'Object' is created to store object related parameters,
    // namely 'url', 'contentType', 'reducedURL' and 'size'. The SQL statement
    // returns object which has the same id as $objectID stored in this POI. 
    $sql_object = $db->prepare(
        ' SELECT contentType,
                url, 
                reducedURL, 
                size 
        FROM Object
        WHERE id = :objectID 
        LIMIT 0,1 '); 

    // Binds the named parameter marker ':objectID' to the specified parameter
    // value $objectID.                 
    $sql_object->bindParam(':objectID', $objectID, PDO::PARAM_INT);
    // Use PDO::execute() to execute the prepared statement $sql_object. 
    $sql_object->execute();
    // Fetch the poi object. 
    $rawObject = $sql_object->fetch(PDO::FETCH_ASSOC);

    /* Process the $rawObject result */
    // if $rawObject array is not empty. 
    if ($rawObject) {
        // Change 'size' type to float. 
        $rawObject['size'] = changetoFloat($rawObject['size']);
        $object = $rawObject;
    }
    return $object;
    }//getObject
    
    // Put fetched icon dictionary for each POI into an associative array.
    // 
    // Arguments:
    //  db ; The database connection handler.
    //  iconID, integer ; The iconID value  which is stored in this POI.
    //
    // Return:
    //  array ; An associative array of retrieved icon dictionary for this POI.
    //  Otherwise, return NULL. 
    function getIcon($db, $iconID) {
    // If no icon object is found, return NULL.
    $icon = NULL;

    // Run the query to retrieve icon information for this POI.  
    $sql_icon = $db->prepare( '
                SELECT url, type
                FROM Icon
                WHERE id = :iconID  
                ' );
    $sql_icon->bindParam(':iconID', $iconID, PDO::PARAM_INT);
    $sql_icon->execute();
    $rawIcon = $sql_icon->fetch(PDO::FETCH_ASSOC);

    // Assign returned values to $icon array. 
    if($rawIcon){
        $rawIcon['type'] = changetoInt($rawIcon['type']);
        $icon = $rawIcon;
    }    
    return $icon;
    }//getIcon
}
?>
