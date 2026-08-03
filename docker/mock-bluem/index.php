<?php

declare(strict_types=1);

$contentType = $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
$requestBody = file_get_contents('php://input') ?: '';

if (!str_contains($contentType, 'type=')) {
    http_response_code(400);
    echo '<Error>Missing Bluem transaction type</Error>';
    exit;
}

$transactionType = strtoupper((string) preg_replace('/.*type=([^; ]+).*/', '$1', $contentType));
$isStatusRequest = $transactionType === 'PSX';
$transactionId = 'ACCEPTANCETX1';
$entranceCode = 'ACCEPTANCEENTRANCE1';
$debtorReference = 'ACCEPTANCEORDER1';

header('Content-Type: application/xml; charset=UTF-8');

if ($isStatusRequest) {
    echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<EPaymentInterface createDateTime="2026-08-03T00:00:00Z" messageCount="1" mode="direct" senderID="acceptance" type="StatusUpdate" version="1.0">
    <PaymentStatusUpdate entranceCode="{$entranceCode}">
        <CreationDateTime>2026-08-03T00:00:00Z</CreationDateTime>
        <PaymentReference>ACCEPTANCE-PAYMENT-1</PaymentReference>
        <DebtorReference>{$debtorReference}</DebtorReference>
        <TransactionID>{$transactionId}</TransactionID>
        <Status>Success</Status>
        <Amount>12.34</Amount>
        <AmountPaid>12.34</AmountPaid>
        <Currency>EUR</Currency>
        <PaymentMethod>IDEAL</PaymentMethod>
    </PaymentStatusUpdate>
</EPaymentInterface>
XML;
    exit;
}

echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<EPaymentInterface createDateTime="2026-08-03T00:00:00Z" messageCount="1" mode="direct" senderID="acceptance" type="TransactionRequest" version="1.0">
    <PaymentTransactionResponse entranceCode="{$entranceCode}">
        <TransactionURL>https://mock-bluem.invalid/payment/transaction/{$transactionId}</TransactionURL>
        <TransactionID>{$transactionId}</TransactionID>
        <DebtorReference>{$debtorReference}</DebtorReference>
        <PaymentReference>ACCEPTANCE-PAYMENT-1</PaymentReference>
    </PaymentTransactionResponse>
</EPaymentInterface>
XML;
