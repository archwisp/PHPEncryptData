<?php // vim:ts=4:sts=4:sw=4:et:

require __DIR__ . '/vendor/autoload.php';

$phpcrypt = new \PHPCrypt\Simple(
    '6zp4y5vnUQpfEroWI6dMq5lC46F5Dmqa4NDcM1W4u2k=',
    'RJikKksPg3UmqgQPXBwCmcSOMHQn0iOtQAFcfRcQOTU='
);

$ciphertext = $phpcrypt->encrypt('Foobar');
printf("Ciphertext: %s\n", $ciphertext);

$decrypted = $phpcrypt->decrypt($ciphertext);
printf("Decrypted: %s\n", $decrypted);
