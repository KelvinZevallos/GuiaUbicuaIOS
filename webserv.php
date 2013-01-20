<?php
// Created by Xuan Wang
// Layar Technical Support
// Email: xuan@layar.com
// Website: http://layar.com

// Modified by: Kelvin Zevallos
//              kelvin.zevallos.o@gmail.com 

/*** Include some external files ***/

// Include database credentials. Please customize these fields with your own
// database configuration.
define('GUWS_INTERNAL', true);

require_once('defines.php');
require_once('config.inc.php');
require_once('utilities.php');
require_once('paramhandler.php');
require_once('database.php');

/*** Main entry point ***/

/* Put parameters from GetPOI request into an associative array named $requestParams */
// Put needed parameter names from GetPOI request in an array called $keys. 
$keys = array('layerName', 'lat', 'lon', 'radius');
if ( isset( $_GET['SEARCHBOX'] ) )     { $keys[] = 'SEARCHBOX'; }
if ( isset( $_GET['CHECKBOX'] ) )      { $keys[] = 'CHECKBOX'; }
if ( isset( $_GET['CUSTOM_SLIDER'] ) ) { $keys[] = 'CUSTOM_SLIDER'; }
if ( isset( $_GET['RADIOLIST'] ) )     { $keys[] = 'RADIOLIST'; }

// Initialize an empty associative array.
$requestParams = array(); 
// Call funtion getRequestParams()  
$requestParams = getRequestParams($keys);

// Create Database connection object.  
$db = new guws_database(); 
	
/* Construct the response into an associative array.*/
	
// Create an empty array named response.
$response = array();
	
// Assign cooresponding values to mandatory JSON response keys.
$response['layer'] = $requestParams['layerName'];
	
// Use Gethotspots() function to retrieve POIs with in the search range.  
$response['hotspots'] = $db->getHotspots($requestParams);

// if there is no POI found, return a custom error message.
if (!$response['hotspots'] ) {
	$response['errorCode'] = 20;
 	$response['errorString'] = 'No POI found. Please adjust the range.';
}//if
else {
  $response['errorCode'] = 0;
  $response['errorString'] = 'ok';
}//else
	/* All data is in $response, print it into JSON format.*/
	// Put the JSON representation of $response into $jsonresponse.
	$jsonresponse = json_encode( $response );
	// Declare the correct content type in HTTP response header.
	header( 'Content-type: application/json; charset=utf-8' );
	// Print out Json response.
	echo $jsonresponse;
?>