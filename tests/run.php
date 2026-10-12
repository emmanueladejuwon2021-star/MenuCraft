<?php
// tests/run.php - Standalone PHP Test Runner for MenuCraft

ini_set('assert.exception', 1);

require_once __DIR__ . '/PricingTest.php';
require_once __DIR__ . '/OrderBuildTest.php';

echo "=========================================\n";
echo " MenuCraft PHP Test Suite\n";
echo "=========================================\n\n";

try {
    testPricing();
    testOrderBuild();
    echo "\nAll tests passed successfully (100% assertions verified).\n";
    exit(0);
} catch (Throwable $e) {
    echo "\n❌ Test Failure: " . $e->getMessage() . "\n";
    echo "In " . $e->getFile() . " on line " . $e->getLine() . "\n";
    exit(1);
}
