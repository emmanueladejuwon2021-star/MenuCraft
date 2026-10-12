<?php
// includes/order_build.php - Order validation and visibility helpers

const MAX_QTY = 20;
const MAX_LINES = 30;

/**
 * Validates requested cart items against trusted menu rows and computes line totals.
 *
 * @param array $requested Array of item rows with ['id' => ..., 'qty' => ...]
 * @param array $menuRows Database rows of menu items
 * @return array ['lines' => array, 'total' => float]
 * @throws InvalidArgumentException
 */
function buildOrderLines(array $requested, array $menuRows): array {
    if (empty($requested)) {
        throw new InvalidArgumentException("Your plate is empty.");
    }

    if (count($requested) > MAX_LINES) {
        throw new InvalidArgumentException("That plate has too many dishes.");
    }

    // Build map of menu rows by ID
    $byId = [];
    foreach ($menuRows as $row) {
        $byId[(int)$row['id']] = $row;
    }

    // Merge requested dishes by ID
    $merged = [];
    foreach ($requested as $req) {
        if (!isset($req['id']) || !is_numeric($req['id'])) {
            throw new InvalidArgumentException("A dish on this plate is not on the menu.");
        }
        $id = (int)$req['id'];
        $qty = isset($req['qty']) && is_numeric($req['qty']) ? (int)$req['qty'] : 0;
        $merged[$id] = ($merged[$id] ?? 0) + $qty;
    }

    $lines = [];
    foreach ($merged as $id => $qty) {
        if (!isset($byId[$id])) {
            throw new InvalidArgumentException("A dish on this plate is not on the menu.");
        }

        $item = $byId[$id];
        $isAvailable = !empty($item['is_available']);

        if (!$isAvailable) {
            throw new InvalidArgumentException(($item['name'] ?? 'Dish') . " is sold out.");
        }

        if ($qty < 1 || $qty > MAX_QTY) {
            throw new InvalidArgumentException("Each dish can be ordered from 1 to 20.");
        }

        $price = (float)$item['price'];
        if ($price < 0) {
            throw new InvalidArgumentException("A dish price on the menu is not valid.");
        }

        $lines[] = [
            'id' => (int)$item['id'],
            'name' => (string)($item['name'] ?? ''),
            'price' => $price,
            'qty' => $qty,
            'image_url' => (string)($item['image_url'] ?? ''),
        ];
    }

    $total = 0.0;
    foreach ($lines as $line) {
        $total += $line['price'] * $line['qty'];
    }
    $total = round($total, 2);

    return [
        'lines' => $lines,
        'total' => $total,
    ];
}

/**
 * Filters order list by user authorization or ticket tokens.
 *
 * @param array $orders Array of order records
 * @param array|null $user Current authenticated user session (if any)
 * @param string|array $tickets Comma-separated or array of view tokens
 * @return array
 */
function visibleOrders(array $orders, ?array $user, $tickets = ''): array {
    if (!empty($user['role']) && $user['role'] === 'staff') {
        return array_values($orders);
    }

    $email = strtolower(trim($user['email'] ?? ''));

    $allowedTokens = [];
    if (is_array($tickets)) {
        $allowedTokens = array_map('trim', $tickets);
    } elseif (is_string($tickets) && $tickets !== '') {
        foreach (explode(',', $tickets) as $t) {
            $t = trim($t);
            if ($t !== '' && strlen($t) <= 80) {
                $allowedTokens[] = $t;
            }
        }
    }
    $allowedMap = array_flip($allowedTokens);

    $filtered = [];
    foreach ($orders as $order) {
        $viewToken = (string)($order['view_token'] ?? '');
        if ($viewToken !== '' && isset($allowedMap[$viewToken])) {
            $filtered[] = $order;
            continue;
        }

        $guestEmail = strtolower(trim($order['guest_email'] ?? ''));
        if ($email !== '' && $guestEmail === $email) {
            $filtered[] = $order;
        }
    }

    return array_values($filtered);
}
