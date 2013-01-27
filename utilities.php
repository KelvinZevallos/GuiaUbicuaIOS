<?php
if (defined("GUWS_INTERNAL") || die("Must run from webservice"));

// Change a string value to float
//
// Arguments:
//   string ; A string value.
// 
// Returns:
//   float ; If the string is empty, return NULL.
//
function changetoFloat($string) {
  if (strlen(trim($string)) != 0) 
    return (float)$string;
  return NULL;
}//changetoFloat

// Change a string value to integer. 
//
// Arguments:
//   string ; A string value.
// 
// Returns:
//   Int ; If the string is empty, return NULL.
//
function changetoInt($string) {
  if (strlen(trim($string)) != 0) 
    return (int)$string;
  return NULL;
}//changetoInt

// Convert a string into an array.
//
// Arguments:
//  string ; The input string
//  separater, string ; The boundary string used to separate the input string
//
// Returns:
//  array ; An array of strings. Otherwise, return an empty array. 
function changetoArray($string, $separator){
  $newArray = array();
  if($string) {
    if (substr_count($string,$separator)) {
      $newArray= array_map('trim' , explode($separator, $string));
        }//if
    else 
      $newArray[0] = trim($string);
  }
  return $newArray;
}//changetoArray

// Convert a TinyInt value to a boolean value TRUE or FALSE
//
// Arguments: 
//  int  value_Tinyint ; The Tinyint value (0 or 1) of a key in the database. 
//
// Returns:
//   boolean ; The boolean value, return 'TRUE' when Tinyint is 1. Return
//     'FALSE' when Tinyint is 0.
//
function changetoBool($value_Tinyint) {
  if (strlen(trim($value_Tinyint)) != 0) {
    if ($value_Tinyint == 0)
      return FALSE;
    else 
      return TRUE;
   }
  return NULL;
}//changetoBool

//Prepare the string so it only uses common ascii characters
function remove_werid_characters ($string)
{
//    $string=ereg_replace(" ","-",$div);
    $string=ereg_replace("á","a",$n_div);
    $string=ereg_replace("é","e",$n_div);
    $string=ereg_replace("í","i",$n_div);
    $string=ereg_replace("ó","o",$n_div);
    $string=ereg_replace("ú","u",$n_div);

    $string=ereg_replace("ä","a",$n_div);
    $string=ereg_replace("ë","e",$n_div);
    $string=ereg_replace("ï","i",$n_div);
    $string=ereg_replace("ö","o",$n_div);
    $string=ereg_replace("ü","u",$n_div);

    $string=ereg_replace("ñ", "n", $n_div);

    $string=ereg_replace("Ñ", "N", $n_div);

    return $string;
}
?>
