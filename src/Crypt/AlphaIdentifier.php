<?php
/**
 * Translates a number to a short alhanumeric version
 *
 * Translated any number up to 9007199254740992
 * to a shorter version in letters e.g.:
 * 9007199254740989 --> PpQXn7COf
 *
 * this function is based on any2dec && dec2any by
 * fragmer[at]mail[dot]ru
 * see: http://nl3.php.net/manual/en/function.base-convert.php#52450
 *
 * The reverse is done because it makes it slightly more cryptic,
 * but it also makes it easier to spread lots of IDs in different
 * directories on your filesystem. Example:
 * $part1 = substr($alpha_id,0,1);
 * $part2 = substr($alpha_id,1,1);
 * $part3 = substr($alpha_id,2,strlen($alpha_id));
 * $destindir = "/".$part1."/".$part2."/".$part3;
 * // by reversing, directories are more evenly spread out. The
 * // first 26 directories already occupy 26 main levels
 *
 * more info on limitation:
 * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
 *
 * if you really need this for bigger numbers you probably have to look
 * at things like: http://theserverpages.com/php/manual/en/ref.bc.php
 * or: http://theserverpages.com/php/manual/en/ref.gmp.php
 * but I haven't really dugg into this. If you have more info on those
 * matters feel free to leave a comment.
 *
 * @author  Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author  Simon Franz
 * @author  Deadfish
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
 * @link    http://kevin.vanzonneveld.net/
 *
 */
namespace Com\Crypt;

class AlphaIdentifier
{

    /**
     *
     * @var string
     */
    protected $_index = 'abcdefghjklmnpqrstuvwxyz0123456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     *
     * @var int
     */
    protected $_padUp = 5;

    /**
     *
     * @var string
     */
    protected $_passKey = null;

    /**
     *
     * @var bool
     */
    protected $_passKeyDone = false;

    /**
     *
     * @param int $value            
     * @return \Com\Crypt\AlphaIdentifier
     */
    function setPadUp($value)
    {
        $value = abs((int) $value);
        if (0 == $value)
            $value = 1;
        
        $this->_padUp = $value;
        return $this;
    }

    /**
     *
     * @return int
     */
    function getPadUp()
    {
        return $this->_padUp;
    }

    /**
     *
     * @param string $value            
     * @return \Com\Crypt\AlphaIdentifier
     */
    function setPassKey($value)
    {
        $this->_passKey = $value;
        return $this;
    }

    /**
     *
     * @return string
     */
    function getPassKey()
    {
        return $this->_passKey;
    }

    /**
     *
     * @param int $int
     *            return bool
     */
    function checkInt($int)
    {
        return preg_match('/^[0-9]+$/', $int) && ($int <= PHP_INT_MAX);
    }

    /**
     *
     * @param int $int            
     * @return string
     */
    function encode($int)
    {
        if (! $this->checkInt($int)) {
            throw new \Exception('Please, provide a valid int value. Max ' . PHP_INT_MAX);
        }
        
        $this->_checkPass();
        $base = strlen($this->_index);
        
        // Digital number -->> alphabet letter code
        if (($this->_padUp - 1) > 0)
            $int += pow($base, ($this->_padUp - 1));
        
        $out = '';
        for ($t = floor(log($int, $base)); $t >= 0; $t --) {
            $bcp = bcpow($base, $t);
            $a = floor($int / $bcp) % $base;
            $out = $out . substr($this->_index, $a, 1);
            $int = $int - ($a * $bcp);
        }
        
        $out = strrev($out); // reverse
        
        return $out;
    }

    /**
     *
     * @param string $string            
     * @return int
     */
    function decode($string)
    {
        $this->_checkPass();
        $base = strlen($this->_index);
        
        // Digital number <<-- alphabet letter code
        $string = strrev($string);
        $out = 0;
        $len = strlen($string) - 1;
        
        for ($t = 0; $t <= $len; $t ++) {
            $bcpow = bcpow($base, $len - $t);
            $out = $out + strpos($this->_index, substr($string, $t, 1)) * $bcpow;
        }
        
        if (($this->_padUp - 1) > 0)
            $out -= pow($base, ($this->_padUp - 1));
        
        $out = sprintf('%F', $out);
        $out = substr($out, 0, strpos($out, '.'));
        
        return $out;
    }

    /**
     *
     * @param int $int            
     * @param string $base            
     * @return int
     */
    protected function _int($int, $base)
    {
        if (($this->_padUp - 1) > 0)
            $int -= pow($base, ($this->_padUp - 1));
        
        return $int;
    }

    protected function _checkPass()
    {
        if ($this->_passKey !== null && ! $this->_passKeyDone) {
            // Although this function's purpose is to just make the
            // ID short - and not so much secure,
            // with this patch by Simon Franz (http://blog.snaky.org/)
            // you can optionally supply a password to make it harder
            // to calculate the corresponding numeric ID
            
            $len = strlen($this->_index);
            for ($n = 0; $n < $len; $n ++)
                $i[] = substr($this->_index, $n, 1);
            
            $this->_passKey = hash('sha256', $this->_passKey);
            $this->_passKey = (strlen($this->_passKey) < $len) ? hash('sha512', $this->_passKey) : $this->_passKey;
            
            for ($n = 0; $n < $len; $n ++)
                $p[] = substr($this->_passKey, $n, 1);
            
            array_multisort($p, SORT_DESC, $i);
            
            $this->_index = implode($i);
            $this->_passKeyDone = true;
        }
    }
}