<?php

namespace Bluem\WooCommerce\Domain;

final class DatabaseMigration
{
    public string $sql;

    public function __construct(string $sql) {
        $this->sql = $sql;
    }
}
