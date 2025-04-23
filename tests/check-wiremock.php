<?php

/**
 * This script checks if WireMock is available before running tests.
 * It will retry a few times before giving up.
 */

$wiremockUrl = 'http://localhost:8080/__admin/mappings';
$maxRetries = 10;
$retryDelay = 2; // seconds

echo "Checking if WireMock is available at $wiremockUrl...\n";

for ($i = 0; $i < $maxRetries; $i++) {
    $ch = curl_init($wiremockUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "WireMock is available!\n";
        exit(0);
    }

    echo "WireMock not available yet (attempt " . ($i + 1) . " of $maxRetries). Retrying in $retryDelay seconds...\n";
    sleep($retryDelay);
}

echo "Error: WireMock is not available after $maxRetries attempts.\n";
exit(1);
