<?php
require_once __DIR__ . '/../config.php';

function test_database_connection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
    } catch (Exception $e) {
        return false;
    }
}

function test_user_authentication_functions_exist() {
    return function_exists('isLoggedIn') && 
           function_exists('validateSession') && 
           function_exists('getUserInfo');
}

function test_csrf_token_generation() {
    $token = generateCSRFToken();
    return !empty($token) && is_string($token);
}

function run_tests() {
    $tests = [
        'Database Connection' => test_database_connection(),
        'Auth Functions Exist' => test_user_authentication_functions_exist(),
        'CSRF Token Generation' => test_csrf_token_generation(),
    ];
    
    echo "Running Basic Tests...\n";
    echo str_repeat("=", 50) . "\n";
    
    $passed = 0;
    $failed = 0;
    
    foreach ($tests as $name => $result) {
        if ($result) {
            echo "✓ PASS: $name\n";
            $passed++;
        } else {
            echo "✗ FAIL: $name\n";
            $failed++;
        }
    }
    
    echo str_repeat("=", 50) . "\n";
    echo "Results: $passed passed, $failed failed\n";
    
    return $failed === 0;
}

if (php_sapi_name() === 'cli') {
    exit(run_tests() ? 0 : 1);
}
