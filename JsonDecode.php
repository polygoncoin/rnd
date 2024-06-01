<?php
/*
MIT License 

Copyright (c) 2024 Ramesh Narayan Jangid. 

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
 * JSON file: '/usr/local/var/www/rnd/test.json'
 * 
 * {
 *     "page": "1",
 *     "perpage": "10",
 *     "totalPages": "1",
 *     "totalRecords": "1",
 *     "data": [
 *         {
 *             "id": 1,
 *             "firstname": "wewe",
 *             "lastname": "cxvasdv",
 *             "email": "email",
 *             "created_by": null,
 *             "created_on": "2024-05-05 18:11:33",
 *             "approved_by": null,
 *             "approved_on": null,
 *             "updated_by": null,
 *             "updated_on": "2024-05-05 18:11:33",
 *             "is_approved": "No",
 *             "is_disabled": "No",
 *             "is_deleted": "No",
 *             "data1": [
 *                 1,2,3,4,5,6,"7"
 *             ]
 *         }
 *     ]
 * }
 * 
 * PHP Code:
 * 
 * require_once "JsonDecode.php";
 * 
 * $JsonDecode = new JsonDecode('/usr/local/var/www/rnd/test.json');
 * 
 * // Validate JSON
 * $JsonDecode->validate();
 * 
 * echo '<pre>';
 * foreach($JsonDecode->process() as $keys => $arr) {
 *     var_dump($keys);
 *     var_dump($arr);
 * }
 * 
 * // To search in specific Keys pattern
 * // Index the JSON file as below.
 * $JsonDecode->indexJSON();
 * 
 * // Perform search inside values of $json['data'][0]['data1']
 * // the syntax is as below to set the keys.
 * $JsonDecode->load('data:0:data1');
 * 
 * // Get your searched data.
 * foreach($JsonDecode->process() as $keys => $arr) {
 *     print_r($keys);
 *     print_r($arr);
 * }
 * $JsonDecode = null;
 */
/**
 * Creates Objects from JSON String.
 *
 * This class is built to decode large json string or file
 * (which leads to memory limit issues for larger data set)
 * This class gives access to create obects from JSON string
 * in parts for what ever smallest part of data.
 * 
 *
 * @category   JSON 
 * @package    JSON Decode
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @2.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonDecode
{
    /**
     * Temporary Stream
     *
     * @var object
     */
    private $tempStream = null;

    /**
     * Array of JsonEncodeObject objects
     *
     * @var array
     */
    private $objects = [];

    /**
     * Parent JsonEncodeObject object
     *
     * @var object
     */
    private $previousObjectIndex = null;

    /**
     * Current JsonEncodeObject object
     *
     * @var object
     */
    private $currentObject = null;

    /**
     * Characters that are escaped while creating JSON.
     *
     * @var array
     */
    private $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c", ' ');

    /**
     * Characters that are escaped with for $escapers while creating JSON.
     *
     * @var array
     */
    private $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b", ' ');

    /**
     * JSON stream indexes.
     * Contains start and end positions for requested indexes.
     *
     * @var array
     */
    public $streamIndex = null;

    /**
     * JSON stream indexes seperated by colon.
     *
     * @var string
     */
    private $indexes = null;

    /**
     * JSON stream start position.
     *
     * @var int
     */
    private $_s_ = null;

    /**
     * JSON stream end position.
     *
     * @var int
     */
    private $_e_ = null;

    /**
     * JSON char counter.
     * Starts from $_s_ till $_e_
     *
     * @var int
     */
    private $charCounter = null;

    /**
     * JsonEncode constructor
     * 
     * @return void
     */
    public function __construct($filepath = "php://input")
    {
        $inputStream = fopen($filepath, "rb");
        $this->tempStream = fopen("php://temp", "rw+b");
        stream_copy_to_stream($inputStream, $this->tempStream);
        fclose($inputStream);
    }

    /**
     * Validates JSON
     * 
     * @return void
     */
    public function validate()
    {
        foreach($this->process as $keyArr => $valueArr) {
            ;
        }
    }

    /**
     * Validate JSON in stream
     *
     * @return bool
     */
    public function indexJSON()
    {
        $this->streamIndex = null;
        foreach ($this->process(true) as $keys => $val) {
            if (
                isset($val['_s_']) &&
                isset($val['_e_'])
            ) {
                $streamIndex = &$this->streamIndex;
                for ($i=0, $iCount = count($keys); $i < $iCount; $i++) {
                    if (!isset($streamIndex[$keys[$i]])) {
                        $streamIndex[$keys[$i]] = [];
                    }
                    $streamIndex = &$streamIndex[$keys[$i]];
                }
                $streamIndex['_s_'] = $val['_s_'];
                $streamIndex['_e_'] = $val['_e_'];
            }
        }
        return true;
    }

    /**
     * Start processing the JSON string for a keys
     * Perform search inside keys of JSON like $json['data'][0]['data1']
     *
     * @param string $indexes Key values seperated by colon.
     * @return void
     */
    public function load($indexes)
    {
        if (empty($indexes)) {
            $this->_s_ = null;
            $this->_e_ = null;
            $this->indexes = null;
            return;
        }
        $streamIndex = &$this->streamIndex;
        foreach (explode(':', $indexes) as $index) {
            if (isset($streamIndex[$index])) {
                $streamIndex = &$streamIndex[$index];
            } else {
                die("Invalid index {$index}");
            }
        }
        if (
            isset($streamIndex['_s_']) &&
            isset($streamIndex['_e_'])
        ) {
            $this->_s_ = $streamIndex['_s_'];
            $this->_e_ = $streamIndex['_e_'];
            $this->indexes = $indexes;
        } else {
            echo "Invalid indexes '{$indexes}'";
            die;
        }
    }

    /**
     * Start processing the JSON string
     *
     * @param bool $index Index output.
     * @return void
     */
    public function process($index = false)
    {
        // Flags Variable
        $quote = false;
        $comma = false;
        $colon = false;
        $keyFlag = false;
        $valueFlag = false;

        // Values Variables
        $keyValue = '';
        $valueValue = '';
        $replacements = '';
        $nullStr = null;

        // Variable mode - key/value;
        $varMode = 'keyValue';

        $this->charCounter = $this->_s_ !== null ? $this->_s_ : 0;
        fseek($this->tempStream, $this->charCounter, SEEK_SET);
        
        for(;
            (
                ($char = fgetc($this->tempStream)) !== false && 
                (
                    ($this->_e_ === null) ||
                    ($this->_e_ !== null && $this->charCounter <= $this->_e_)
                )
            )
            ;$this->charCounter++
        ) {
            //Switch mode to value collection after colon.
            if ($quote === false && $colon === false && $char === ':') {
                $colon = true;
                $keyFlag = false;
                $valueFlag = true;
                continue;
            }
            // Start or End of Array or Object.
            if ($quote === false && in_array($char, ['[',']','{','}'])) {
                $arr = $this->handleOpenClose($char, $keyValue, $nullStr, $index);
                if ($arr !== false) {
                    yield $arr['key'] => $arr['value'];
                }
                $keyValue = $valueValue = '';
                $keyFlag = true;
                $valueFlag = false;
                $colon = false;
                continue;
            }
            // Start of Key or value inside quote.
            if ($quote === false && $char === '"') {
                $quote = true;
                if ($colon === true) {
                    $varMode = 'valueValue';
                    $colon = false;
                    $comma = false;
                    $keyFlag = false;
                    $valueFlag = true;
                    continue;
                }
                if ($colon === false) {
                    $varMode = 'keyValue';
                    $keyFlag = true;
                    $valueFlag = false;
                    continue;
                }
            }
            // Escaped values.
            if ($quote === true && $replacements === "\\" && in_array($char, $this->escapers)) {
                $replacement .= $char;
                $$varMode .= str_replace($this->replacements, $this->escapers, $replacement);
                $replacement = '';
                continue;
            }
            // Closing double quotes.
            if ($quote === true && $char === '"') {
                $quote = false;
                $colon = false;
                if ($valueFlag === true) {
                    $this->currentObject->objectValues[$keyValue] = $valueValue;
                    $keyValue = $valueValue = '';
                    $keyFlag = true;
                    $valueFlag = false;
                    $colon = false;
                    $comma = true;
                }
                continue;
            }
            // Collect values for key or value.
            if ($quote === true) {
                $$varMode .= $char;
                continue;
            }
            // Check for null values.
            if ($quote === false) {
                if ($char === ',') {
                    if (!is_null($nullStr)) {
                        $nullStr = $this->checkNullStr($nullStr);
                        switch ($this->currentObject->mode) {
                            case 'Array':
                                $this->currentObject->arrayValues[] = $nullStr;
                                if (is_null($this->currentObject->arrayKey)) {
                                    $this->currentObject->arrayKey = 0;
                                } else {
                                    $this->currentObject->arrayKey++;
                                }
                                break;
                            case 'Object':
                                if (!empty($keyValue)) {
                                    $this->currentObject->objectValues[$keyValue] = $nullStr;
                                }
                                break;
                        }
                        $nullStr = null;
                        $keyValue = $valueValue = '';
                        $keyFlag = true;
                        $valueFlag = false;
                        $colon = false;
                        $comma = true;
                    }
                } else {
                    if (!in_array($char, $this->escapers)) {
                        $nullStr .= $char;
                    }
                }
                continue;
            }
        }

        $this->objects = [];
        $this->currentObject = null;
        $this->previousObjectIndex = null;
    }

    /**
     * Handles array / object open close char
     *
     * @param string $char     Character among any one "[" "]" "{" "}".
     * @param string $keyValue String value of key of an object.
     * @param string $nullStr  String present in JSON without double quotes.
     * @param bool   $index    Index output.
     * @return array
     */
    private function handleOpenClose(&$char, &$keyValue, &$nullStr, &$index)
    {
        $arr = false;
        switch ($char) {
            case '[':
                if (!$index) {
                    $arr = [
                        'key' => $this->keys($index),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->startArray($keyValue);
                $this->increment();
                break;
            case '{':
                if (!$index) {
                    $arr = [
                        'key' => $this->keys($index),
                        'value' => $this->getObjectValues()
                    ];
                }
                $this->startObject($keyValue);
                $this->increment();
                break;
            case ']':
                if (!empty($keyValue)) {
                    $this->currentObject->arrayValues[] = $keyValue;
                    if (is_null($this->currentObject->arrayKey)) {
                        $this->currentObject->arrayKey = 0;
                    } else {
                        $this->currentObject->arrayKey++;
                    }
                }
                if ($index) {
                    $arr = [
                        'key' => $this->keys($index),
                        'value' => [
                            '_s_' => $this->currentObject->_s_,
                            '_e_' => $this->charCounter
                        ]
                    ];
                } else {
                    if (!empty($this->currentObject->arrayValues)) {
                        $arr = [
                            'key' => $this->keys(),
                            'value' => $this->currentObject->arrayValues
                        ];    
                    }
                }
                $this->currentObject->arrayValues = [];
                $this->endArray();
                break;
            case '}':
                if (!empty($keyValue) && !empty($nullStr)) {
                    $nullStr = $this->checkNullStr($nullStr);
                    $this->currentObject->objectValues[$keyValue] = $nullStr;
                }
                if ($index) {
                    $arr = [
                        'key' => $this->keys($index),
                        'value' => [
                            '_s_' => $this->currentObject->_s_,
                            '_e_' => $this->charCounter
                        ]
                    ];
                } else {
                    if (!empty($this->currentObject->objectValues)) {
                        $arr = [
                            'key' => $this->keys(),
                            'value' => $this->currentObject->objectValues
                        ];    
                    }
                }
                $this->currentObject->objectValues = [];
                $this->endObject();
                break;
        }
        if (
            $arr !== false && 
            !empty($arr) &&
            isset($arr['value']) && 
            $arr['value'] !== false && 
            count($arr['value']) > 0
        ) {
            return $arr;
        }
        return false;
    }

    /**
     * Check String present in JSON without double quotes for null or integer
     *
     * @param string $nullStr String present in JSON without double quotes.
     * @return mixed
     */
    private function checkNullStr($nullStr)
    {
        $return = false;
        if ($nullStr === 'null') {
            $return = null;
        } elseif (is_numeric($nullStr)) {
            $return = (int)$nullStr;
        }
        if ($return === false) {
            $this->isBadJson($nullStr);
        }
        return $return;
    }

    /**
     * Start of array
     *
     * @param string $key Used while creating simple array inside an objectiative array and $key is the key.
     * @return void
     */
    private function startArray($key = null)
    {
        $this->pushCurrentObject($key);
        $this->setPreviousObjectIndex();
        $this->currentObject = new JsonDecodeObject('Array', $key);
        $this->currentObject->_s_ = $this->charCounter;
    }

    /**
     * End of array
     *
     * @return void
     */
    private function endArray()
    {
        $this->popCurrentObject();
        $this->setPreviousObjectIndex();
    }

    /**
     * Start of object
     *
     * @param string $key Used while creating objectiative array inside an objectiative array and $key is the key.
     * @return void
     */
    private function startObject($key = null)
    {
        $this->pushCurrentObject($key);
        $this->setPreviousObjectIndex();
        $this->currentObject = new JsonDecodeObject('Object', $key);
        $this->currentObject->_s_ = $this->charCounter;
    }

    /**
     * End of object
     *
     * @return void
     */
    private function endObject()
    {
        $this->popCurrentObject();
        $this->setPreviousObjectIndex();
    }

    /**
     * Push current object
     *
     * @return void
     */
    private function pushCurrentObject($key)
    {
        if ($this->currentObject) {
            if ($this->currentObject->mode === 'Object' && empty(trim($key))) {
                $this->isBadJson($key);
            }
            if ($this->currentObject->mode === 'Array' && !empty(trim($key))) {
                $this->isBadJson($key);
            }
            array_push($this->objects, $this->currentObject);
            $this->currentObject = null;
        }
    }

    /**
     * Pop current object
     *
     * @return void
     */
    private function popCurrentObject()
    {
        if (count($this->objects) > 0) {
            $this->currentObject = array_pop($this->objects);
        } else {
            $this->currentObject = null;
        }
    }

    /**
     * Sets previous object index
     *
     * @return void
     */
    private function setPreviousObjectIndex()
    {
        if (count($this->objects) > 0) {
            $this->previousObjectIndex = count($this->objects) - 1;
        } else {
            $this->previousObjectIndex = null;
        }
    }

    /**
     * Increment arrayKey counter for array of objects or arrays
     *
     * @return void
     */
    private function increment()
    {
        if (
            !is_null($this->previousObjectIndex) &&
            $this->objects[$this->previousObjectIndex]->mode === 'Array'
        ) {
            if (is_null($this->objects[$this->previousObjectIndex]->arrayKey)) {
                $this->objects[$this->previousObjectIndex]->arrayKey = 0;
            } else {
                $this->objects[$this->previousObjectIndex]->arrayKey++;
            }
        }
    }

    /**
     * Returns extracted object values.
     *
     * @return array
     */
    private function getObjectValues()
    {
        $arr = false;
        if (
            !is_null($this->currentObject) && 
            $this->currentObject->mode === 'Object' && 
            count($this->currentObject->objectValues) > 0
        ) {
            $arr = $this->currentObject->objectValues;
            $this->currentObject->objectValues = [];
        }
        return $arr;
    }

    /**
     * Check for a valid JSON.
     * 
     * @return void
     */
    private function isBadJson($str)
    {
        $str = trim($str);
        if (!empty($str)) {
            echo 'Invalid JSON: ' . $str;
            die;
        }
    }

    /**
     * Generated Array.
     * 
     * @param bool $index Index output.
     * @return array
     */
    private function keys($index = false)
    {
        $keys = [];
        $return = &$keys;
        if (!$index && !empty($this->indexes)) {
            foreach(explode(':', $this->indexes) as $ind) {
                $keys[$ind] = [];
                $keys = &$keys[$ind];
            }
        }
        $objCount = count($this->objects);
        if ($objCount > 0) {
            for ($i=1; $i<$objCount; $i++) {
                switch ($this->objects[$i]->mode) {
                    case 'Object':
                        if (!is_null($this->objects[$i]->objectKey)) {
                            if ($index) {
                                $keys[] = $this->objects[$i]->objectKey;
                            } else {
                                $keys[$this->objects[$i]->objectKey] = [];
                                $keys = &$keys[$this->objects[$i]->objectKey];
                            }
                        }
                        break;
                    case 'Array':
                        if (!is_null($this->objects[$i]->objectKey)) {
                            if ($index) {
                                $keys[] = $this->objects[$i]->objectKey;
                            } else {
                                $keys[$this->objects[$i]->objectKey] = [];
                                $keys = &$keys[$this->objects[$i]->objectKey];
                            }
                        }
                        if (!is_null($this->objects[$i]->arrayKey)) {
                            if ($index) {
                                $keys[] = $this->objects[$i]->arrayKey;
                            } else {
                                $keys[$this->objects[$i]->arrayKey] = [];
                                $keys = &$keys[$this->objects[$i]->arrayKey];
                            }
                        }
                        break;
                }
            }
        }
        if ($this->currentObject) {
            switch ($this->currentObject->mode) {
                case 'Object':
                    if (!is_null($this->currentObject->objectKey)) {
                        if ($index) {
                            $keys[] = $this->currentObject->objectKey;
                        } else {
                            $keys[$this->currentObject->objectKey] = [];
                            $keys = &$keys[$this->currentObject->objectKey];
                        }
                    }
                    break;
                case 'Array':
                    if (!is_null($this->currentObject->objectKey)) {
                        if ($index) {
                            $keys[] = $this->currentObject->objectKey;
                        } else {
                            $keys[$this->currentObject->objectKey] = [];
                            $keys = &$keys[$this->currentObject->objectKey];
                        }
                    }
                    break;
            }
        }
        return $return;
    }
}

/**
 * JSON Object
 *
 * This class is built to help maintain state of an Array or Object of JSON.
 *
 * @category   JSON
 * @package    Json Decode
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class JsonDecodeObject
{
    /**
     * JSON stream start position.
     *
     * @var int
     */
    public $_s_ = null;

    /**
     * JSON stream end position.
     *
     * @var int
     */
    public $_e_ = null;
    
    /**
     * Object / Array.
     *
     * @var string
     */
    public $mode = '';

    /**
     * Object key for parant object.
     *
     * @var string
     */
    public $objectKey = null;
    
    /**
     * Array key for parant object.
     *
     * @var string
     */
    public $arrayKey = null;

    /**
     * Object values.
     *
     * @var array
     */
    public $objectValues = [];

    /**
     * Array values.
     *
     * @var array
     */
    public $arrayValues = [];

    /**
     * Constructor
     *
     * @param string $mode Values can be one among Array/Object
     */
    public function __construct($mode, $objectKey = null)
    {
        $this->mode = $mode;
        $objectKey = trim($objectKey);
        $this->objectKey = !empty($objectKey) ? $objectKey : null;
    }
}

$JsonDecode = new JsonDecode('/usr/local/var/www/rnd/test.json');
foreach($JsonDecode->process() as $keys => $arr) {
    print_r($keys);
    print_r($arr);
}

// To search in specific Keys pattern
// Index the JSON file as below.
$JsonDecode->indexJSON();

// Perform search inside values of $json['data'][0]['data1']
// the syntax is as below to set the parameter.
$JsonDecode->load('data:0:data1');

// Get your searched data.
foreach($JsonDecode->process() as $keys => $arr) {
    print_r($keys);
    print_r($arr);
}

$JsonDecode = null;
