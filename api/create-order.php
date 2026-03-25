<?php
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$invoiceId = 'order_' . time();

$stmt = $db->prepare("
    INSERT INTO orders (invoice_id, email, amount)
    VALUES (?, ?, ?)
");

$stmt->execute([
    $invoiceId,
    $data['email'],
    $data['amount']
]);

echo json_encode([
    'invoiceId' => $invoiceId
]);
