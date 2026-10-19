<?php

namespace Bluem\Wordpress\Requests;

use Closure;

/** Request writes; adapters supply link persistence and translated audit logging. */
final class BluemRequestWriter
{
    /**
     * @param \wpdb $database WordPress connection or test double.
     * @param Closure(mixed, mixed): mixed $link Receives request and order IDs.
     * @param Closure(mixed, string, mixed): mixed $log Receives ID, operation and request data.
     */
    public function __construct(
        private readonly object $database,
        private readonly BluemRequestValidator $validator,
        private readonly Closure $link,
        private readonly Closure $log
    ) {
    }

    public function create($request): int
    {
        if (!$this->validator->isValid($request)) {
            return -1;
        }
        if (!$this->database->insert($this->database->prefix . 'bluem_requests', $request)) {
            return -1;
        }

        // Capture before link/log writes change the connection's insert ID.
        $id = $this->database->insert_id;
        $request = (object) $request;
        if (isset($request->order_id) && !is_null($request->order_id) && $request->order_id != '') {
            ($this->link)($id, $request->order_id);
        }
        ($this->log)($id, 'created', $request);

        return $id;
    }

    public function update($id, $request): bool
    {
        if (!$this->validator->isWellFormed($request)) {
            return false;
        }
        if (!$this->database->update($this->database->prefix . 'bluem_requests', $request, ['id' => $id])) {
            return false;
        }
        ($this->log)($id, 'updated', $request);

        return true;
    }
}
