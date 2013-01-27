<?php

class GUWS_Database_deprecated{
    

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
    
}
?>