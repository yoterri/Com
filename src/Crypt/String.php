<?php
/**
 * encode/decode a string message v.1.0
 * There is a string $key defined by default but
 * $key can be defined on the fly by the function caller
 * the three conditions for the string $key are
 * 1.- can not contain numbers 0,1,2,3,4,5,6,7,8,9 because are part of the algorithm
 * 2.- the length of string must be greater than 25 bytes
 * 3.- The string must be the same to encode or to decode
 *
 * Author   : Aitor Solozabal Merino (spain)
 * Email    : aitor-3@euskalnet.net
 * Date     : 07-10-2007      $Len_Simbolos = strlen($SIMBOLOS);
 * Enter description here ...
 * @author yoterri
 *
 */
namespace Com\Crypt;

class String
{

    const DEFAULT_KEY = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     *
     * @param string $string            
     * @param string $key            
     * @return string
     */
    static function encode($string, $key = self::DEFAULT_KEY)
    {
        $lenStrMessage = strlen($string);
        $lenSimbolos = strlen($key);
        
        $strEncodedMessage = '';
        for ($position = 0; $position < $lenStrMessage; $position ++) {
            $byteToBeEncoded = substr($string, $position, 1);
            $asciiNumByteToEncode = ord($byteToBeEncoded);
            $restModSimbols = ($asciiNumByteToEncode + $lenSimbolos) % $lenSimbolos;
            $plusModSimbols = (int) ($asciiNumByteToEncode / $lenSimbolos);
            $Encoded_Byte = substr($key, $restModSimbols, 1);
            if ($plusModSimbols == 0) {
                $strEncodedMessage .= $Encoded_Byte;
            } else {
                $strEncodedMessage .= $plusModSimbols . $Encoded_Byte;
            }
        }
        
        return $strEncodedMessage;
    }

    /**
     *
     * @param string $string            
     * @param string $key            
     */
    static function decode($string, $key = self::DEFAULT_KEY)
    {
        $lenStrMessage = strlen($string);
        $LenSimbolos = strlen($key);
        
        $strDecodedMessage = '';
        for ($position = 0; $position < $lenStrMessage; $position ++) {
            $plusModSimbols = 0;
            $byteToBeDecoded = substr($string, $position, 1);
            if ($byteToBeDecoded > 0) {
                $plusModSimbols = $byteToBeDecoded;
                $position ++;
                $byteToBeDecoded = substr($string, $position, 1);
            }
            // finding the position in the string
            $key;
            $byteDecoded = 0;
            for ($secondPosition = 0; $secondPosition < $LenSimbolos; $secondPosition ++) {
                $byteToBeCompared = substr($key, $secondPosition, 1);
                if ($byteToBeDecoded == $byteToBeCompared) {
                    $byteDecoded = $secondPosition;
                }
            }
            
            $byteDecoded = ($plusModSimbols * $LenSimbolos) + $byteDecoded;
            $asciiNumByteToDecode = chr($byteDecoded);
            $strDecodedMessage .= $asciiNumByteToDecode;
        }
        
        return $strDecodedMessage;
    }
}
