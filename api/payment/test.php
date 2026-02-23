<?php
/**
 * Тестирование подключения к Robokassa
 * 
 * GET /api/payment/test.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../PaymentGatewayFactory.php';

$config = require __DIR__ . '/../config.php';

try {
    $gateway = PaymentGatewayFactory::create($config);
    
    // Если это Robokassa, тестируем подключение
    if (method_exists($gateway, 'testConnection')) {
        $result = $gateway->testConnection();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Тест для данного платежного шлюза не реализован',
            'config' => [
                'payment_system' => $config['payment_system'],
                'merchant_login' => $config['robokassa']['merchant_login'] ?? null,
                'is_test' => $config['robokassa']['is_test'] ?? null,
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'hint' => 'Проверьте конфигурацию в config.php'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
