<?php

class PHPGangsta_GoogleAuthenticator
{
    protected $_codeLength = 6;

    
    public function createSecret($secretLength = 16)
    {
        $validChars = $this->_getBase32LookupTable();

        
        if ($secretLength < 16 || $secretLength > 128) {
            throw new Exception('Bad secret length');
        }
        $secret = '';
        $rnd = false;
        if (function_exists('random_bytes')) {
            $rnd = random_bytes($secretLength);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $rnd = openssl_random_pseudo_bytes($secretLength, $cryptoStrong);
            if (!$cryptoStrong) {
                $rnd = false;
            }
        }
        if ($rnd !== false) {
            for ($i = 0; $i < $secretLength; ++$i) {
                $secret .= $validChars[ord($rnd[$i]) & 31];
            }
        } else {
            throw new Exception('No source of secure random');
        }

        return $secret;
    }

  
    public function getCode($secret, $timeSlice = null)
    {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretkey = $this->_base32Decode($secret);

        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        
        $offset = ord(substr($hm, -1)) & 0x0F;
        
        $hashpart = substr($hm, $offset, 4);

        $value = unpack('N', $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;

        $modulo = pow(10, $this->_codeLength);

        return str_pad($value % $modulo, $this->_codeLength, '0', STR_PAD_LEFT);
    }

    
    public function setCodeLength($length)
    {
        $this->_codeLength = $length;

        return $this;
    }

    
    protected function _base32Decode($secret)
    {
        if (empty($secret)) {
            return '';
        }
    
        $base32chars = $this->_getBase32LookupTable();
        $base32charsFlipped = array_flip($base32chars);
    
        $paddingChar = $base32chars[32];
        $paddingCharCount = substr_count($secret, $paddingChar);
        $allowedValues = array(6, 4, 3, 1, 0);
    
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }
    
        for ($i = 0; $i < 4; ++$i) {
            $padLen = $allowedValues[$i];
            if ($paddingCharCount === $padLen &&
                substr($secret, -$padLen) !== str_repeat($paddingChar, $padLen)) {
                return false;
            }
        }
    
        $secret = str_replace('=', '', $secret); // remove padding
        $secret = str_split($secret);
        $binaryString = '';
    
        for ($i = 0; $i < count($secret); $i += 8) {
            $x = '';
    
            for ($j = 0; $j < 8; ++$j) {
                if (!isset($secret[$i + $j])) {
                    continue;
                }
    
                $char = $secret[$i + $j];
    
                if (!isset($base32charsFlipped[$char])) {
                    return false;
                }
    
                $val = $base32charsFlipped[$char];
                $x .= str_pad(base_convert($val, 10, 2), 5, '0', STR_PAD_LEFT);
            }
    
            $eightBits = str_split($x, 8);
            foreach ($eightBits as $bits) {
                if (strlen($bits) === 8) {
                    $binaryString .= chr(bindec($bits));
                }
            }
        }
    
        return $binaryString;
    }
    

    
    protected function _getBase32LookupTable()
    {
        return array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
            'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
            '=',  // padding char
        );
    }

   
    private function timingSafeEquals($safeString, $userString)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($safeString, $userString);
        }
        $safeLen = strlen($safeString);
        $userLen = strlen($userString);

        if ($userLen != $safeLen) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $userLen; ++$i) {
            $result |= (ord($safeString[$i]) ^ ord($userString[$i]));
        }

        
        return $result === 0;
    }
}

?>