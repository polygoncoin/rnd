<?php
/*
MIT License 

Copyright (c) 2023 Ramesh Jangid. 

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

/*
 * Usage instructions:
 * 
 * include_once ('CacheHandler.php');
 * $filePath = '/filename.ext';
 * CacheHandler::init($filePath);
 */

/**
 * Class to reduce cache hits and save on bandwidth using ETags and cache headers.
 *
 * HTTP etags header helps reduce the cache hits
 * Helps browser avoid unwanted hits to un-modified content on the server
 * which are cached on client browser.
 * The headers in class helps fetch only the modified content.
 * 
 * @category   PHP E-Tags
 * @package    Cache handler
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class CacheHandler
{
    /**
     * Cache Folder
     * 
     * The folder location outside docroot
     * without a slash at the end
     * 
     * @var string
     */
    private static $cacheLocation = '/var/www/cache';

    /**
     * Initalise check and serve file
     *
     * @param string $filePath File path in cache Folder with leading slash(/)
     * @return void
     */
    public static function init($filePath)
    {
        $filePath = '/' . trim(str_replace('../','',urldecode($filePath)), './');
        $fileLocation = self::$cacheLocation . $filePath;
        $modifiedTime = filemtime($fileLocation);

        // Let Etag be last modified timestamp of file.
        $eTag = "{$modifiedTime}";

        if (
            (
                isset($_SERVER['HTTP_IF_NONE_MATCH']) &&
                strpos($_SERVER['HTTP_IF_NONE_MATCH'], $eTag) !== false
            ) ||
            (
                isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
                @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $modifiedTime
            )
        ) { 
            header("HTTP/1.1 304 Not Modified"); 
            exit;
        } else {
            self::serveFile($fileLocation, $modifiedTime, $eTag);
        }
    }

    /**
     * Serve File content
     *
     * @param string  $fileLocation
     * @param integer $modifiedTime
     * @param string  $eTag
     * @return void
     */
    private static function serveFile($fileLocation, $modifiedTime, $eTag) {
        // File name requested for download
        $fileName = basename($fileLocation);

        // Get the $fileLocation file mime
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fileInfo, $fileLocation);
        finfo_close($fileInfo);

        // send the headers
        //header("Content-Disposition: attachment; filename='$fileName';");
        header('Cache-Control: max-age=0, must-revalidate');
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", $modifiedTime)." GMT"); 
        header("Etag:\"{$eTag}\"");
        header('Expires: -1');
        header("Content-Type: $mime");
        header('Content-Length: ' . filesize($fileLocation));

        // Send file content as stream
        $fp = fopen($fileLocation, 'rb');
        fpassthru($fp);
        exit;
    }
}
