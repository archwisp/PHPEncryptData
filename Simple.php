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

    const RJD_256_HMAC_SHA256 = 'rjd-256-hmac-sha256';

    public function __construct($encryptionKey, $macKey) {
        $this->_encryptionKey = base64_decode($encryptionKey);
        $this->_macKey = base64_decode($macKey);

        $keySize = $this->_getKeySize();

        if (strlen($this->_encryptionKey) !== $keySize) {
            throw new \InvalidArgumentException(
                sprintf('Encryption key must be exactly %s bytes long.', $keySize)
            );
        }

        if (strlen($this->_macKey) !== $keySize) {
            throw new \InvalidArgumentException(
                sprintf('MAC key must be exactly %s bytes long.', $keySize)
            );
        }
    }

    public function encrypt($plaintext, $iv = null) {
        if (is_null($iv)) {
            $iv = $this->generateIv();
        }

        $decodedIv = base64_decode($iv);

        if (strlen($decodedIv) !== $this->_getBlockSize()) {
            throw new \InvalidArgumentException(
                sprintf('IV must be exactly %s bytes long.', $this->_getBlockSize())
            );
        }

        $paddedPlaintext = $this->_padWithPkcs7($plaintext);

        $ciphertext = $decodedIv . mcrypt_encrypt($this->_encryptionAlgorithm,
            $this->_encryptionKey, $paddedPlaintext, $this->_mode, $decodedIv
        );

        $signature = hash_hmac($this->_macAlgorithm, $ciphertext, $this->_macKey, true);

        return base64_encode(
            self::RJD_256_HMAC_SHA256 . '|' . base64_encode($ciphertext) . '|' . base64_encode($signature)
        );
    }

    public function decrypt($signedCiphertext) {
        $signedCiphertext = base64_decode($signedCiphertext);
        $signedCiphertextParts = explode('|', $signedCiphertext);

        if (count($signedCiphertextParts) !== 3) {
            throw new \RuntimeException('Invalid encoding');
        }

        if ($signedCiphertextParts[0] !== self::RJD_256_HMAC_SHA256) {
            throw new \RunTimeException(sprintf('Unknown construction, "%s"', $signedCiphertextParts[0]));
        }

        $decodedCiphertext = base64_decode($signedCiphertextParts[1]);
        
        $signature = hash_hmac($this->_macAlgorithm, $decodedCiphertext, $this->_macKey, true);

        if (!$this->_compareMac($signature, base64_decode($signedCiphertextParts[2]))) {
            throw new \RuntimeException('Invalid signature');
        }

        $iv = substr($decodedCiphertext, 0, $this->_getBlockSize());
        $ciphertext = substr($decodedCiphertext, $this->_getBlockSize());

        $paddedPlaintext = mcrypt_decrypt($this->_encryptionAlgorithm,
            $this->_encryptionKey, $ciphertext, $this->_mode, $iv);

        return $this->_trimPkcs7($paddedPlaintext);
    }

    public function generateIv() {
        return base64_encode(mcrypt_create_iv($this->_getBlockSize(), MCRYPT_DEV_URANDOM));
    }

    private function _getBlockSize() {
        return mcrypt_get_iv_size($this->_encryptionAlgorithm, $this->_mode);
    }

    private function _getKeySize() {
        return mcrypt_get_key_size($this->_encryptionAlgorithm, $this->_mode);
    }

    private function _padWithPkcs7($plaintext) {
        $blockSize = $this->_getBlockSize();

        if ($blockSize > 255) {
            throw new \RuntimeException('PKCS7 padding is only well defined for block sizes smaller than 256 bits');
        }

        $padLength = ($blockSize - (strlen($plaintext) % $blockSize));

        return $plaintext . str_repeat(chr($padLength), $padLength);
    }

    private function _trimPkcs7($plaintext) {
        $padChar = substr($plaintext, -1);
        $padLength = ord($padChar);

        if (substr($plaintext, -$padLength) !== str_repeat($padChar, $padLength)) {
            throw new \RuntimeException('Invalid pad value');
        }

        return substr($plaintext, 0, -$padLength);
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
