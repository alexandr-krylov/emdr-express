<?php

interface PaymentGatewayInterface
{
    /**
     * Generate payment URL
     *
     * @param float $amount Payment amount
     * @param int $invId Order ID
     * @param string $description Payment description
     * @param string|null $email Customer email
     * @param array $receipt Receipt data
     * @return string Payment URL
     */
    public function getPaymentUrl(
        float $amount,
        int $invId,
        string $description,
        ?string $email = null,
        array $receipt = []
    ): string;

    /**
     * Validate webhook signature
     *
     * @param array $requestData Webhook request data
     * @return bool True if signature is valid
     */
    public function validateWebhook(array $requestData): bool;

    /**
     * Get payment status
     *
     * @param int $invId Order ID
     * @return array Payment status information
     */
    public function getPaymentStatus(int $invId): array;

    /**
     * Create receipt data
     *
     * @param string $name Item name
     * @param float $amount Item amount
     * @param int $quantity Item quantity
     * @param string $tax Tax rate
     * @return array Receipt data
     */
    public function createReceipt(
        string $name,
        float $amount,
        int $quantity = 1,
        string $tax = 'none'
    ): array;
}