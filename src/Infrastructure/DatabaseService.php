<?php

namespace Bluem\BluemPHP\Infrastructure;

class DatabaseService
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }

    private function prefixTable(string $tableName):string {
        return $this->wpdb->prefix.$tableName;
    }

    public function insert($tableName, $data): int
    {
        $result = $this->wpdb->insert(
            $this->prefixTable($tableName),
            $data
        );

        return $result ? $this->wpdb->insert_id : -1;
    }

    public function getInsertedId(): int
    {
        return $this->wpdb->insert_id;
    }
}
