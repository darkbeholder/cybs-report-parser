<?php
/*
 * Report Parser Config
 *
 * Set the constants required for all classes within the application and other general settings
 *
 * @Author Nick Mather
 * @Copyright 2011-2014 Symantec Corporation
 */
namespace Reports;

//Set the library path
define('LIB_PATH', 'library'); 
 
//Set Provider fee % for sales and refund transactions 
define('PROVFEE_SALE', 3);
define('PROVFEE_REFUND', 3);

//Set the default timezone to UTC/GMT for reporting as database is in UTC regarless of server time
date_default_timezone_set('UTC');
