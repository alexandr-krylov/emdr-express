<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

/**
 * Класс для работы с платежной системой PrimePay (SmartCore API)
 *
 * Документация: https://docs.smartcore.pro/#smartcore-api
 */

class PrimePay implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $secretKey;
    private string $projectId;
    private bool $isTest;
    private string $apiUrl;
    private string $createPaymentUrl;
    private string $statusUrl;
    private string $resultUrl;
    private string $successUrl;
    private string $failUrl;
    
    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->secretKey = $config['secret_key'];
        $this->projectId = $config['project_id'];
        $this->isTest = $config['is_test'] ?? true;
        $this->apiUrl = $config['api_url'];
        $this->createPaymentUrl = $config['create_payment_url'];
        $this->statusUrl = $config['status_url'];
        $this->resultUrl = $config['result_url'] ?? '';
        $this->successUrl = $config['success_url'] ?? '';
        $this->failUrl = $config['fail_url'] ?? '';
    }
    
    /**
     * Генерация URL для оплаты
     * 
     * @param float $amount Сумма платежа
     * @param int $invId Номер заказа
     * @param string $description Описание платежа
     * @param string|null $email Email покупателя
     * @param array $receipt Данные для чека (если поддерживается)
     * @return string URL для редиректа на оплату
     */
    public function getPaymentUrl(
        float $amount,
        int $invId,
        string $description,
        ?string $email = null,
        array $receipt = []
    ): string {
        $data = [
            'project_id' => $this->projectId,
            'amount' => $amount * 100, // Предполагаем, что сумма в копейках/тиынах
            'currency' => 'KZT',
            'description' => $description,
            'order_id' => $invId,
            'result_url' => $this->resultUrl,
            'success_url' => $this->successUrl,
            'fail_url' => $this->failUrl,
            'test' => $this->isTest ? 1 : 0,
        ];
        
        if ($email) {
            $data['email'] = $email;
        }
        
        // Делаем API запрос
        $response = $this->makeApiRequest('POST', $this->createPaymentUrl, $data);
        
        if (!$response || !isset($response['url'])) {
            throw new Exception('Не удалось создать платеж: ' . json_encode($response));
        }
        
        return $response['url'];
    }
    
    /**
     * Проверка подписи от PrimePay (webhook)
     *
     * @param array $data Данные из webhook
     * @param string $signature Подпись
     * @return bool
     */
    public function validateResultSignature(array $data, string $signature): bool
    {
        // Убираем signature из данных для проверки
        unset($data['signature']);

        // Сортируем ключи
        ksort($data);

        // Создаем строку для подписи
        $string = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $string .= $key . '=' . $value . '&';
        }
        $string = rtrim($string, '&');

        // Вычисляем HMAC-SHA256
        $expectedSignature = hash_hmac('sha256', $string, $this->secretKey);

        return hash_equals(strtolower($expectedSignature), strtolower($signature));
    }

    /**
     * Validate webhook signature (implements PaymentGatewayInterface)
     *
     * @param array $requestData Webhook request data
     * @return bool True if signature is valid
     */
    public function validateWebhook(array $requestData): bool
    {
        if (!isset($requestData['signature'])) {
            return false;
        }

        $signature = $requestData['signature'];
        $data = $requestData;
        unset($data['signature']);

        return $this->validateResultSignature($data, $signature);
    }
    
    /**
     * Проверка статуса платежа
     * 
     * @param int $invId Номер заказа
     * @return array Информация о платеже
     */
    public function getPaymentStatus(int $invId): array
    {
        // Предполагаем, что invId - это order_id, но для статуса нужен payment_id
        // В реальности, может понадобиться хранить mapping
        // Для простоты, предполагаем invId = payment_id
        $url = str_replace('{id}', $invId, $this->statusUrl);
        
        $response = $this->makeApiRequest('GET', $url);
        
        if (!$response) {
            return [
                'success' => false,
                'error' => 'Не удалось получить статус платежа'
            ];
        }
        
        return $this->parseStatusResponse($response);
    }
    
    /**
     * Парсинг ответа о статусе платежа
     */
    private function parseStatusResponse(array $response): array
    {
        $status = $response['status'] ?? 'unknown';
        
        // Маппинг статусов SmartCore на общий формат
        $statusMapping = [
            'pending' => ['code' => 50, 'is_paid' => false, 'is_pending' => true, 'is_refunded' => false],
            'paid' => ['code' => 100, 'is_paid' => true, 'is_pending' => false, 'is_refunded' => false],
            'failed' => ['code' => 10, 'is_paid' => false, 'is_pending' => false, 'is_refunded' => false],
            'cancelled' => ['code' => 60, 'is_paid' => false, 'is_pending' => false, 'is_refunded' => false],
            'refunded' => ['code' => 60, 'is_paid' => false, 'is_pending' => false, 'is_refunded' => true],
        ];
        
        $mapped = $statusMapping[$status] ?? ['code' => 0, 'is_paid' => false, 'is_pending' => false, 'is_refunded' => false];
        
        return [
            'success' => true,
            'code' => $mapped['code'],
            'status' => $status,
            'is_paid' => $mapped['is_paid'],
            'is_pending' => $mapped['is_pending'],
            'is_refunded' => $mapped['is_refunded'],
            'raw' => $response
        ];
    }
    
    /**
     * Создание чека (если поддерживается SmartCore)
     * 
     * @param string $name Название товара/услуги
     * @param float $amount Сумма
     * @param int $quantity Количество
     * @param string $tax НДС
     * @return array Данные чека
     */
    public function createReceipt(
        string $name,
        float $amount,
        int $quantity = 1,
        string $tax = 'none'
    ): array {
        // SmartCore может поддерживать чеки, но формат может отличаться
        return [
            'items' => [
                [
                    'name' => $name,
                    'quantity' => $quantity,
                    'amount' => $amount,
                    'tax' => $tax
                ]
            ]
        ];
    }
    
    /**
     * Выполнение API запроса
     */
    private function makeApiRequest(string $method, string $url, array $data = []): ?array
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode < 200 || $httpCode >= 300) {
            return null;
        }
        
        return json_decode($response, true);
    }
}