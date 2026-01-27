<?php
/**
 * Конфигурация платежных систем
 *
 * Поддерживаемые системы: robokassa, primepay
 */

/**
 * Конфигурация Robokassa
 * 
 * Получите эти данные в личном кабинете Robokassa:
 * https://partner.robokassa.ru/
 */

return [
    
    // Активная платежная система (robokassa или primepay)
    'payment_system' => 'robokassa',

    // Общие настройки
    'default_description' => 'Интенсив EMDR Express',
    'default_amount' => 42000,
    'currency' => 'KZT',
    'result_url' => 'https://yourdomain.com/api/payment/result.php',
    'success_url' => 'https://yourdomain.com/payment-success.html',
    'fail_url' => 'https://yourdomain.com/payment-fail.html',

    // Настройки Robokassa
    'robokassa' => [
        // Идентификатор магазина
        'merchant_login' => 'your_merchant_login',

        // Пароль #1 (для формирования подписи запроса)
        'password1' => 'your_password1',

        // Пароль #2 (для проверки подписи ответа)
        'password2' => 'your_password2',

        // Тестовый режим (true - тестовый, false - боевой)
        'is_test' => true,

        // URL для оплаты
        'payment_url' => 'https://auth.robokassa.ru/Merchant/Index.aspx',

        // URL для проверки статуса (XML интерфейс)
        'status_url' => 'https://auth.robokassa.ru/Merchant/WebService/Service.asmx/OpStateExt',

        // Алгоритм хеширования (md5, sha1, sha256, sha384, sha512)
        'hash_algo' => 'md5',
    ],

    // Настройки PrimePay (SmartCore API)
    'primepay' => [
        // API ключ
        'api_key' => '', // Получите в личном кабинете SmartCore

        // Секретный ключ
        'secret_key' => '', // Получите в личном кабинете SmartCore

        // ID проекта
        'project_id' => '', // ID проекта в SmartCore

        // Тестовый режим
        'is_test' => true,

        // Базовый URL API
        'api_url' => 'https://api.smartcore.pro/v1',

        // URL для создания платежа
        'create_payment_url' => 'https://api.smartcore.pro/v1/payments',

        // URL для проверки статуса
        'status_url' => 'https://api.smartcore.pro/v1/payments/{id}/status',
    ],
];
