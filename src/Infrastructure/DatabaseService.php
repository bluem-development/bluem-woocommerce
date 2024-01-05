<?php

namespace Bluem\WooCommerce\Infrastructure;

class DatabaseService
{
    private $wpdb;

    public function __construct()
    {
        global $wpdb;

        // @todo: set timezone
//         date_default_timezone_set('Europe/Amsterdam');
//         $wpdb->time_zone = 'Europe/Amsterdam';
        // for testing envs:
//        $wpdb->show_errors();

        $this->wpdb = $wpdb;
    }

    private function prefixTable(string $tableName):string {
        return $this->wpdb->prefix . $tableName;
    }

    public function insert(string $tableName, array $data): int
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

    public function update(string $tableName, $request_object, array $conditions)
    {
        return $this->wpdb->update(
            $this->prefixTable($tableName),
            $request_object,
            $conditions
        );
    }

    public function getById(string $tableName, $idValue, string $idField= 'id'): ?array
    {
        $data = $this->wpdb->get_results(
            sprintf("SELECT * FROM %s WHERE %s='%s' LIMIT 1", $tableName, $idField, $idValue)
        );

        return $data !== null ? $data[0] : null;
    }

    public function delete(string $tableName, array $conditions): bool
    {
        return $this->wpdb->delete($tableName, $conditions);
    }

    public function query(string $tableName, string $query): ?array
    {
        return $this->wpdb->get_results(
            str_replace("{TABLENAME}", $this->prefixTable($tableName), $query)
        );
    }
}
