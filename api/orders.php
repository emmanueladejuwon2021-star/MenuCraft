<?php
// api/orders.php - Orders JSON API endpoint (GET / POST)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/order_build.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDb();
$currentUser = get_current_user_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: $_POST;

    $itemsRequested = $body['items'] ?? [];
    $guest = $body['guest'] ?? [];
    $note = clean_label($body['note'] ?? '', 240);

    $guestName = clean_label($guest['name'] ?? ($currentUser['name'] ?? ''), 80);
    $guestEmail = strtolower(clean_label($guest['email'] ?? ($currentUser['email'] ?? ''), 120));
    $guestPhone = clean_label($guest['phone'] ?? ($currentUser['phone'] ?? ''), 30);

    if ($guestName === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'Add your name so the kitchen can call the plate.']);
        exit;
    }

    if ($guestEmail === '') {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $guestName)) ?: 'guest';
        $guestEmail = "walkin:{$slug}-" . base_convert((string)time(), 10, 36);
    }

    try {
        $menuRows = $db->query("SELECT id, name, price, is_available, image_url FROM menu_items")->fetchAll();
        $built = buildOrderLines($itemsRequested, $menuRows);

        $payRef = 'COUNTER-' . substr((string)time(), -8);
        $viewToken = bin2hex(random_bytes(18));
        $createdAt = gmdate('Y-m-d\TH:i:s\Z');

        $stmt = $db->prepare("INSERT INTO orders (guest_name, guest_email, phone, note, items_json, total, paid, pay_ref, status, view_token, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'New', ?, ?)");
        $stmt->execute([
            $guestName,
            $guestEmail,
            $guestPhone,
            $note,
            json_encode($built['lines']),
            $built['total'],
            $payRef,
            $viewToken,
            $createdAt
        ]);

        $orderId = (int)$db->lastInsertId();
        $order = [
            'id' => $orderId,
            'guest_name' => $guestName,
            'guest_email' => $guestEmail,
            'phone' => $guestPhone,
            'note' => $note,
            'items' => $built['lines'],
            'total' => $built['total'],
            'paid' => false,
            'pay_ref' => $payRef,
            'view_token' => $viewToken,
            'status' => 'New',
            'created_at' => $createdAt,
        ];

        add_ticket($viewToken);
        http_response_code(201);
        echo json_encode(['ok' => true, 'order' => $order]);
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Could not send the order. Please try again.']);
    }
    exit;
}

// GET /api/orders
$tickets = $_GET['tickets'] ?? get_tickets();
$allOrders = $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();

$formatted = array_map(function($r) {
    return [
        'id' => (int)$r['id'],
        'guest_name' => $r['guest_name'],
        'guest_email' => $r['guest_email'],
        'phone' => $r['phone'] ?? '',
        'note' => $r['note'] ?? '',
        'items' => json_decode($r['items_json'] ?? '[]', true) ?: [],
        'total' => (float)$r['total'],
        'paid' => !empty($r['paid']),
        'pay_ref' => $r['pay_ref'] ?? '',
        'view_token' => $r['view_token'] ?? '',
        'status' => $r['status'] ?? 'New',
        'created_at' => $r['created_at'],
    ];
}, $allOrders);

$visible = visibleOrders($formatted, $currentUser, $tickets);
echo json_encode(['ok' => true, 'orders' => $visible]);
