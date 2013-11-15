<?php // vim:ts=4:sts=4:sw=4:et:

/**
 * Simple Encryption Library For PHP 5.3+
 *
 * PHP Version 5.3
 *
 * @category  PHP
 * @package   PHPCrypt
 * @author    Bryan C. Geraghty <bryan@ravensight.org>
 * @copyright 2013 Bryan C. Geraghty
 * @license   https://github.com/archwisp/PHPCrypt/blob/master/LICENSE MIT
 * @link      https://github.com/archwisp/PHPCrypt
 */

namespace PHPCrypt;

class Simple
{
    private $_encryptionAlgorithm = MCRYPT_RIJNDAEL_256;
    private $_mode = MCRYPT_MODE_CBC;
    private $_macAlgorithm = 'sha256';
    private $_macByteCount = 32;
    private $_encryptionKey;
    private $_macKey;

    public function __construct($encryptionKey, $macKey) {
        $this->_encryptionKey = base64_decode($encryptionKey);
        $this->_macKey = base64_decode($macKey);
    }

    public function encrypt($plaintext, $iv = null) {
        if (is_null($iv)) {
            $iv = $this->generateIv();
        }
        
        $decodedIv = base64_decode($iv);
        $paddedPlaintext = $this->padWithPkcs7($plaintext);

        $ciphertext = $decodedIv . mcrypt_encrypt($this->_encryptionAlgorithm, 
            $this->_encryptionKey, $paddedPlaintext, $this->_mode, $decodedIv 
        );

        $signature = hash_hmac($this->_macAlgorithm, $ciphertext, $this->_macKey, true);

        return base64_encode($ciphertext) . '|' . base64_encode($signature);
    }

    public function decrypt($signedCiphertext) {
        $signedCiphertextParts = explode('|', $signedCiphertext);

        if (count($signedCiphertextParts) !== 2) {
            throw new \RuntimeException('Invalid signature');
        }

        $decodedCiphertext = base64_decode($signedCiphertextParts[0]);
        
        $signature = hash_hmac($this->_macAlgorithm, 
            $decodedCiphertext, $this->_macKey, true
        );

        if (!$this->_compareMac($signature, base64_decode($signedCiphertextParts[1]))) {
            throw new \RuntimeException('Invalid signature');
        }

        $iv = substr($decodedCiphertext, 0, $this->getBlockSize());
        $ciphertext = substr($decodedCiphertext, $this->getBlockSize());

        $paddedPlaintext = mcrypt_decrypt($this->_encryptionAlgorithm,
            $this->_encryptionKey, $ciphertext, $this->_mode, $iv);

        return $this->trimPkcs7($paddedPlaintext);
    }

    public function generateIv() {
        return base64_encode(mcrypt_create_iv($this->getBlockSize(), MCRYPT_DEV_URANDOM));
    }

    public function generateKey() {
        return base64_encode(mcrypt_create_iv($this->getKeySize(), MCRYPT_DEV_URANDOM));
    }
    
    private function getBlockSize() {
        return mcrypt_get_iv_size($this->_encryptionAlgorithm, $this->_mode);
    }

    private function getKeySize() {
        return mcrypt_get_key_size($this->_encryptionAlgorithm, $this->_mode);
    }

    private function padWithPkcs7($plaintext) {
        $block_size = $this->getBlockSize();

        if ($block_size < 256) {
            throw new \RuntimeException('PKCS7 padding is only well defined for block sizes smaller than 256 bits');
        }

        $pad_length = ($block_size - (strlen($plaintext) % $block_size));

        return $plaintext . str_repeat(chr($pad_length), $pad_length);
    }

    private function trimPkcs7($plaintext) {
        $pad_char = substr($plaintext, -1);
        $pad_length = ord($pad_char);

        if (substr($plaintext, -$pad_length) !== str_repeat($pad_char, $pad_length)) {
            throw new \RuntimeException('Invalid pad value');
        }

        return substr($plaintext, 0, -$pad_length);
    }

    /**
     * Constant-time comparison function
     *
     * Stolen and adapted from: 
     * https://cryptocoding.net/index.php/Coding_rules#Compare_secret_strings_in_constant_time
     *
     * DON'T MESS WITH THIS FUNCTION
     *
     * returns boolean true on match, otherwise, false 
     */
    private function _compareMac($a, $b) {
        $result = "\x00";

        for ($i = 0; $i < $this->_macByteCount; $i++) {
            $result |= substr($a, $i, 1) ^ substr($b, $i, 1);
        }
        
        /* \x00 if equal, nonzero otherwise */
        return ($result === "\x00");
    }
}
