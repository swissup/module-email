<?php
/**
 * Scripted SMTP server for RequireTlsSmtpTest, run in a child process.
 *
 * Usage: php smtp-server.php <cert.pem|-> <steps-json>
 *
 * Prints the listening address, then every line received from the client
 * prefixed with "> ". The first step is the greeting; every other string step
 * reads one client line and writes the reply. "TLS" upgrades the connection,
 * "CLOSE" drops it.
 *
 * phpcs:ignoreFile -- standalone CLI process: exit codes and a silenced handshake are intended
 */
declare(strict_types=1);

[, $cert, $steps] = $argv;

$context = stream_context_create(
    $cert === '-' ? [] : ['ssl' => ['local_cert' => $cert, 'verify_peer' => false]]
);
$server = stream_socket_server(
    'tcp://127.0.0.1:0',
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
    $context
);
if (!$server) {
    fwrite(STDERR, $errstr);
    exit(1);
}
fwrite(STDOUT, stream_socket_get_name($server, false) . "\n");

$conn = stream_socket_accept($server, 5);
if (!$conn) {
    exit(1);
}
stream_set_timeout($conn, 5);

foreach (json_decode($steps, true) as $i => $step) {
    if ($step === 'TLS') {
        fwrite(STDOUT, "TLS\n");
        if (!@stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_SERVER)) {
            fwrite(STDOUT, "TLS FAILED\n");
            exit(1);
        }
        continue;
    }
    if ($step === 'CLOSE') {
        fclose($conn);
        exit(0);
    }
    if ($i > 0) {
        $line = fgets($conn);
        if ($line === false) {
            exit(0);
        }
        fwrite(STDOUT, '> ' . rtrim($line) . "\n");
    }
    fwrite($conn, $step);
}

while (($line = fgets($conn)) !== false) {
    fwrite(STDOUT, '> ' . rtrim($line) . "\n");
}
