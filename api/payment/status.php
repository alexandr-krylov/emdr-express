<?php
/**
 * Проверка статуса платежа
 * 
 * GET /api/payment/status.php?inv_id=123456
 * 
 * Параметры:
 * - inv_id (int) - номер заказа
 */

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

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../PaymentGatewayFactory.php';

$config = require __DIR__ . '/../config.php';

// Получаем номер заказа
$invId = (int) ($_GET['inv_id'] ?? 0);

if ($invId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Неверный номер заказа']);
    exit;
}

$gateway = PaymentGatewayFactory::create($config);

// Получаем статус платежа
$status = $gateway->getPaymentStatus($invId);

echo json_encode($status, JSON_UNESCAPED_UNICODE);
