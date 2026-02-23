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
require_once __DIR__ . '/../PaymentGatewayFactory.php';

$config = require __DIR__ . '/../config.php';

// Логирование для отладки
$logFile = __DIR__ . $config['log_file'] ?? __DIR__ . '/../../logs/payment_result.log';
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

// Определяем систему платежа
$system = PaymentGatewayFactory::detectSystemFromRequest();

// Извлекаем ID заказа в зависимости от системы
if ($system === 'robokassa') {
    $invId = $_REQUEST['InvId'] ?? null;
    $amount = $_REQUEST['OutSum'] ?? null;
} elseif ($system === 'primepay') {
    $invId = $_REQUEST['id'] ?? null;
    $amount = null; // Для PrimePay сумма может быть в других полях
} else {
    http_response_code(400);
    echo 'ERROR: Unknown payment system';
    exit;
}

// Проверяем наличие ID заказа
if (!$invId) {
    http_response_code(400);
    echo 'ERROR: Missing order ID';
    exit;
}

// Создаем экземпляр платежного шлюза через фабрику
$gateway = PaymentGatewayFactory::createFromRequest($config);

// Проверяем подпись через унифицированный метод
if (!$gateway->validateWebhook($_REQUEST)) {
    http_response_code(400);
    echo 'ERROR: Invalid signature';

    // Логируем ошибку
    file_put_contents($logFile, "ERROR: Invalid signature for InvId=$invId, System=$system\n\n", FILE_APPEND);
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
    file_put_contents($logFile, "SUCCESS: Payment confirmed for InvId=$invId, System=$system\n\n", FILE_APPEND);

    // Возвращаем подтверждение в зависимости от системы
    if ($system === 'robokassa') {
        echo "OK$invId";
    } elseif ($system === 'primepay') {
        // Для PrimePay может потребоваться другой формат ответа
        echo json_encode(['status' => 'ok', 'order_id' => $invId]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
    
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n\n", FILE_APPEND);
}
