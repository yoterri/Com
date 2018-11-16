<?php
namespace Com\Util;

class MaxUploadFileSize
{

    protected static $_value = null;

    /**
     * Detects max size of file cab be uploaded to server
     *
     * Based on php.ini parameters “upload_max_filesize”, “post_max_size” &
     * “memory_limit”. Valid for single file upload form. May be used
     * as MAX_FILE_SIZE hidden input or to inform user about max allowed file size.
     * RULE memory_limit > post_max_size > upload_max_filesize
     * http://php.net/manual/en/ini.core.php : 128M > 8M > 2M
     * Sets max size of post data allowed. This setting also affects file upload.
     * To upload large files, this value must be larger than upload_max_filesize.
     * If memory limit is enabled by your configure script, memory_limit also
     * affects file uploading. Generally speaking, memory_limit should be larger
     * than post_max_size. When an integer is used, the value is measured in bytes.
     * Shorthand notation, as described in this FAQ, may also be used. If the size
     * of post data is greater than post_max_size, the $_POST and $_FILES
     * superglobals are empty. This can be tracked in various ways, e.g. by passing
     * the $_GET variable to the script processing the data, i.e.
     * , and then checking
     * if $_GET['processed'] is set.
     * memory_limit > post_max_size > upload_max_filesize
     *
     * @author Paul Melekhov edited by lostinscope
     * @return int - Max file size in bytes
     */
    static function maxUploadFileSize()
    {
        if(is_null(self::$_value))
        {
            $max_upload = self::normalice(ini_get('upload_max_filesize'));
            $max_post = ini_get('post_max_size');
            if(0 == $max_post)
                throw new Exception('Check Your php.ini settings');
            
            $memory_limit = (ini_get('memory_limit') == - 1) ? $max_post : self::normalice(ini_get('memory_limit'));
            
            if($memory_limit < $max_post || $memory_limit < $max_upload)
                return $memory_limit;
            
            if($max_post < $max_upload)
                return $max_post;
            
            self::$_value = min($max_upload, $max_post, $memory_limit);
        }
        
        return self::$_value;
    }

    /**
     * Converts shorthands like “2M” or “512K” to bytes
     *
     * @param mixed $size            
     * @return number
     */
    static function normalice($size)
    {
        $match = null;
        
        if(preg_match('/^([\d\.]+)([KMG])$/i', $size, $match))
        {
            $pos = array_search($match[2], array(
                "K",
                "M",
                "G"
            ));
            
            if($pos !== false)
            {
                $size = $match[1] * pow(1024, $pos + 1);
            }
        }
        
        return $size;
    }
    
    // static function maxUploadFileSize()
    // {
    // if(is_null(self::$_value))
    // {
    // $max_upload = (int)(ini_get('upload_max_filesize'));
    // $max_post = (int)(ini_get('post_max_size'));
    // $memory_limit = (int)(ini_get('memory_limit'));
    // self::$_value = min($max_upload, $max_post, $memory_limit);
    // }
    
    // return self::$_value;
    // }
}