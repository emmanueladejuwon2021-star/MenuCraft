<?php
// includes/pricing.php - Price calculation helper for MenuCraft

/**
 * Calculates a new price based on either a fixed amount delta or a percentage.
 *
 * @param mixed $price Current price
 * @param string $mode 'percent' or 'amount'
 * @param mixed $amount Delta amount (e.g. 10 for +10% or +10.00, -10 for -10% or -10.00)
 * @return float Calculated new price (floored at 0)
 */
function applyPrice($price, string $mode, $amount): float {
    if (!is_numeric($price) || !is_numeric($amount)) {
        return 0.0;
    }

    $current = (float)$price;
    $delta = (float)$amount;

    if (!is_finite($current) || !is_finite($delta)) {
        return 0.0;
    }

    if ($mode === 'amount') {
        $next = $current + $delta;
    } else {
        $next = $current * (1 + $delta / 100);
    }

    return (float)max(0.0, round($next, 2));
}
