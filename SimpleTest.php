<?php // vim:ts=4:sts=4:sw=4:et:

namespace PHPCrypt;

class SimpleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Simple
     */
    private $_instance;
    private $_encryptionKey = 'nXA5gXtlOgHgxl6EZTfkfDmIzWaRqxZ1rq7DRNCIQ/Q=';
    private $_macKey = 'K9iPmOMowXUvcQTd7ehfcxvvHd4OtzyztQp+wuQwb6U=';

    public function setUp() {
        $this->_instance = new Simple($this->_encryptionKey, $this->_macKey);
    }

    public function testCanEncryptPlaintext() {
        $ciphertext = $this->_instance->encrypt('FooBar ', 'lAuCU7ft5tnHPKWRjF1IKV4J6V9/eCGQIisHZfuqMtY=');

        $this->assertSame(
            'cmpkLTI1Ni1obWFjLXNoYTI1NnxsQXVDVTdmdDV0bkhQS1dSakYxSUtWNEo2VjkvZUNHUUlpc0haZnVxTXRhLzNnSmc3SWhIZ3h2YVVZNmlzUnlQY1JxK3gvclFmblB4WS9BMVhxWTJuQT09fDZNQkJDS0JiWWMrYVdMcG5rMU1RVlcyak01Sm56NW9IZlhuRHJpeUlMOVE9',
            $ciphertext
        );
    }

    public function testCanDecryptCiphertext() {
        $plaintext = $this->_instance->decrypt(
            'cmpkLTI1Ni1obWFjLXNoYTI1NnxsQXVDVTdmdDV0bkhQS1dSakYxSUtWNEo2VjkvZUNHUUlpc0haZnVxTXRhLzNnSmc3SWhIZ3h2YVVZNmlzUnlQY1JxK3gvclFmblB4WS9BMVhxWTJuQT09fDZNQkJDS0JiWWMrYVdMcG5rMU1RVlcyak01Sm56NW9IZlhuRHJpeUlMOVE9'
        );

        $this->assertSame('FooBar ', $plaintext);
    }

    /**
     * @expectedException \RunTimeException
     * @expectedExceptionMessage Unknown construction, "rjd-256-hmac-sha256/128"
     */
    public function testDecryptingSignedCiphertextWithUnknownConstructionThrowsException() {
        $this->_instance->decrypt(
            'cmpkLTI1Ni1obWFjLXNoYTI1Ni8xMjh8bEF1Q1U3ZnQ1dG5IUEtXUmpGMUlLVjRKNlY5L2VDR1FJaXNIWmZ1cU10YS8zZ0pnN0loSGd4dmFVWTZpc1J5UGNScSt4L3JRZm5QeFkvQTFYcVkybkE9PXw2TUJCQ0tCYlljK2FXTHBuazFNUVZXMmpNNUpuejVvSGZYbkRyaXlJTDlRPQ=='
        );
    }

    /**
     * Encrypts the same string with randomized IVs and flips a single bit of
     * the ciphertext.
     */
    public function invalidCiphertextData() {
        $this->setUp();
        $invalidCiphertexts = array();

        for ($x = 0; $x < 100; $x++) {
            $signedCiphertext = $this->_instance->encrypt('Randomize this with new IVs');
            $signedCiphertext = base64_decode($signedCiphertext);
            list($construction, $encodedCiphertext, $encodedSignature) = explode('|', $signedCiphertext);
            $ciphertext = base64_decode($encodedCiphertext);
            $randomByte = rand(1, strlen($ciphertext));
            $mask = str_repeat("\x00", $randomByte -1) . "\x01" . str_repeat("\x00", strlen($ciphertext) - $randomByte);

            // SANITY CHECK: If this mask is removed, this test should fail every 
            // single run because the ciphertext should match.
            $invalidCiphertext = $ciphertext ^ $mask;

            // printf("Ciphertext:         %s\n", bin2hex($ciphertext));
            // printf("Mask:               %s\n", bin2hex($mask));
            // printf("Invalid Ciphertext: %s\n", bin2hex($invalidCiphertext));

            $encodedInvalidCiphertext = base64_encode($invalidCiphertext);
            $invalidCiphertexts[] = array(base64_encode($construction . '|' . $encodedInvalidCiphertext . '|' . $encodedSignature));
        }

        return $invalidCiphertexts;
    }

    /**
     * @dataProvider invalidCiphertextData
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid signature
     */
    public function testDecryptingInvalidCiphertextThrowsException($signedCiphertext) {
        $this->_instance->decrypt($signedCiphertext);
    }

    public function testEncryptedDataCanBeDecrypted() {
        $plaintext = 'Something';
        $ciphertext = $this->_instance->encrypt('Something');

        $this->assertSame($plaintext, $this->_instance->decrypt($ciphertext));
    }

    public function invalidKeyData() {
        return array(
            array(null),
            array(''),
            array('Foo'),
            array('0123456789ABCDF0123456789ABCDF'),
            array('0123456789ABCDF0123456789ABCDF0123456789ABCDEF'),
            array('3e5VO09Oslbw/sskJPdloizTQ/2iz8Icyo+VT3PxYW='),
        );
    }

    /**
     * @dataProvider invalidKeyData
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Encryption key must be
     */
    public function testInvalidEncryptionKeyThrowsException($invalidKey) {
        new Simple($invalidKey, $this->_macKey);
    }

    /**
     * @dataProvider invalidKeyData
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage MAC key must be
     */
    public function testInvalidMacKeySizeThrowsException($invalidKey) {
        new Simple($this->_encryptionKey, $invalidKey);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage IV must be exactly
     */
    public function testEncryptWithInvalidIvLengthThrowsException() {
        $this->_instance->encrypt('FooBar ', 'wrong-iv-length');
    }

    public function testDifferentIvGeneratedOnEachRun() {
        $iv = $this->_instance->generateIv();
        $secondIv = $this->_instance->generateIv();

        $this->assertNotSame($iv, $secondIv);
    }
}
