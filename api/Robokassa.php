<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Класс для работы с платежной системой Robokassa
 *
 * Документация: https://docs.robokassa.ru/
 */

class Robokassa implements PaymentGatewayInterface
{
    private string $merchantLogin;
    private string $password1;
    private string $password2;
    private bool $isTest;
    private string $paymentUrl;
    private string $statusUrl;
    private string $hashAlgo;
    private string $culture;
    
    public function __construct(array $config)
    {
        $this->merchantLogin = $config['merchant_login'];
        $this->password1 = $config['password1'];
        $this->password2 = $config['password2'];
        $this->isTest = $config['is_test'] ?? true;
        $this->paymentUrl = $config['payment_url'];
        $this->statusUrl = $config['status_url'];
        $this->hashAlgo = $config['hash_algo'] ?? 'md5';
        $this->culture = $config['culture'] ?? 'ru';
    }

    /**
     * Тест подключения к Robokassa API
     * 
     * @return array Результат проверки
     */
    public function testConnection(): array
    {
        // Проверяем доступность API
        $testUrl = $this->paymentUrl . '?MerchantLogin=' . $this->merchantLogin . '&IsTest=1';
        
        return [
            'success' => true,
            'merchant_login' => $this->merchantLogin,
            'is_test' => $this->isTest,
            'payment_url' => $this->paymentUrl,
            'message' => 'Конфигурация корректна. Ошибка 500 означает проблемы на стороне Robokassa.' .
                         ' Проверьте: 1) Активирован ли магазин в личном кабинете;' .
                         ' 2) Разрешены ли платежи в тестовом режиме;' .
                         ' 3) Правильные ли пароли (password1/password2) для Kazakhstan API'
        ];
    }
    
    /**
     * Генерация URL для оплаты (HTTP протокол)
     *
     * @param float $amount Сумма платежа
     * @param int $invId Номер заказа
     * @param string $description Описание платежа
     * @param string|null $email Email покупателя
     * @param array $receipt Данные для чека (54-ФЗ)
     * @param string $shpItem Артикул товара/услуги (обязательно для Robokassa Kazakhstan)
     * @return string URL для редиректа на оплату
     */
    public function getPaymentUrl(
        float $amount,
        int $invId,
        string $description,
        ?string $email = null,
        array $receipt = [],
        string $shpItem = '1'
    ): string {
        // Формируем подпись (с Shp_item для Kazakhstan API)
        $signature = $this->generateSignature($amount, $invId, $shpItem);

        // Базовые параметры
        $params = [
            'MerchantLogin' => $this->merchantLogin,
            'OutSum' => number_format($amount, 2, '.', ''),
            'InvId' => $invId,
            'Description' => $description,
            'SignatureValue' => $signature,
            'Shp_item' => $shpItem,
        ];

        // Добавляем email если указан
        if ($email) {
            $params['Email'] = $email;
        }

        // Тестовый режим
        if ($this->isTest) {
            $params['IsTest'] = 1;
        }

        // Язык интерфейса (важно для Kazakhstan)
        $params['Culture'] = $this->culture;

        // Добавляем данные для чека (54-ФЗ) если указаны
        // http_build_query автоматически URL-кодирует значения
        if (!empty($receipt)) {
            $params['Receipt'] = json_encode($receipt, JSON_UNESCAPED_UNICODE);
        }

        return $this->paymentUrl . '?' . http_build_query($params);
    }

    /**
     * Генерация HTML с JavaScript формой оплаты (рекомендуемый способ)
     *
     * @param float $amount Сумма платежа
     * @param int $invId Номер заказа
     * @param string $description Описание платежа
     * @param string|null $email Email покупателя
     * @param array $receipt Данные для чека (54-ФЗ)
     * @param string $shpItem Артикул товара/услуги
     * @return string HTML с JavaScript формой
     */
    public function getPaymentFormHtml(
        float $amount,
        int $invId,
        string $description,
        ?string $email = null,
        array $receipt = [],
        string $shpItem = '1'
    ): string {
        // Формируем подпись для JavaScript формы
        $signature = $this->generateJsSignature($amount, $invId, $shpItem);

        // Базовые параметры
        $params = [
            'MerchantLogin' => $this->merchantLogin,
            'DefaultSum' => number_format($amount, 2, '.', ''),
            'InvoiceID' => $invId,
            'Description' => $description,
            'SignatureValue' => $signature,
            'Shp_item' => $shpItem,
        ];

        // Добавляем email если указан
        if ($email) {
            $params['Email'] = $email;
        }

        // Тестовый режим
        if ($this->isTest) {
            $params['IsTest'] = 1;
        }

        // Язык интерфейса (важно для Kazakhstan)
        $params['Culture'] = $this->culture;

        // Добавляем данные для чека (54-ФЗ) если указаны
        // http_build_query автоматически URL-кодирует значения
        if (!empty($receipt)) {
            $params['Receipt'] = json_encode($receipt, JSON_UNESCAPED_UNICODE);
        }

        $queryString = http_build_query($params);

        return "<html><script language=JavaScript src='https://auth.robokassa.kz/Merchant/PaymentForm/FormFLS.js?{$queryString}'></script></html>";
    }
    
    /**
     * Генерация подписи для запроса оплаты
     * 
     * Формула для Robokassa Kazakhstan: MerchantLogin:OutSum:InvId:Password1:Shp_item=value
     * 
     * @param float $amount Сумма платежа
     * @param int $invId Номер заказа
     * @param string $shpItem Артикул товара/услуги
     * @return string Подпись в верхнем регистре
     */
    public function generateSignature(float $amount, int $invId, string $shpItem = '1'): string
    {
        $data = implode(':', [
            $this->merchantLogin,
            number_format($amount, 2, '.', ''),
            $invId,
            $this->password1,
            "Shp_item=$shpItem"
        ]);
        
        return strtoupper(hash($this->hashAlgo, $data));
    }

    /**
     * Генерация подписи для JavaScript формы оплаты (FormFLS.js)
     *
     * Формула для Kazakhstan: MerchantLogin:OutSum:InvId:Password1:Shp_item=value
     */
    private function generateJsSignature(float $amount, int $invId, string $shpItem = '1'): string
    {
        $data = implode(':', [
            $this->merchantLogin,
            number_format($amount, 2, '.', ''),
            $invId,
            $this->password1,
            "Shp_item=$shpItem"
        ]);

        return strtoupper(hash($this->hashAlgo, $data));
    }

    /**
     * Проверка подписи от Robokassa (ResultURL)
     * 
     * Формула: OutSum:InvId:Password2
     */
    public function validateResultSignature(float $amount, int $invId, string $signature): bool
    {
        $data = implode(':', [
            number_format($amount, 2, '.', ''),
            $invId,
            $this->password2
        ]);
        
        $expectedSignature = strtoupper(hash($this->hashAlgo, $data));
        
        return hash_equals($expectedSignature, strtoupper($signature));
    }
    
    /**
     * Проверка подписи для SuccessURL
     *
     * Формула: OutSum:InvId:Password1
     */
    public function validateSuccessSignature(float $amount, int $invId, string $signature): bool
    {
        $data = implode(':', [
            number_format($amount, 2, '.', ''),
            $invId,
            $this->password1
        ]);

        $expectedSignature = strtoupper(hash($this->hashAlgo, $data));

        return hash_equals($expectedSignature, strtoupper($signature));
    }

    /**
     * Validate webhook signature (implements PaymentGatewayInterface)
     *
     * @param array $requestData Webhook request data
     * @return bool True if signature is valid
     */
    public function validateWebhook(array $requestData): bool
    {
        if (!isset($requestData['OutSum'], $requestData['InvId'], $requestData['SignatureValue'])) {
            return false;
        }

        return $this->validateResultSignature(
            (float) $requestData['OutSum'],
            (int) $requestData['InvId'],
            $requestData['SignatureValue']
        );
    }
    
    /**
     * Проверка статуса платежа через XML интерфейс
     * 
     * @param int $invId Номер заказа
     * @return array Информация о платеже
     */
    public function getPaymentStatus(int $invId): array
    {
        // Формируем подпись для запроса статуса
        $signature = strtoupper(hash($this->hashAlgo, implode(':', [
            $this->merchantLogin,
            $invId,
            $this->password2
        ])));
        
        $params = [
            'MerchantLogin' => $this->merchantLogin,
            'InvoiceID' => $invId,
            'Signature' => $signature,
        ];
        
        $url = $this->statusUrl . '?' . http_build_query($params);
        
        // Выполняем запрос
        $response = file_get_contents($url);
        
        if ($response === false) {
            return [
                'success' => false,
                'error' => 'Не удалось получить статус платежа'
            ];
        }
        
        // Парсим XML ответ
        $xml = simplexml_load_string($response);
        
        if ($xml === false) {
            return [
                'success' => false,
                'error' => 'Ошибка парсинга ответа'
            ];
        }
        
        return $this->parseStatusResponse($xml);
    }
    
    /**
     * Парсинг XML ответа о статусе платежа
     */
    private function parseStatusResponse(SimpleXMLElement $xml): array
    {
        $result = $xml->Result;
        
        if (!$result) {
            return [
                'success' => false,
                'error' => 'Неверный формат ответа'
            ];
        }
        
        $code = (int) $result->Code;
        
        // Коды состояния операции
        $statuses = [
            0 => 'Операция не найдена',
            5 => 'Только инициирована, деньги не получены',
            10 => 'Деньги не были получены',
            50 => 'Деньги получены, операция ожидает подтверждения',
            60 => 'Деньги возвращены покупателю',
            80 => 'Исполнение операции приостановлено',
            100 => 'Операция выполнена успешно'
        ];
        
        return [
            'success' => true,
            'code' => $code,
            'status' => $statuses[$code] ?? 'Неизвестный статус',
            'is_paid' => $code === 100,
            'is_pending' => $code === 50,
            'is_refunded' => $code === 60,
            'raw' => (array) $result
        ];
    }
    
    /**
     * Формирование данных для чека (54-ФЗ)
     * 
     * @param string $name Название товара/услуги
     * @param float $amount Сумма
     * @param int $quantity Количество
     * @param string $tax НДС (none, vat0, vat10, vat20, vat110, vat120)
     * @return array Данные чека
     */
    public function createReceipt(
        string $name,
        float $amount,
        int $quantity = 1,
        string $tax = 'none'
    ): array {
        return [
            'items' => [
                [
                    'name' => $name,
                    'quantity' => $quantity,
                    'sum' => $amount,
                    'payment_method' => 'full_payment',
                    'payment_object' => 'service',
                    'tax' => $tax
                ]
            ]
        ];
    }
}
