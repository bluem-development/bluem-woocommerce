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
$isPaymentStatusRequest = $transactionType === 'PSX';
$isMandateRequest = $transactionType === 'TRX';
$isMandateStatusRequest = $transactionType === 'SRX';
$isIdentityRequest = $transactionType === 'ITX';
$isIdentityStatusRequest = $transactionType === 'ISX';
$isStatusRequest = $isPaymentStatusRequest || $isMandateStatusRequest || $isIdentityStatusRequest;
$transactionId = 'ACCEPTANCETX1';
$entranceCode = 'ACCEPTANCEENTRANCE1';
$debtorReference = 'ACCEPTANCEORDER1';
$mandateId = 'ACCEPTANCEMANDATE1';

if (($isPaymentStatusRequest || $isIdentityStatusRequest) && preg_match('/<TransactionID>([^<]+)<\/TransactionID>/', $requestBody, $matches) === 1) {
    $transactionId = (string) $matches[1];
}

if (($isMandateRequest || $isMandateStatusRequest) && preg_match('/<MandateID>([^<]+)<\/MandateID>/', $requestBody, $matches) === 1) {
    $mandateId = (string) $matches[1];
}

if ($isMandateRequest) {
    $transactionId = 'ACCEPTANCEMANDATETX1';
    $entranceCode = 'ACCEPTANCEMANDATEENTRANCE1';
}

if ($isIdentityRequest) {
    $transactionId = 'ACCEPTANCEIDINTX1';
    $entranceCode = 'ACCEPTANCEIDINENTRANCE1';
}

header('Content-Type: application/xml; charset=UTF-8');

if ($isPaymentStatusRequest) {
    $status = $transactionId === 'ACCEPTANCEPENDING1' ? 'Pending' : 'Success';
    echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<EPaymentInterface createDateTime="2026-08-03T00:00:00Z" messageCount="1" mode="direct" senderID="acceptance" type="StatusUpdate" version="1.0">
    <PaymentStatusUpdate entranceCode="{$entranceCode}">
        <CreationDateTime>2026-08-03T00:00:00Z</CreationDateTime>
        <PaymentReference>ACCEPTANCE-PAYMENT-1</PaymentReference>
        <DebtorReference>{$debtorReference}</DebtorReference>
        <TransactionID>{$transactionId}</TransactionID>
        <Status>{$status}</Status>
        <Amount>12.34</Amount>
        <AmountPaid>12.34</AmountPaid>
        <Currency>EUR</Currency>
        <PaymentMethod>IDEAL</PaymentMethod>
    </PaymentStatusUpdate>
</EPaymentInterface>
XML;
    exit;
}

if ($isMandateStatusRequest) {
    echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<EMandateInterface createDateTime="2026-08-03T00:00:00Z" messageCount="1" mode="direct" senderID="acceptance" type="StatusUpdate" version="1.0">
    <EMandateStatusUpdate entranceCode="ACCEPTANCEMANDATEENTRANCE1">
        <EMandateStatus>
            <Status>Success</Status>
            <MandateID>{$mandateId}</MandateID>
            <PurchaseID>ACCEPTANCEPURCHASE1</PurchaseID>
            <AcceptanceReport>
                <DebtorIBAN>NL66ABNA4097012428</DebtorIBAN>
                <DebtorBankID>ABNANL2A</DebtorBankID>
                <DebtorAccountName>Bluem Acceptance</DebtorAccountName>
                <MaxAmount>250.00</MaxAmount>
            </AcceptanceReport>
        </EMandateStatus>
    </EMandateStatusUpdate>
</EMandateInterface>
XML;
    exit;
}

if ($isIdentityStatusRequest) {
    echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<IdentityInterface createDateTime="2026-08-03T00:00:00Z" messageCount="1" mode="direct" senderID="acceptance" type="StatusUpdate" version="1.0">
    <IdentityStatusUpdate entranceCode="ACCEPTANCEIDINENTRANCE1">
        <AuthenticationAuthorityID>ACCEPTANCEAUTHORITY1</AuthenticationAuthorityID>
        <Status>Success</Status>
        <IdentityReport>
            <ReportStatus>Verified</ReportStatus>
            <CustomerName>Bluem Acceptance</CustomerName>
            <AgeCheckResponse>Passed</AgeCheckResponse>
        </IdentityReport>
    </IdentityStatusUpdate>
</IdentityInterface>
XML;
    exit;
}

if ($isMandateRequest) {
    echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<EMandateInterface createDateTime="2026-08-03T00:00:00Z" messageCount="1" mode="direct" senderID="acceptance" type="TransactionRequest" version="1.0">
    <EMandateTransactionResponse entranceCode="{$entranceCode}">
        <TransactionURL>https://mock-bluem.invalid/mandate/transaction/{$transactionId}</TransactionURL>
        <TransactionID>{$transactionId}</TransactionID>
        <MandateID>{$mandateId}</MandateID>
    </EMandateTransactionResponse>
</EMandateInterface>
XML;
    exit;
}

if ($isIdentityRequest) {
    echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<IdentityInterface createDateTime="2026-08-03T00:00:00Z" messageCount="1" mode="direct" senderID="acceptance" type="TransactionRequest" version="1.0">
    <IdentityTransactionResponse entranceCode="{$entranceCode}">
        <TransactionURL>https://mock-bluem.invalid/identity/transaction/{$transactionId}</TransactionURL>
        <TransactionID>{$transactionId}</TransactionID>
        <DebtorReference>ACCEPTANCEIDENTITY1</DebtorReference>
    </IdentityTransactionResponse>
</IdentityInterface>
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
