<?php
/*
MIT License 

Copyright (c) 2023 Ramesh Narayan Jangid. 

Permission is hereby granted, free of charge, to any person obtaining a copy 
of this software and associated documentation files (the "Software"), to deal 
in the Software without restriction, including without limitation the rights 
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
copies of the Software, and to permit persons to whom the Software is 
furnished to do so, subject to the following conditions: 

The above copyright notice and this permission notice shall be included in all 
copies or substantial portions of the Software. 

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR 
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE 
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE 
SOFTWARE. 
*/ 
/**
 * Example
 * 
 * $jsonEncode = new JsonEncode();
 * $sth = $db->select($sql);
 * $sth->execute($params);
 * 
 * // For single row
 * $jsonEncode->startAssoc();
 * foreach($sth->fetch(PDO::FETCH_ASSOC) as $key => $value) {
 *    $jsonEncode->addKeyValue($key, $value);
 * }
 * $jsonEncode->endAssoc();
 * $sth->closeCursor();
 * $jsonEncode = null;
 * 
 * // For multiple row
 * $jsonEncode->startArray();
 * for(;$row=$sth->fetch(PDO::FETCH_ASSOC);) {
 *    $jsonEncode->encode($row);
 * }
 * $jsonEncode->endArray();
 * $sth->closeCursor();
 * $jsonEncode = null;
 * 
 * // For mixture type
 * // Array inside Associative array.
 * $jsonEncode->startAssoc();
 * foreach($sth->fetch(PDO::FETCH_ASSOC) as $key => $value) {
 *    $jsonEncode->addKeyValue($key, $value);
 * }
 * $sth->closeCursor();
 *
 * $sth_2 = $db->select($sql_2);
 * $sth_2->execute($params_2);
 * $jsonEncode->startArray($assocKey);
 * for(;$row=$sth_2->fetch(PDO::FETCH_ASSOC);) {
 *    $jsonEncode->encode($row);
 * }
 * $sth_2->closeCursor();
 * $jsonEncode->endArray();
 * $jsonEncode->endAssoc();
 * $jsonEncode = null;
 * 
 */
/**
 * Creates JSON
 *
 * This class is built to avoid creation of large array objects
 * (which leads to memory limit issues for larger data set)
 * which are then converted to JSON. This class gives access to
 * create JSON in parts for what ever smallest part of data
 * we have of the large data set which are yet to be fetched.
 *
 * @category   JSON
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @2.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonEncode
{
    /**
     * Temporary Stream
     *
     * @var string
     */
    private $tempStream = '';

    /**
     * Array of JsonEncodeObject objects
     *
     * @var array
     */
    private $objects = [];

    /**
     * Current JsonEncodeObject object
     *
     * @var object
     */
    private $currentObject = null;

    /**
     * JsonEncode constructor
     */
    public function __construct()
    {
        ob_start();
        $this->tempStream = fopen("php://temp", "w+b");
    }

    /**
     * Write to temporary stream
     * 
     * @return void
     */
    public function write($str)
    {
        fwrite($this->tempStream, $str);
    }

    /**
     * Escape the json string key or value
     *
     * @param string $str json key or value string.
     * @return string
     */
    private function escape($str)
    {
        if (is_null($str)) return 'null';
        $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
        $str = str_replace($escapers, $replacements, $str);
        $this->write('"' . $str . '"');
    }

    /**
     * Encodes both simple and associative array to json
     *
     * @param $arr string value escaped and array value json_encode function is applied.  
     * @return void
     */
    public function encode($arr)
    {
        if ($this->currentObject) {
            $this->write($this->currentObject->comma);
        }
        if (is_array($arr)) {
            $this->write(json_encode($arr));
        } else {
            $this->write($this->escape($arr));
        }
        if ($this->currentObject) {
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Add simple array/value as in the json format.
     *
     * @param $value data type is string/array. This is used to add value/array in the current Array.
     * @return void
     */
    public function addValue($value)
    {
        if ($this->currentObject->mode !== 'Array') {
            throw new Exception('Mode should be Array');
        }
        $this->encode($value);
    }

    /**
     * Add simple array/value as in the json format.
     *
     * @param string $key   key of associative array
     * @param        $value data type is string/array. This is used to add value/array in the current Array.
     * @return void
     */
    public function addKeyValue($key, $value)
    {
        if ($this->currentObject->mode !== 'Assoc') {
            throw new Exception('Mode should be Assoc');
        }
        $this->write($this->currentObject->comma);
        $this->write($this->escape($key) . ':');
        $this->currentObject->comma = '';
        $this->encode($value);
    }

    /**
     * Start simple array
     *
     * @param string $key Used while creating simple array inside an associative array and $key is the key.
     * @return void
     */
    public function startArray($key = null)
    {
        if ($this->currentObject) {
            $this->write($this->currentObject->comma);
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new JsonEncodeObject('Array');
        if (!is_null($key)) {
            $this->write($this->escape($key) . ':');
        }
        $this->write('[');
    }

    /**
     * End simple array
     *
     * @return void
     */
    public function endArray()
    {
        $this->write(']');
        $this->currentObject = null;
        if (count($this->objects)>0) {
            $this->currentObject = array_pop($this->objects);
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Start simple array
     *
     * @param string $key Used while creating associative array inside an associative array and $key is the key.
     * @return void
     */
    public function startAssoc($key = null)
    {
        if ($this->currentObject) {
            $this->write($this->currentObject->comma);
            array_push($this->objects, $this->currentObject);
        }
        $this->currentObject = new JsonEncodeObject('Assoc');
        if (!is_null($key)) {
            $this->write($this->escape($key) . ':');
        }
        $this->write('{');
    }

    /**
     * End associative array
     *
     * @return void
     */
    public function endAssoc()
    {
        $this->write('}');
        $this->currentObject = null;
        if (count($this->objects)>0) {
            $this->currentObject = array_pop($this->objects);
            $this->currentObject->comma = ',';
        }
    }

    /**
     * Stream Json String.
     *
     * @return void
     */
    private function streamJson()
    {
        if (empty(ob_get_contents())) {
            // end the json
            // rewind the temp stream.
            rewind($this->tempStream);
            // stream the temp to output
            $outputStream = fopen("php://output", "w+b");
            stream_copy_to_stream($this->tempStream, $outputStream);
            fclose($outputStream);
        }
        fclose($this->tempStream);
    }

    /**
     * Checks json was properly closed.
     *
     * @return void
     */
    public function end()
    {
        while ($this->currentObject && $this->currentObject->mode) {
            switch ($this->currentObject->mode) {
                case 'Array':
                    $this->endArray();
                    break;
                case 'Assoc':
                    $this->endAssoc();
                    break;
            }
        }
        $this->streamJson();
    }

    /** 
     * destruct functipn 
     */ 
    public function __destruct() 
    { 
        $this->end();
    }
}

/**
 * JSON Object
 *
 * This class is built to help maintain state of simple/associative array
 *
 * @category   JsonObject
 * @package    Microservices
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonEncodeObject
{
    public $mode = '';
    public $comma = '';

    /**
     * Constructor
     *
     * @param string $mode Values can be one among Array/Assoc
     */
    public function __construct($mode)
    {
        $this->mode = $mode;
    }
}
