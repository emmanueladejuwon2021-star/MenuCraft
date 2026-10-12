<?php
// tests/PricingTest.php - Tests for pricing logic
require_once __DIR__ . '/../includes/pricing.php';

function testPricing(): void {
    echo "Running Pricing tests...\n";

    // Percent change rounds to cents and cannot go below zero
    assert(applyPrice(1000, "percent", 10) === 1100.0, "1000 + 10% should be 1100");
    assert(applyPrice(1999, "percent", -50) === 999.5, "1999 - 50% should be 999.5");
    assert(applyPrice(100, "percent", -200) === 0.0, "100 - 200% should floor at 0");

    // Fixed amount change floors at zero
    assert(applyPrice(2500, "amount", 150) === 2650.0, "2500 + 150 should be 2650");
    assert(applyPrice(200, "amount", -500) === 0.0, "200 - 500 should floor at 0");

    // Bad input does not produce invalid numbers
    assert(applyPrice("nope", "percent", 10) === 0.0, "Invalid price string should return 0");
    assert(applyPrice(500, "percent", "nope") === 0.0, "Invalid amount string should return 0");

    echo "✔ Pricing tests passed!\n";
}
