<?php
// api/health.php - Health check endpoint
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDb();
    echo json_encode(['ok' => true, 'connected' => ($db !== null)]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'connected' => false, 'error' => $e->getMessage()]);
}
