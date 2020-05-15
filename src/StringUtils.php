<?php
namespace Com;
use Laminas;

class StringUtils extends Laminas\Stdlib\StringUtils
{


    /**
     * @param string $date
     * @param string $from_format
     * @param string $to_format
     * @param string $def
     * @return string
     */
    static function convert_date($date, $from_format, $to_format, $def = '-')
    {
        $myDateTime = \DateTime::createFromFormat($from_format, $date);

        if(!$myDateTime)
        {
            return $def;
        }
        
        $newDate = $myDateTime->format($to_format);

        return $newDate;
    }


    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    static function ends_with($haystack, $needles)
    {
        foreach((array) $needles as $needle)
        {
            if((string) $needle === substr($haystack, -strlen($needle))) return true;
        }

        return false;
    }


    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    static function starts_with($haystack, $needles)
    {
        foreach((array)$needles as $needle)
        {
            if($needle != '' && strpos($haystack, $needle) === 0) return true;
        }

        return false;
    }


    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    static function str_contains($haystack, $needles)
    {
        foreach((array) $needles as $needle)
        {
            if ($needle != '' && strpos($haystack, $needle) !== false) return true;
        }

        return false;
    }


    /**
     * Determine if a given string matches a given pattern.
     *
     * @param  string  $pattern
     * @param  string  $value
     * @return bool
     */
    static function str_is($pattern, $value)
    {
        if($pattern == $value) return true;
        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern).'\z';
        return (bool) preg_match('#^'.$pattern.'#', $value);
    }



    /**
     * Limit the number of characters in a string.
     *
     * @param  string  $value
     * @param  int     $limit
     * @param  string  $end
     * @return string
     */
    static function str_limit($value, $limit = 100, $end = '...')
    {
        if(mb_strlen($value) <= $limit) return $value;

        return rtrim(mb_substr($value, 0, $limit, 'UTF-8')).$end;
    }



    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int  $length
     * @return string
     *
     * @throws \RuntimeException
     */
    static function str_random($length = 16)
    {
        if(!function_exists('openssl_random_pseudo_bytes'))
        {
            throw new \Exception('OpenSSL extension is required.');
        }

        $bytes = openssl_random_pseudo_bytes($length * 2);
        if($bytes === false)
        {
            throw new \Exception('Unable to generate random string.');
        }

        return substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $length);
    }



    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param  string  $title
     * @param  string  $separator
     * @return string
     */
    static function str_slug($title, $separator = '-')
    {
        $title = ascii($title);
        // Convert all dashes/underscores into separator
        $flip = $separator == '-' ? '_' : '-';
        $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);
        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));
        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);
        return trim($title, $separator);
    }
}