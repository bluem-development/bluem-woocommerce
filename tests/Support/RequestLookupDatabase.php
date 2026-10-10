<?php

namespace Tests\Support;

final class RequestLookupDatabase
{
    public string $prefix = 'custom_';
    public mixed $results = [];
    public array $prepared = [];
    public array $queries = [];
    public int $showErrorsCalls = 0;
    public bool $failPrepare = false;
    public bool $failRead = false;

    public function show_errors(): void
    {
        ++$this->showErrorsCalls;
    }

    public function prepare($sql, ...$values): string
    {
        $this->prepared[] = [$sql, $values];
        if ($this->failPrepare) {
            throw new \RuntimeException('prepare failed');
        }
        return 'prepared-query';
    }

    public function get_results($sql): mixed
    {
        $this->queries[] = $sql;
        if ($this->failRead) {
            throw new \RuntimeException('read failed');
        }
        return $this->results;
    }
}
