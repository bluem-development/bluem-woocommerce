<?php

namespace Tests\Support;

final class RequestLogLinkDatabase
{
    public string $prefix = 'custom_';
    public int $insert_id = 73;
    public mixed $insertResult = 1;
    public mixed $results = [];
    public array $inserts = [];
    public array $prepared = [];
    public bool $throw = false;

    public function insert($table, $data): mixed
    {
        $this->inserts[] = [$table, $data];
        if ($this->throw) {
            throw new \RuntimeException('database failure');
        }
        return $this->insertResult;
    }

    public function prepare($sql, ...$values): string
    {
        $this->prepared[] = [$sql, $values];
        return $sql;
    }

    public function get_results($sql): mixed
    {
        if ($this->throw) {
            throw new \RuntimeException('database failure');
        }
        return $this->results;
    }
}
