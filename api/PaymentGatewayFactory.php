<?php
require_once __DIR__ . '/PaymentGatewayInterface.php';

class PaymentGatewayFactory
{
    public static function create(array $config): PaymentGatewayInterface
    {
        $system = $config['payment_system'];

        switch ($system) {
            case 'robokassa':
                require_once __DIR__ . '/Robokassa.php';
                return new Robokassa($config['robokassa']);
            case 'primepay':
                require_once __DIR__ . '/PrimePay.php';
                $gatewayConfig = array_merge($config['primepay'], [
                    'result_url' => $config['result_url'],
                    'success_url' => $config['success_url'],
                    'fail_url' => $config['fail_url'],
                ]);
                return new PrimePay($gatewayConfig);
            default:
                throw new Exception("Unsupported payment system: $system");
        }
    }
    
    /**
     * Detect payment system from webhook request
     */
    public static function detectSystemFromRequest(): string
    {
        if (isset($_REQUEST['OutSum']) && isset($_REQUEST['InvId']) && isset($_REQUEST['SignatureValue'])) {
            return 'robokassa';
        } elseif (isset($_REQUEST['id']) && isset($_REQUEST['status']) && isset($_REQUEST['signature'])) {
            return 'primepay';
        } else {
            // Try JSON body for PrimePay
            $data = json_decode(file_get_contents('php://input'), true);
            if ($data && isset($data['id']) && isset($data['status']) && isset($data['signature'])) {
                return 'primepay';
            }
            throw new Exception('Unable to detect payment system from request');
        }
    }
    
    /**
     * Create gateway for webhook processing
      */
    public static function createFromRequest(array $config): PaymentGatewayInterface
    {
        $system = self::detectSystemFromRequest();

        // Temporarily set the system in config
        $tempConfig = $config;
        $tempConfig['payment_system'] = $system;

        return self::create($tempConfig);
    }
}