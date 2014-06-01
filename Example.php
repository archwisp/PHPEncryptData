<?php // vim:ts=4:sts=4:sw=4:et:

require __DIR__ . '/vendor/autoload.php';

$phpcrypt = new \PHPEncryptData\Simple(
    '6zp4y5vnUQpfEroWI6dMq5lC46F5Dmqa4NDcM1W42k=',
    'RJikKksPg3UmqgQPXBwCmcSOMHQn0iOtQAFcfRQOTU='
);

$ciphertext = $phpcrypt->encrypt('Foobar');
printf("Ciphertext: %s\n", $ciphertext);

$decrypted = $phpcrypt->decrypt($ciphertext);
printf("Decrypted: %s\n", $decrypted);
