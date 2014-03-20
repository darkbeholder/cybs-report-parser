<?php
/**
 * Report Parser Factory
 *
 * Creates a parser instance based on the type requested if it exists in the parsers directory
 *
 * @Author Nick Mather
 * @Copyright 2011-2014 Symantec Corporation
 */
 
namespace Reports;

class Parser
{
    public static function create($type, $db, $data)
    {
        $parser = 'Reports\Parser\\' . $type . 'Parser';
        if (!class_exists($parser))
        {
            require_once(LIB_PATH . '/parser/' . $type . '.php');
        }
        return new $parser($db, $data);
    }
}
