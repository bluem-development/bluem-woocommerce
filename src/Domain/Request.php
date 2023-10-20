<?php

namespace Bluem\BluemPHP\Domain;

class Request
{
    public string $entrance_code;
    public string $transaction_id;
    public string $transaction_url;
    public string $description;
    public string $debtor_reference;
    private ?string $user_id;
    private string $timestamp;
    private ?string $order_id;
    private array $payload;

    public function __construct(
        $entrance_code,
        $transaction_id,
        $transaction_url,
        $description,
        $debtor_reference,
        $environment,
        $user_id
    ) {
        $this->debtor_reference = $debtor_reference;
        $this->description = $description;
        $this->transaction_url = $transaction_url;
        $this->transaction_id = $transaction_id;
        $this->entrance_code = $entrance_code;
        $this->user_id = $user_id;
        $this->timestamp = date("Y-m-d H:i:s") ?: '';
        $this->type = "identity";
        $this->payload = ['environment' => $environment];
    }
}
