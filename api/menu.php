<?php
// api/menu.php - Live Menu API endpoint
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();
    $settings = get_restaurant_settings($db);
    $categories = $db->query("SELECT id, name, display_order FROM categories ORDER BY display_order, name")->fetchAll();
    $rawItems = $db->query("SELECT * FROM menu_items ORDER BY name")->fetchAll();

    $items = array_map(function($row) {
        return [
            'id' => (int)$row['id'],
            'category_id' => (int)$row['category_id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'price' => (float)$row['price'],
            'image_url' => $row['image_url'] ?? '',
            'is_available' => !empty($row['is_available']),
            'tags' => !empty($row['tags']) ? array_values(array_filter(array_map('trim', explode(',', $row['tags'])))) : [],
            'prep_time' => (int)($row['prep_time'] ?? 10),
        ];
    }, $rawItems);

    echo json_encode([
        'ok' => true,
        'settings' => $settings,
        'categories' => $categories,
        'items' => $items,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Could not load menu.']);
}
