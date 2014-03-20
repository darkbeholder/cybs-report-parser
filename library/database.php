<?php
/*
 * Database Singleton
 * 
 * Create a single database object for use in the application
 *
 * @author Nick Mather
 * @Copyright 2011-2014 Symantec Corporation
 */
namespace Reports;

class Database
{
    //Database Variables
    private static $db_host = 'hostname';
    private static $db_user = 'username';
    private static $db_pass = 'password';
    private static $db_name = 'database';
    
    //Keep 1 single instance
    private static $_instance = NULL;
    
    //Prevent class being cloned
    private function __clone() 
    { 
        //Do nothing
    }
    
    //Prevent class being constructed
    private function __construct() 
    {
        
    }

    public static function getInstance()
    {
        if (is_null(self::$_instance))
        {
            //Create the connection
            self::$_instance = new PDO('mysql:host=' . self::$db_host . ';dbname=' . self::$db_name, self::$db_user, self::$db_pass);
            //Set error handling
            self::$_instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        }
        return self::$_instance;
    }
}
