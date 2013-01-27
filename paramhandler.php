<?php
if (defined("GUWS_INTERNAL") || die("Must run from webservice"));

class guws_params{
    private $params;
    
    // Put needed getPOI request parameters and their values in an associative array
    //
    // Arguments:
    //  array ; An array of needed parameters passed in getPOI request
    //
    // Returns:
    //  array ; An associative array which contains the request parameters and
    //  their values.
    public function __construct($keys = ''){
        $this->params = array();
        try {
            // Retrieve parameter values using $_GET and put them in $value array with
            // parameter name as key. 
            foreach( $keys as $key ) {
            if ( isset($_GET[$key]) )
                $this->params[$key] = $_GET[$key]; 
            else 
                throw new Exception($key .' parameter is not passed in GetPOI request.');
            }
            return $this->params;
        }
        catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
        } 
    }//getRequestParams
    
    /*** Specific Custom Functions ***/

    // Prepare checkbox value which will be used in SQL statement. 
    // In this function, we add all the numbers in $checkboxlist parameter. If
    // $checkboxlist is empty, then we return 0.
    //
    // Arguments:
    // checkboxlist ; the value of CHECKBOXLIST parameter in the GetPOI request.
    //
    // Returns:
    // checkbox_value ; the value that can be used to construct the right SQL
    // statement. 
    function getCheckboxValue ($checkboxlist) {
        // if $checkboxlist exists, prepare checkbox_value. 	
        if(isset($checkboxlist)) {
        // Initialize returned value to be 0 if $checkboxlist is empty. 
            $checkbox_value = 0;
        // If $checkboxlist is not empty, return the added value of all the numbers splited by ','.
            if (!empty($checkboxlist)) {
                    if (strstr($checkboxlist , ',')) {
                            $checkbox_array = explode(',' , $checkboxlist);
                            for( $i=0; $i<count($checkbox_array); $i++ )
                                    $checkbox_value+=$checkbox_array[$i]; 
                    }//if
                    else $checkbox_value = $checkboxlist;
            }//if
            return $checkbox_value;
        } //if
        else {
            throw new Exception("checkboxlist parameter is not passed in GetPOI request.");
        }//else
    }//getCheckboxValue
    
    // Prepare custom_slider value which will be used in SQL statement. 
    // In this function, we simply return the value of $customslider defined in the
    // GetPOI request. 
    //
    // Arguments:
    // customslider ; the value of CUSTOM_SLIDER parameter in the GetPOI request.
    //
    // Returns:
    // customslider ; the value that can be used to construct the right SQL
    // statement. 
    //
    function getSliderValue ($customslider) { 
        if(isset($customslider)) return $customslider;
        else 
        throw new Exception("custom slider parameter is not passed in GetPOI request.");
    }//getSliderValue
    
    // Prepare radiolist value which will be used in SQL statement. In this
    // function, we convert the returned value into the ones that are stored in the
    // database. 
    //
    // Arguments:
    // radiolist ; the integer value of RADIOLIST parameter in the GetPOI request.
    //
    // Returns:
    // radio_value ; the value that can be used to construct the right SQL
    // statement. 
    function getRadioValue ($radiolist) {	
        if(isset($radiolist)) {
            $radio_value = '';
            switch ($radiolist) {
                case '1': $radio_value = "sale" ; break;
                case '2': $radio_value = "rent" ; break;		
                default: throw new Exception("invalid radiolist value:" . $radiolist);
            }
            return $radio_value;
        }
        else {
            throw new Exception("radiolist parameter is not passed in GetPOI request.");
        }
    }// getRadioValue
    
    // Prepare the search value which will be used in SQL statement. 
    // Arguments: 
    //   searchbox ; the value of SEARCHBOX parameter in the GetPOI request.
    //
    // Returns:
    //   searchbox_value ; If searchbox parameter has an empty string, return a
    //   string which is  a combination of numbers, letters and white spaces.
    //   Otherwise, return the value of searchbox parameter. 
    function getSearchValue ($searchbox) {
        // if $searchbox exists, prepare search value. 
        if (isset($searchbox)) {
            // initiate searchbox value to be any string that consists of numbers, letters and spaces. 
            $searchbox_value = '[0-9a-zA-Z\s]*';
            // if $searchbox is not an empty string, return the $searchbox value. 
            if ( !empty( $searchbox ) ) {
                $searchbox_value = remove_werid_characters($searchbox);
            }
            return $searchbox_value;
        }
        else { // If $searchbox does not exist, throw an exception. 
            throw new Exception("searchbox parameter is not passed in GetPOI request.");
        }
    }// getSearchValue
}
?>
