<?php
// tests/OrderBuildTest.php - Tests for order building & visibility
require_once __DIR__ . '/../includes/order_build.php';

function testOrderBuild(): void {
    echo "Running OrderBuild tests...\n";

    $menu = [
        ['id' => 1, 'name' => 'Jollof', 'price' => 3200, 'is_available' => 1, 'image_url' => ''],
        ['id' => 2, 'name' => 'Zobo', 'price' => 700, 'is_available' => 0, 'image_url' => ''],
    ];

    // Uses the menu price, not the price sent by the browser
    $built = buildOrderLines([['id' => 1, 'price' => 1, 'qty' => 2]], $menu);
    assert($built['lines'][0]['price'] === 3200.0, "Should use server price 3200");
    assert($built['total'] === 6400.0, "Total should be 6400");

    // Rejects a sold-out dish
    $rejectedSoldOut = false;
    try {
        buildOrderLines([['id' => 2, 'qty' => 1]], $menu);
    } catch (InvalidArgumentException $e) {
        if (strpos($e->getMessage(), 'sold out') !== false) {
            $rejectedSoldOut = true;
        }
    }
    assert($rejectedSoldOut, "Should reject sold out dish");

    // Rejects an unknown dish
    $rejectedUnknown = false;
    try {
        buildOrderLines([['id' => 9, 'qty' => 1]], $menu);
    } catch (InvalidArgumentException $e) {
        if (strpos($e->getMessage(), 'not on the menu') !== false) {
            $rejectedUnknown = true;
        }
    }
    assert($rejectedUnknown, "Should reject unknown dish");

    // Rejects a blank plate
    $rejectedBlank = false;
    try {
        buildOrderLines([], $menu);
    } catch (InvalidArgumentException $e) {
        if (strpos($e->getMessage(), 'empty') !== false) {
            $rejectedBlank = true;
        }
    }
    assert($rejectedBlank, "Should reject empty plate");

    // Rejects a quantity outside 1 to 20
    $rejectedQty = false;
    try {
        buildOrderLines([['id' => 1, 'qty' => 21]], $menu);
    } catch (InvalidArgumentException $e) {
        if (strpos($e->getMessage(), '1 to 20') !== false) {
            $rejectedQty = true;
        }
    }
    assert($rejectedQty, "Should reject quantity > 20");

    // Does not show blank-email orders to a stranger
    $orders = [
        ['id' => 1, 'guest_email' => '', 'view_token' => 'ticket-a'],
        ['id' => 2, 'guest_email' => 'ada@example.com', 'view_token' => 'ticket-b'],
    ];

    $vStranger = visibleOrders($orders, null, '');
    assert(count($vStranger) === 0, "Stranger should see 0 orders");

    $vTicketA = visibleOrders($orders, null, 'ticket-a');
    assert(count($vTicketA) === 1 && $vTicketA[0]['id'] === 1, "Ticket token should match order 1");

    $vAda = visibleOrders($orders, ['role' => 'guest', 'email' => 'ada@example.com'], '');
    assert(count($vAda) === 1 && $vAda[0]['id'] === 2, "Ada should match order 2");

    $vStaff = visibleOrders($orders, ['role' => 'staff'], '');
    assert(count($vStaff) === 2, "Staff should see all orders");

    // Adds quantities when the same dish is sent twice
    $builtMerged = buildOrderLines([
        ['id' => 1, 'qty' => 2, 'price' => 1],
        ['id' => 1, 'qty' => 3, 'price' => 9]
    ], $menu);
    assert(count($builtMerged['lines']) === 1, "Lines should be merged into 1");
    assert($builtMerged['lines'][0]['qty'] === 5, "Quantity should sum to 5");
    assert($builtMerged['total'] === 16000.0, "Total should be 16000");

    echo "✔ OrderBuild tests passed!\n";
}
