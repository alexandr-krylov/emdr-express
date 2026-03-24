<?php
// test DB connection sqlite
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Только GET запросы
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
var_dump(realpath(__DIR__ . '/../db'));
$db = new PDO('sqlite:' . __DIR__ . '/../db/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, name TEXT)");
$stmt = $db->prepare("INSERT INTO test (name) VALUES (:name)");
$stmt->execute([':name' => 'Test DB Connection']);
echo json_encode(['success' => true]);
exit;