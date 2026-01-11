<?php
/**
 * Создание платежа и получение URL для оплаты
 * 
 * POST /api/payment/create.php
 * 
 * Параметры:
 * - amount (float) - сумма платежа (опционально, по умолчанию из конфига)
 * - email (string) - email покупателя (опционально)
 * - description (string) - описание платежа (опционально)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Обработка preflight запроса
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Robokassa.php';

$config = require __DIR__ . '/../config.php';

// Получаем данные из запроса
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$amount = (float) ($input['amount'] ?? $config['default_amount']);
$email = $input['email'] ?? null;
$description = $input['description'] ?? $config['default_description'];

// Валидация суммы
if ($amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Неверная сумма платежа']);
    exit;
}

// Генерируем уникальный номер заказа
// В реальном проекте это должен быть ID из базы данных
$invId = time() . rand(100, 999);

// Создаем экземпляр Robokassa
$robokassa = new Robokassa($config);

// Формируем данные для чека (54-ФЗ)
$receipt = $robokassa->createReceipt($description, $amount);

// Получаем URL для оплаты
$paymentUrl = $robokassa->getPaymentUrl(
    $amount,
    $invId,
    $description,
    $email,
    $receipt
);

// Здесь можно сохранить заказ в базу данных
// saveOrder($invId, $amount, $email, $description);

// Возвращаем URL для редиректа
echo json_encode([
    'success' => true,
    'payment_url' => $paymentUrl,
    'inv_id' => $invId,
    'amount' => $amount
]);
