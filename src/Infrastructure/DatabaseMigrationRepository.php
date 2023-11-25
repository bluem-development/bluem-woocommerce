<?php

namespace Bluem\WooCommerce\Infrastructure;

use Bluem\WooCommerce\Domain\DatabaseMigration;

class DatabaseMigrationRepository
{
    /**
     * @return DatabaseMigration[]
     */
    public function getMigrations(): array
    {
        global $wpdb, $bluem_db_version;

        $installed_ver = (float) get_option( "bluem_db_version" );
        if ( !empty( $installed_ver ) && $installed_ver >= $bluem_db_version ) {
            // up to date
            return [];
        }

        // Define table names
        $table_name_storage = $wpdb->prefix . 'bluem_storage';
        $table_name_requests = $wpdb->prefix . 'bluem_requests';
        $table_name_links = $wpdb->prefix . 'bluem_requests_links';
        $table_name_logs = $wpdb->prefix . 'bluem_requests_log';

        $charset_collate = $wpdb->get_charset_collate();

        $migrations = [];
        /**
         * Create tables.
         */
        $migrations[] = new DatabaseMigration("CREATE TABLE IF NOT EXISTS `$table_name_requests` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            transaction_id varchar(64) NOT NULL,
            entrance_code varchar(64) NOT NULL,
            transaction_url varchar(256) NOT NULL,
            timestamp timestamp DEFAULT NOW() NOT NULL,
            description tinytext NOT NULL,
            debtor_reference varchar(64) NOT NULL,
            type varchar(16) DEFAULT '' NOT NULL,
            status varchar(16) DEFAULT 'created' NOT NULL,
            order_id mediumint(9) DEFAULT NULL,
            payload text NOT NULL,
            PRIMARY KEY (id)
            ) $charset_collate;");

        $migrations[] = new DatabaseMigration("CREATE TABLE IF NOT EXISTS `$table_name_logs` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            request_id mediumint(9) NOT NULL,
            timestamp timestamp DEFAULT NOW() NOT NULL,
            description varchar(512) NOT NULL,
            user_id mediumint(9) NULL,
            PRIMARY KEY (id)
            ) $charset_collate;");

        $migrations[] = new DatabaseMigration("CREATE TABLE IF NOT EXISTS `$table_name_links` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            request_id mediumint(9) NOT NULL,
            item_id mediumint(9) NOT NULL,
            item_type varchar(32) NOT NULL DEFAULT 'order',
            timestamp timestamp DEFAULT NOW() NOT NULL,
            PRIMARY KEY (id)
            ) $charset_collate;");

        $migrations[] = new DatabaseMigration("CREATE TABLE IF NOT EXISTS `$table_name_storage` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            token varchar(191) NOT NULL,
            secret varchar(191) NOT NULL,
            data longtext NOT NULL,
            timestamp timestamp DEFAULT NOW() NOT NULL,
            PRIMARY KEY (id)
            ) $charset_collate;");

        /**
         * Check for previous installed versions
         * Migrate old tables to new tables including wp-prefix.
         * Old tables in release version <= 1.3.
         */
        if (!empty($installed_ver) && $installed_ver <= '1.3') {
            $bluem_requests_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'bluem_requests'") === 'bluem_requests';
            $bluem_requests_links_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'bluem_requests_log'") === 'bluem_requests_log';
            $bluem_requests_log_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'bluem_requests_links'") === 'bluem_requests_links';

            if ( $bluem_requests_table_exists ) {
                $migrations[] = new DatabaseMigration("INSERT INTO `$table_name_requests` SELECT * FROM bluem_requests;");
            }

            if ( $bluem_requests_log_table_exists ) {
                $migrations[] = new DatabaseMigration("INSERT INTO `$table_name_logs` SELECT * FROM bluem_requests_log;");
            }

            if ( $bluem_requests_links_table_exists ) {
                $migrations[] = new DatabaseMigration("INSERT INTO `$table_name_links` SELECT * FROM bluem_requests_links;");
            }
        }

        return $migrations;
    }
}
