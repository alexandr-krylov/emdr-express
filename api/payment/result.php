<?php
/**
 * ResultURL - обработка уведомления от Robokassa об успешной оплате
 * 
 * Robokassa отправляет POST запрос на этот URL после успешной оплаты.
 * Этот скрипт должен вернуть "OK{InvId}" для подтверждения получения.
 * 
 * Параметры от Robokassa:
 * - OutSum - сумма платежа
 * - InvId - номер заказа
 * - SignatureValue - подпись для проверки
 * - Shp_* - дополнительные параметры (если были переданы)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Robokassa.php';

$config = require __DIR__ . '/../config.php';

// Логирование для отладки
$logFile = __DIR__ . '/../../logs/robokassa_result.log';
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logData = [
    'time' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'get' => $_GET,
    'post' => $_POST,
];
file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n", FILE_APPEND);

// Получаем параметры (могут прийти как GET, так и POST)
$outSum = $_REQUEST['OutSum'] ?? null;
$invId = $_REQUEST['InvId'] ?? null;
$signatureValue = $_REQUEST['SignatureValue'] ?? null;

// Проверяем наличие обязательных параметров
if (!$outSum || !$invId || !$signatureValue) {
    http_response_code(400);
    echo 'ERROR: Missing required parameters';
    exit;
}

$robokassa = new Robokassa($config);

// Проверяем подпись
if (!$robokassa->validateResultSignature((float) $outSum, (int) $invId, $signatureValue)) {
    http_response_code(400);
    echo 'ERROR: Invalid signature';
    
    // Логируем ошибку
    file_put_contents($logFile, "ERROR: Invalid signature for InvId=$invId\n\n", FILE_APPEND);
    exit;
}

// Подпись верна - платеж успешен!
// Здесь нужно обновить статус заказа в базе данных

try {
    // Пример обновления статуса заказа:
    // updateOrderStatus($invId, 'paid', $outSum);
    
    // Можно также отправить email уведомление:
    // sendPaymentNotification($invId, $outSum);
    
    // Логируем успешную оплату
    file_put_contents($logFile, "SUCCESS: Payment confirmed for InvId=$invId, Sum=$outSum\n\n", FILE_APPEND);
    
    // Возвращаем подтверждение для Robokassa
    // ВАЖНО: формат ответа должен быть именно таким!
    echo "OK$invId";
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
    
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);
}
