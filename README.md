# \PHPEncryptData\ - Simple Data Encryption Library For PHP 5.3+

[![Build Status](https://travis-ci.org/archwisp/PHPEncryptData.svg?branch=master)](https://travis-ci.org/archwisp/PHPEncryptData) [![Coverage Status](https://img.shields.io/coveralls/archwisp/PHPEncryptData.svg)](https://coveralls.io/r/archwisp/PHPEncryptData)

If you are looking for the answer to the question, "How do I encrypt
sensitive data in PHP?", you are in the correct place. Read through
this README, execute a couple of commands, write about four lines of
code, and you will have secure encryption.

## Installation

Install via Composer:

1. [Download Composer](http://getcomposer.org/download/) using your preferred method.
2. Add PHPEncryptData to your project:

        $ php composer.phar require archwisp/php-encrypt-data

## Basic Usage

1. Generate your encryption key:

        $ head -c 32 /dev/urandom | base64
        6zp4y5vnUQpfEroWI6dMq5lC46F5Dmqa4NDcM1W42k=

2. Generate your MAC key:

        $ head -c 32 /dev/urandom | base64
        RJikKksPg3UmqgQPXBwCmcSOMHQn0iOtQAFcfcQOTU=

3. Write your code:

        <?php

        require __DIR__ . '/vendor/autoload.php';

        // These keys won't actually work... on purpose. Create your OWN!
        
        $phpcrypt = new \PHPEncryptData\Simple(
                '6zp4y5vnUQpfEroWI6dMq5lC46F5Dmqa4NDcM1W42k='
                'RJikKksPg3UmqgQPXBwCmcSOMHQn0iOtQAFcfcQOTU='
        );

        $ciphertext = $phpcrypt->encrypt('Foobar');
        printf("Ciphertext: %s\n", $ciphertext);

        $decrypted = $phpcrypt->decrypt($ciphertext);
        printf("Decrypted: %s\n", $decrypted);

## How This Library Came To Be

A fellow programmer/friend whom I know from my local PHP user group was
tasked with encrypting some data in an application and started searching
Google for a good solution. After finding something promising, she sent
what she had found to me asking for a review, knowing that I have a deep
interest and some background in cryptography. Upon reviewing the code, I
found some critical problems. As I wrote my response and explained the
fixes that would need to be implemented, I realized that these are not
things that I could expect anyone without some cryptanalysis experience to
to implement correctly.

So, I set out to create this library in which I have made all of the hard
decisions for you. There are better constructions out there but they come
with some baggage that is more difficult to handle and so they are not
really suited for "simple" libraries. In this library's current design,
all you must do is run a couple of commands to generate a couple of keys
that will be stuck in your code. Then, you just call the encrypt() and
decrypt() functions when you need them.

My goal with this library is to improve the baseline for encryption in PHP.

## Calling Smart Crypto People

Every bit of review and feedback you're willing to provide is appreciated.
If you see a major bug, efficiency improvement, or design decision that can 
be improved in a concrete way, please submit an issue. src/Simple.php is where
everything happens and tests/SimpleTest.php contains the unit tests.

Thank you!

## Cryptographic Details

For most cryptographic functionality, this library makes use of the Mcrypt
extension. The extension has been around for over a decade but is not
include by default on many distributions. You will need to ensure that you
have it installed.

The cryptographic primitives & practices behind this library are:

* 192-bit Rijndael block cipher (256-bit key and 192-bit block size; not AES compatible)
* CFB block cipher mode
* HMAC-SHA-256/128 (first 128 bits of output)
* Encrypt-Then-MAC construction
* Constant-time MAC comparison
* Uses /dev/urandom as its PRNG

## Example (Explained)

The goal of this library was secure defaults and simplicity. There are
five simple steps to follow to encrypt and decrypt data (see the
Example.php script to see it all in one place):

1. Find a Linux machine that has been running for more than 15 minutes and
generate an encryption key with the following command:

        head -c 32 /dev/urandom | base64

    The output will look something like (Don't use this sample key!... it won't work anyway):
        
        6zp4y5vnUQpfEroWI6dMq5lC46F5Dmqa4NDM1W42k=

2. Run the command a second time to generate your MAC key:

        head -c 32 /dev/urandom | base64

    Again, the output will again look like this (Ensure that it is not the same as the encryption key.):

        RJikKksPg3UmqgQPXBwCmcSOMHQn0iOtQAFccQOTU=

3. Given the above inputs, add the following code to your bootstrap:

        <?php

        require __DIR__ . '/vendor/autoload.php';

        $phpcrypt = new \PHPEncryptData\Simple(
            '6zp4y5vnUQpfEroWI6dMq5lC46F5Dmqa4NM1W4u2k=',
            'RJikKksPg3UmqgQPXBwCmcSOMHQn0iOtFcfRcQOTU='
        );

4. Call the encrypt() function:

        $ciphertext = $phpcrypt->encrypt('Foobar');

    This will generate output similar to this:  

        MHxjcUhrSnR5cUlnc2RLbmxGRkJFRUtZMmUyQkdvM01pVnlaRk5XM2VjfEJyc0FFaEhUZGs1T3A4VElFUFJLUXc9PQ==

    As you can see, the output is base64 encoded for you and the MAC is
    appended automatically, so you don't have to worry about any of the
    cryptographic details. Even the construction is encoded into this 
    string to account for future changes. Just feed plaintext in, and 
    encoded & signed ciphertext comes out.

    Also, keep in mind that the IV for each encryption is randomized, so
    encrypting the same value will produce different ciphertexts. The
    encrypt() function accepts an IV as an optional second argument if you
    need to manually control it. Most people should not use it.

5. Call the decrypt() function:

        $ciphertext = $phpcrypt->decrypt(
            'MHxjcUhrSnR5cUlnc2RLbmxGRkJFRUtZMmUyQkdvM01pVnlaRk5XM2VjfEJyc0FFaEhUZGs1T3A4VElFUFJLUXc9PQ=='
        );

    This will produce the value:

        'Foobar'

## Unit Tests

1. [Download Composer](http://getcomposer.org/download/) using your preferred method.

2. From the PHPEncryptData directory, install the project dependencies:

        $ php composer.phar install

3. (optional) If you want to customize the PHPUnit configuration:

        $ cp phpunit.xml.dist phpunit.xml

    Then customize phpunit.xml to your liking.

4. Execute `phpunit` binary from the project root:

        $ ./vendor/bin/phpunit

## Credits

* Bryan C. Geraghty: Original author
* John Kary: Responsible for really cleaning the project up
