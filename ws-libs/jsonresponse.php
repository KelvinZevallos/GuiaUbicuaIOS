<?php
class GUWS_JSON {
    private $errorCode;
    private $errorString;
    private $hotspots;
    
    public function __construct($errorCode, $errorString){
        $this->errorCode   = $errorCode;
        $this->errorString = $errorString;
    }
    
    public function generate_json_response(){
        $response = array(
            'errorCode'   => $this->errorCode,
            'errorString' => $this->errorString,
        );
        
        $jsonresponse = json_encode( $response );
	
	// Declare the correct content type in HTTP response header.
	header( 'Content-type: application/json; charset=utf-8' );
	
	// Print out Json response.
	echo $jsonresponse;
    }
}
?>
