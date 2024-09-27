<?php

namespace Bluem\WooCommerce\Domain;

class Request
{
    public int $id;

    public string $entrance_code;
    public string $transaction_id;
    public string $transaction_url;
    public string $description;
    public string $debtor_reference;
    private ?string $user_id;
    private string $timestamp;
    public ?string $orderId;
    public array $payload;

    public function __construct(
        $entrance_code,
        $transaction_id,
        $transaction_url,
        $description,
        $debtor_reference,
        $environment,
        $user_id,
        $orderId,
        $payload = ['environment' => $environment]
    ) {
        $this->debtor_reference = $debtor_reference;
        $this->description = $description;
        $this->transaction_url = $transaction_url;
        $this->transaction_id = $transaction_id;
        $this->entrance_code = $entrance_code;
        $this->user_id = $user_id;
        $this->timestamp = date("Y-m-d H:i:s") ?: '';
        $this->type = "identity";
        $this->payload =$payload;
        $this->orderId = $orderId;
    }

    public function withId(int $getInsertedId): Request
    {
        $copy = clone $this;
        $copy->id = $getInsertedId;

        return $copy;
    }

    public function toArray(): array
    {
        return [
            'debtor_reference' => $this->debtor_reference,
            'description' => $this->description,
            'transaction_url' => $this->transaction_url,
            'transaction_id' => $this->transaction_id,
            'entrance_code' => $this->entrance_code,
            'user_id' => $this->user_id,
            'timestamp' => $this->timestamp,
            'type' => $this->type,
            'payload' => $this->payload,
            'orderId' => $this->orderId,
        ];
    }
}
