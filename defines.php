<?php
define( 'GUWS_PREFIX', 'guws_' );

define( 'GUWS_DIRPATH', dirname(__FILE__) );
define( 'GUWS_URLPATH', $_SERVER['SERVER_NAME'] );

define( 'GUWS_IMG_DIRPATH' , GUWS_DIRPATH . '/img' );
define( 'GUWS_IMG_URLPARTH', GUWS_URLPATH . '/img' );

$guws_default_icon = array(
    'type' => 0,
);

$guws_default_transform = array(
    'scale' => 1,
    'translate' => array(
        'x' => 0,
        'y' => 0,
        'z' => 0,
    ),
    'rotate' => array(
        'angle' => 0,
        'rel' => true,
        'axis' => array( 
            'x' => 0.0,
            'y' => 0.0,
            'z' => 1
        ),
    )  
);
?>