<?php

namespace Bluem\Wordpress\Requests;

use Closure;
use stdClass;

/** Merges persisted payload fields; the adapter supplies WordPress JSON encoding. */
final class BluemRequestPayloadUpdater
{
    /** @param Closure(stdClass): (string|false) $encode */
    public function __construct(
        private readonly BluemRequestRepository $requests,
        private readonly BluemRequestWriter $writer,
        private readonly Closure $encode
    ) {
    }

    public function update($requestId, $data): void
    {
        $request = $this->requests->findById($requestId);
        if ($request === false) {
            return;
        }
        $payload = json_decode((string) $request->payload);
        $payload = $payload instanceof stdClass ? $payload : new stdClass();
        foreach ($data as $key => $value) {
            $payload->$key = $value;
        }

        $this->writer->update($requestId, ['payload' => ($this->encode)($payload)]);
    }
}
