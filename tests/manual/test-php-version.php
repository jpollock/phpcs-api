<?php
/**
 * Manual test script for PHP version compatibility testing.
 * 
 * This script demonstrates how to use the PHP version compatibility testing feature
 * of the PHPCS API. It sends requests to test different PHP code samples against
 * different PHP versions.
 * 
 * Usage:
 * php test-php-version.php
 */

// Configuration
$apiUrl = 'http://localhost:8080/v1/analyze';
$apiKey = 'YOUR_API_KEY'; // Replace with your actual API key

// Test cases
$testCases = [
    [
        'name' => 'PHP 7.0 Type Declarations',
        'code' => '<?php
function test(string $param): string {
    return $param;
}',
        'phpVersion' => '5.6',
        'expectedIssues' => 2, // Should find 2 issues: parameter type and return type
    ],
    [
        'name' => 'PHP 7.0 Type Declarations (Compatible)',
        'code' => '<?php
function test(string $param): string {
    return $param;
}',
        'phpVersion' => '7.0',
        'expectedIssues' => 0, // Should be compatible with PHP 7.0
    ],
    [
        'name' => 'PHP 7.4 Arrow Functions',
        'code' => '<?php
$fn = fn($x) => $x + 1;',
        'phpVersion' => '7.3',
        'expectedIssues' => 1, // Should find 1 issue: arrow functions require PHP 7.4+
    ],
    [
        'name' => 'PHP 7.4 Arrow Functions (Compatible)',
        'code' => '<?php
$fn = fn($x) => $x + 1;',
        'phpVersion' => '7.4',
        'expectedIssues' => 0, // Should be compatible with PHP 7.4
    ],
    [
        'name' => 'PHP 8.0 Named Arguments',
        'code' => '<?php
function test($a, $b, $c) {
    return $a + $b + $c;
}

$result = test(c: 3, a: 1, b: 2);',
        'phpVersion' => '7.4',
        'expectedIssues' => 1, // Should find 1 issue: named arguments require PHP 8.0+
    ],
    [
        'name' => 'PHP 8.0 Named Arguments (Compatible)',
        'code' => '<?php
function test($a, $b, $c) {
    return $a + $b + $c;
}

$result = test(c: 3, a: 1, b: 2);',
        'phpVersion' => '8.0',
        'expectedIssues' => 0, // Should be compatible with PHP 8.0
    ],
];

// Function to send API request
function sendRequest($url, $apiKey, $code, $phpVersion) {
    $data = [
        'code' => $code,
        'standard' => 'PHPCompatibility',
        'phpVersion' => $phpVersion,
        'options' => [
            'report' => 'json'
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => "HTTP Error: $httpCode",
            'response' => $response
        ];
    }

    return json_decode($response, true);
}

// Run tests
echo "PHP Version Compatibility Testing\n";
echo "================================\n\n";

foreach ($testCases as $index => $test) {
    echo "Test Case " . ($index + 1) . ": " . $test['name'] . "\n";
    echo "PHP Version: " . $test['phpVersion'] . "\n";
    echo "Code:\n" . $test['code'] . "\n";

    $result = sendRequest($apiUrl, $apiKey, $test['code'], $test['phpVersion']);

    if (!isset($result['success']) || $result['success'] !== true) {
        echo "ERROR: API request failed\n";
        if (isset($result['error'])) {
            echo "Error message: " . $result['error'] . "\n";
        }
        echo "\n";
        continue;
    }

    $totalIssues = $result['results']['totals']['errors'] + $result['results']['totals']['warnings'];
    $passed = $totalIssues === $test['expectedIssues'];

    echo "Result: " . ($passed ? "PASSED" : "FAILED") . "\n";
    echo "Found $totalIssues issues, expected " . $test['expectedIssues'] . "\n";

    if ($totalIssues > 0) {
        echo "Issues:\n";
        foreach ($result['results']['files'] as $file) {
            foreach ($file['messages'] as $message) {
                echo "- " . $message['message'] . " (Line " . $message['line'] . ")\n";
            }
        }
    }

    echo "\n";
}

echo "Testing completed.\n";
