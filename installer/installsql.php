<?php
define( 'GUWS_PREFIX', 'guws_' );
//define( '' );

require_once '../config.inc.php';

//contains the functions to create, edit, delete or backup the DB.
new GUWS_DB_Layer;

class GUWS_DB_Layer{
    private $defaultPOI;
    private $db;
    
    public function __construct(){
        try {
            $dbconn = 'mysql:host=' . DBHOST . ';dbname=' . DBDATA ; 
            $this->db = new PDO($dbconn , DBUSER , DBPASS , array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
            // set the error mode to exceptions
            $this->db->setAttribute(PDO::ATTR_ERRMODE , PDO::ERRMODE_EXCEPTION);
//            return $db; 
        }// try
            catch(PDOException $e) {
            error_log('message:' . $e->getMessage());
        }// catch
        
        $default_icon = array( 
            'url'          => '', 
            'type'         => '0' 
        );
        $default_object = array( 
            'contentType'  => 'image/vnd.layar.generic',
            'url'          => 'http://kelvinzevallos.freevar.com/images/poi_default.png',
            'reducedURL'   => '',
            'size'         => '23'
        );
        $default_transform = array(
            'rel'          => 'true',
            'angle'        => '0',
            'rotate_x'     => '0',
            'rotate_y'     => '0',
            'rotate_z'     => '1',
            'translate_x'  => '0',
            'translate_y'  => '0',
            'translate_z'  => '1',
            'scale'        => '1'
        );
        
        $default_POI_RealEstate = array( 
            'title'        => '', //to be inserted
            'imageURL'     => 'http://kelvinzevallos.freevar.com/images/poi_avatar.png',
            'description'  => '', //to be inserted
            'footnote'     => 'PUCP',
            'lat'          => '', //to be inserted
            'lon'          => '', //to be inserted
            'filter_value' => '', //to be inserted
            'poiType'      => 'geo',
            'transformID'  => '', //ID fron last inserted transform
            'objectID'     => '', //ID from last inserted object
            'iconID'       => '', //ID from last inserted icon
        );
        
        $default_POI_Action     = array(
            'label'            => '', //to be inserted
            'uri'              => '', //to be inserted
            'contentType'      => '',
            'activityType'     => '1',
            'autoTriggerRange' => '0',
            'autoTriggerOnly'  => 'false',
            'params'           => '',
            'poiID'            => '', //ID from last inserted POI
        );
        
        
        $this->defaultPOI = array();
    }
    
    public function create_database_tables(){
      $sql = $this->db->prepare('
          CREATE  TABLE IF NOT EXISTS `Icon` (
            `id` INT NOT NULL AUTO_INCREMENT ,
            `url` VARCHAR(250) NULL ,
            `type` VARCHAR(250) NULL ,
            PRIMARY KEY (`id`) );

          CREATE  TABLE IF NOT EXISTS `Object` (
            `id` INT NOT NULL AUTO_INCREMENT ,
            `contentType` VARCHAR(100) NULL ,
            `url` VARCHAR(250) NULL ,
            `reducedURL` VARCHAR(100) NULL ,
            `size` INT NULL ,
            PRIMARY KEY (`id`) );

         CREATE  TABLE IF NOT EXISTS `Transform` (
            `id` INT NOT NULL AUTO_INCREMENT ,
            `rel` TINYINT(1) NULL ,
            `angle` FLOAT NULL ,
            `rotate_x` FLOAT NULL ,
            `rotate_y` FLOAT NULL ,
            `rotate_z` FLOAT NULL ,
            `translate_x` FLOAT NULL ,
            `translate_y` FLOAT NULL ,
            `translate_z` FLOAT NULL ,
            `scale` FLOAT NULL ,
            PRIMARY KEY (`id`) );

         CREATE  TABLE IF NOT EXISTS `POI_RealEstate` (
            `id` INT NOT NULL AUTO_INCREMENT ,
            `title` VARCHAR(250) NOT NULL ,
            `imageURL` VARCHAR(250) NULL ,
            `description` LONGTEXT NULL ,
            `footnote` LONGTEXT NULL ,
            `lat` DECIMAL(30,20) NULL ,
            `lon` DECIMAL(30,20) NULL ,
            `filter_value` INT NOT NULL ,
            `poiType` VARCHAR(100) NULL ,
            `transformID` INT NOT NULL ,
            `objectID` INT NOT NULL ,
            `iconID` INT NOT NULL ,
            PRIMARY KEY (`id`) ,
            INDEX `fk_POI_RealEstate_Transform` (`transformID` ASC) ,
            INDEX `fk_POI_RealEstate_Object1` (`objectID` ASC) ,
            INDEX `fk_POI_RealEstate_Icon1` (`iconID` ASC) ,
            CONSTRAINT `fk_POI_RealEstate_Transform`
                FOREIGN KEY (`transformID` )
                REFERENCES `Transform` (`id` )
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT `fk_POI_RealEstate_Object1`
                FOREIGN KEY (`objectID` )
                REFERENCES `Object` (`id` )
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            CONSTRAINT `fk_POI_RealEstate_Icon1`
                FOREIGN KEY (`iconID` )
                REFERENCES `Icon` (`id` )
                ON DELETE CASCADE
                ON UPDATE CASCADE);
  
            CREATE  TABLE IF NOT EXISTS `POIAction` (
                `id` INT NOT NULL AUTO_INCREMENT ,
                `label` VARCHAR(250) NULL ,
                `uri` VARCHAR(250) NULL ,
                `contentType` VARCHAR(250) NULL ,
                `activityType` INT NULL ,
                `autoTriggerRange` INT NULL ,
                `autoTriggerOnly` TINYINT(1) NULL ,
                `params` TEXT NULL ,
                `poiID` INT NOT NULL ,
                PRIMARY KEY (`id`) ,
                INDEX `fk_POIAction_POI_RealEstate1` (`poiID` ASC) ,
                CONSTRAINT `fk_POIAction_POI_RealEstate1`
                    FOREIGN KEY (`poiID` )
                    REFERENCES `POI_RealEstate` (`id` )
                    ON DELETE CASCADE
                    ON UPDATE CASCADE); '); 
      
      $sql->execute();
    }
    
    public function insert_POI($POI_Data){
        $database = array();

        
        
        
        $sql = $this->db->prepare('
            


        ');
    }
}
?>