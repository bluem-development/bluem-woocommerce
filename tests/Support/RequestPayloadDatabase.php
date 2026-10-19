<?php

namespace Tests\Support;

final class RequestPayloadDatabase
{
    public string $prefix = 'payload_';
    public array $results = [];
    public array $updates = [];
    public array $logs = [];
    public mixed $updateResult = 1;
    public bool $throwOnUpdate = false;

    public function show_errors(): void {}
    public function prepare($sql, ...$values) { return $sql; }
    public function get_results($sql) { return $this->results; }
    public function update($table, $data, $where) {
        $this->updates[] = [$table, $data, $where];
        if ($this->throwOnUpdate) {
            throw new \RuntimeException('update failed');
        }
        return $this->updateResult;
    }
    public function insert($table, $data) { $this->logs[] = [$table, $data]; return 1; }
}
