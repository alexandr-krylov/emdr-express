<?php
/**
 * Конфигурация Robokassa
 * 
 * Получите эти данные в личном кабинете Robokassa:
 * https://partner.robokassa.ru/
 */

return [
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
    
    // Описание платежа по умолчанию
    'default_description' => 'Интенсив EMDR Express',
    
    // Сумма по умолчанию
    'default_amount' => 42000,
    
    // Валюта
    'currency' => 'KZT',
    
    // URL для редиректов (замените на ваш домен)
    'result_url' => 'https://yourdomain.com/api/payment/result.php',
    'success_url' => 'https://yourdomain.com/payment-success.html',
    'fail_url' => 'https://yourdomain.com/payment-fail.html',
];
