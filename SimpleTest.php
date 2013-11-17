<?php // vim:ts=4:sts=4:sw=4:et:

namespace PHPCrypt;

class SimpleTest extends \PHPUnit_Framework_TestCase
{
    private $_instance;
    private $_encryptionKey = 'nXA5gXtlOgHgxl6EZTfkfDmIzWaRqxZ1rq7DRNCIQ/Q=';
    private $_macKey = 'K9iPmOMowXUvcQTd7ehfcxvvHd4OtzyztQp+wuQwb6U=';

    public function setUp() {
        $this->_instance = new \PHPCrypt\Simple($this->_encryptionKey, $this->_macKey);
    }

    public function testEncrypt() {
        $ciphertext = $this->_instance->encrypt('FooBar ', 'lAuCU7ft5tnHPKWRjF1IKV4J6V9/eCGQIisHZfuqMtY=');
        
        $this->assertEquals(
            'lAuCU7ft5tnHPKWRjF1IKV4J6V9/eCGQIisHZfuqMta/3gJg7IhHgxvaUY6isRyPcRq+x/rQfnPxY/A1XqY2nA==|6MBBCKBbYc+aWLpnk1MQVW2jM5Jnz5oHfXnDriyIL9Q=',
            $ciphertext
        );
    }
    
    public function testDecrypt() {
        $plaintext = $this->_instance->decrypt(
            'lAuCU7ft5tnHPKWRjF1IKV4J6V9/eCGQIisHZfuqMta/3gJg7IhHgxvaUY6isRyPcRq+x/rQfnPxY/A1XqY2nA==|6MBBCKBbYc+aWLpnk1MQVW2jM5Jnz5oHfXnDriyIL9Q='
        );
        
        $this->assertEquals('FooBar ', $plaintext);
    }

    /**
    /* This function encrypts the same string with randomized IVs and 
    /* flips a single bit of the ciphertext 
    */
    public function invalidCiphertextData() {
        $this->setUp();
        $invalidCiphertexts = array();

        for ($x = 0; $x < 100; $x++) {
            $signedCiphertext = $this->_instance->encrypt('Randomize this with new IVs');
            list($encodedCiphertext, $encodedSignature) = explode('|', $signedCiphertext);
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

            $invalidCiphertexts[] = array($encodedInvalidCiphertext . '|' . $encodedSignature);
        }

        return $invalidCiphertexts;
    }

    /**
     * @dataProvider invalidCiphertextData
     */
    public function testDecryptInvalidCiphertext($signedCiphertext) { 
        $this->setExpectedException('Exception', 'Invalid signature');
        $plaintext = $this->_instance->decrypt($signedCiphertext);
    }
    
    public function testEncryptAndDecrypt() {
        $plaintext = 'Something';
        $ciphertext = $this->_instance->encrypt('Something');
        $this->assertEquals($plaintext, $this->_instance->decrypt($ciphertext));
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
     */
    public function testEncryptInvalidEncryptionKeySize($invalidKey) {
        $this->setExpectedException('Exception');
        $instance = new \PHPCrypt\Simple($invalidKey, $this->_macKey);
    }
    
    /**
     * @dataProvider invalidKeyData
     */
    public function testEncryptInvalidMacKeySize($invalidKey) {
        $this->setExpectedException('Exception');
        $instance = new \PHPCrypt\Simple($this->_encryptionKey, $invalidKey);
    }

    public function testEncryptInvalidIvLength() {
        $this->setExpectedException('Exception');

        $ciphertext = $this->_instance->encrypt(
            'FooBar ', $this->_encryptionKey, $this->macKey, 'Short IV');

        $this->assertEquals('This should never execute', base64_encode($ciphertext));
    }

    public function testGenerateIv() {
        $iv = $this->_instance->generateIv();
        $secondIv = $this->_instance->generateIv();
        $this->assertNotEquals($iv, $secondIv);
    }
}
