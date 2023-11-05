<?php

register_activation_hook( __FILE__, 'bluem_db_create_requests_table' );
// no need for a deactivation hook yet.

/**
 * Initialize a database table for the requests.
 * @return void
 */
function bluem_db_create_requests_table(): void {
    global $wpdb, $bluem_db_version;

    $installed_ver = (float) get_option( "bluem_db_version" );

    if ( empty( $installed_ver ) || $installed_ver < $bluem_db_version ) {
        $charset_collate = $wpdb->get_charset_collate();

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Define table names
        $table_name_storage = $wpdb->prefix . 'bluem_storage';
        $table_name_requests = $wpdb->prefix . 'bluem_requests';
        $table_name_links = $wpdb->prefix . 'bluem_requests_links';
        $table_name_logs = $wpdb->prefix . 'bluem_requests_log';

        /**
         * Create tables.
         */
        $sql = "CREATE TABLE IF NOT EXISTS `$table_name_requests` (
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
            ) $charset_collate;";
        dbDelta( $sql );

        $sql = "CREATE TABLE IF NOT EXISTS `$table_name_logs` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            request_id mediumint(9) NOT NULL,
            timestamp timestamp DEFAULT NOW() NOT NULL,
            description varchar(512) NOT NULL,
            user_id mediumint(9) NULL,
            PRIMARY KEY (id)
            ) $charset_collate;";
        dbDelta( $sql );

        $sql = "CREATE TABLE IF NOT EXISTS `$table_name_links` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            request_id mediumint(9) NOT NULL,
            item_id mediumint(9) NOT NULL,
            item_type varchar(32) NOT NULL DEFAULT 'order',
            timestamp timestamp DEFAULT NOW() NOT NULL,
            PRIMARY KEY (id)
            ) $charset_collate;";
        dbDelta( $sql );

        $sql = "CREATE TABLE IF NOT EXISTS `$table_name_storage` (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            token varchar(191) NOT NULL,
            secret varchar(191) NOT NULL,
            data longtext NOT NULL,
            timestamp timestamp DEFAULT NOW() NOT NULL,
            PRIMARY KEY (id)
            ) $charset_collate;";
        dbDelta( $sql );

        // Check for previous installed versions
        if ( !empty( $installed_ver ) )
        {
            /**
             * Migrate old tables to new tables including wp-prefix.
             * Old tables in release version <= 1.3.
             */
            if ( $installed_ver <= '1.3' )
            {
                $bluem_requests_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'bluem_requests'") === 'bluem_requests';
                $bluem_requests_links_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'bluem_requests_log'") === 'bluem_requests_log';
                $bluem_requests_log_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'bluem_requests_links'") === 'bluem_requests_links';

                if ( $bluem_requests_table_exists ) {
                    $sql = "INSERT INTO `$table_name_requests` SELECT * FROM bluem_requests;";
                    dbDelta( $sql );
                }

                if ( $bluem_requests_log_table_exists ) {
                    $sql = "INSERT INTO `$table_name_logs` SELECT * FROM bluem_requests_log;";
                    dbDelta( $sql );
                }

                if ( $bluem_requests_links_table_exists ) {
                    $sql = "INSERT INTO `$table_name_links` SELECT * FROM bluem_requests_links;";
                    dbDelta( $sql );
                }
            }
        }

        update_option(
            "bluem_db_version",
            $bluem_db_version
        );
    }
}

function bluem_db_check() {
    global $bluem_db_version;

    if ( (float) get_site_option( 'bluem_db_version' ) !== (float) $bluem_db_version ) {
        bluem_db_create_requests_table();
    }
}

add_action( 'plugins_loaded', 'bluem_db_check' );

// request specific functions
function bluem_db_create_request( $request_object ) {
    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    if ( ! bluem_db_validated_request( $request_object ) ) {
        return - 1;
    }

    $insert_result = $wpdb->insert(
        $wpdb->prefix . "bluem_requests",
        $request_object
    );

    if ( $insert_result ) {
        $request_id = $wpdb->insert_id;

        $request_object = (object) $request_object;

        if ( isset( $request_object->order_id )
            && ! is_null( $request_object->order_id )
            && $request_object->order_id != ""
        ) {
            bluem_db_create_link(
                $request_id,
                $request_object->order_id,
                "order"
            );
        }
        bluem_db_request_log(
            $request_id,
            "Created request"
        );

        return $wpdb->insert_id;
    } else {
        return - 1;
    }
}

function bluem_db_request_log( $request_id, $description, $log_data = [] ) {
    global $wpdb, $current_user;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    return $wpdb->insert(
        $wpdb->prefix . "bluem_requests_log",
        [
            'request_id'  => $request_id,
            'description' => $description,
            'timestamp'   => date( "Y-m-d H:i:s" ),
            'user_id'     => $current_user->ID
        ]
    );
}

/**
 * Insert data into storage
 *
 * @param $object
 * @return bool
 * @throws Exception
 */
function bluem_db_insert_storage( $object ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'bluem_storage';

    $token = !empty( $_COOKIE['bluem_storage_token'] ) ? sanitize_text_field( $_COOKIE['bluem_storage_token'] ) : '';

    $secret = !empty( $_COOKIE['bluem_storage_secret'] ) ? sanitize_text_field( $_COOKIE['bluem_storage_secret'] ) : '';

    if ( !empty( $token ) && !empty( $secret ) )
    {
        $query = $wpdb->prepare( "SELECT id, data FROM $table_name WHERE token = %s AND secret = %s", $token, $secret );

        $result = $wpdb->get_results( $query );

        if ( $result ) {
            $decoded_data = json_decode( $result[0]->data, true );

            $record_id = $result[0]->id;

            $new_object = [];

            if ( $decoded_data !== null ) {
                // Loop through current data
                foreach ($decoded_data as $key => $value) {
                    $new_object[$key] = $value;
                }
            }

            // Loop through new data
            foreach ($object as $key => $value) {
                $new_object[$key] = $value; // Overwrite if key exists
            }

            return bluem_db_update_storage($record_id, [
                'data' => json_encode( $new_object ),
            ]);
        }
    }

    // Generate a 32-character token
    $token = bin2hex( random_bytes( 16 ) );

    // Generate a 64-character secret
    $secret = bin2hex( random_bytes( 32 ) );

    $db_result = $wpdb->insert(
        $wpdb->prefix . "bluem_storage",
        [
            'token' => $token,
            'secret' => $secret,
            'data' => json_encode( $object ),
            'timestamp' => date( "Y-m-d H:i:s" ),
        ]
    );

    if ( $db_result !== false )
    {
        // Set cookies for token and secret for
        setcookie( 'bluem_storage_token', $token, 0, '/', $_SERVER['SERVER_NAME'], false, true );
        setcookie( 'bluem_storage_secret', $secret, 0, '/', $_SERVER['SERVER_NAME'], false, true );

        return true;
    }
    return false;
}

/**
 * Get data from storage.
 *
 * @param $key
 * @return false|mixed|void
 */
function bluem_db_get_storage( $key = null ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'bluem_storage';

    $token = !empty( $_COOKIE['bluem_storage_token'] ) ? sanitize_text_field( $_COOKIE['bluem_storage_token'] ) : '';

    $secret = !empty( $_COOKIE['bluem_storage_secret'] ) ? sanitize_text_field( $_COOKIE['bluem_storage_secret'] ) : '';

    if ( !empty( $token ) && !empty( $secret ) )
    {
        $query = $wpdb->prepare( "SELECT data FROM $table_name WHERE token = %s AND secret = %s", $token, $secret );

        $result = $wpdb->get_var( $query );

        if ( $result ) {
            // Decode the JSON data
            $decoded_data = json_decode( $result, true );

            if ( $decoded_data !== null ) {
                if ( $key !== null && isset($decoded_data[ $key ]) ) {
                    return $decoded_data[ $key ]; // Return the specific key's value
                } else {
                    return $decoded_data; // Return the entire decoded JSON data as an array
                }
            }
        }
    }
    return false;
}

/**
 * Update data from storage.
 *
 * @param $id
 * @param $object
 *
 * @return bool
 */
function bluem_db_update_storage( $id, $object ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'bluem_storage';

    $update_result = $wpdb->update(
        $table_name,
        $object,
        [
            'id' => $id
        ]
    );

    if ( $update_result ) {
        return true;
    } else {
        return false;
    }
}

/**
 * @param $request_id
 * @param $request_object
 *
 * @return bool
 */
function bluem_db_update_request( $request_id, $request_object ) {
    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    if ( ! bluem_db_validated_request_well_formed( $request_object ) ) {
        return false;
    }
    $update_result = $wpdb->update(
        $wpdb->prefix . "bluem_requests",
        $request_object,
        [
            'id' => $request_id
        ]
    );

    if ( $update_result ) {
        bluem_db_request_log(
            $request_id,
            "Updated request. New data: " . json_encode( $request_object )
        );

        return true;
    } else {
        return false;
    }
}

/**
 * check if all fields are well-formed
 *
 * @param [type] $request
 *
 * @return bool
 */
function bluem_db_validated_request_well_formed( $request ): bool {
    // @todo: check all available fields on their format
    return true;
}

/**
 * check if all required fields are present and well-formed
 *
 * @param [type] $request
 *
 * @return void
 */
function bluem_db_validated_request( $request ) {
    // check if present
    // entrance_code
    // transaction_id
    // transaction_url
    // user_id
    // timestamp
    // description
    // type

    // optional fields
    // debtor_reference
    // order_id
    // payload

    // and well formed
    if ( ! bluem_db_validated_request_well_formed( $request ) ) {
        return false;
    }

    return true;
}

/**
 * Get fields within any request
 * @return string[]
 */
function bluem_db_get_request_fields() {
    return [
        'id',
        'entrance_code',
        'transaction_id',
        'transaction_url',
        'user_id',
        'timestamp',
        'description',
        'type',
        'debtor_reference',
        'order_id',
        'payload'
    ];
}

/**
 * Get the request for a given ID, or false if not found
 *
 * @param $request_id
 *
 * @return bool|object
 */
function bluem_db_get_request_by_id( string $request_id ) {
    // @todo change to only accept int for $request_id

    $res = bluem_db_get_requests_by_keyvalue(
        'id',
        $request_id
    );

    return $res[0] ?? false;
}

function bluem_db_delete_request_by_id( $request_id ) {
    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    $wpdb->show_errors();

    $query  = $wpdb->delete( $wpdb->prefix . 'bluem_requests', [ 'id' => $request_id ] );
    $query2 = $wpdb->delete( $wpdb->prefix . 'bluem_requests_log', [ 'request_id' => $request_id ] );

    return $query && $query2;
}

function bluem_db_get_request_by_debtor_reference( $debtor_reference ) {
    $res = bluem_db_get_requests_by_keyvalue(
        'debtor_reference',
        $debtor_reference
    );

    return $res !== false && count( $res ) > 0 ? $res[0] : false;
}

function bluem_db_get_request_by_transaction_id( $transaction_id ) {
    $res = bluem_db_get_requests_by_keyvalue(
        'transaction_id',
        $transaction_id
    );

    return $res !== false && count( $res ) > 0 ? $res[0] : false;
}

function bluem_db_get_request_by_transaction_id_and_type( $transaction_id, $type ) {
    $res = bluem_db_get_requests_by_keyvalues(
        [
            'transaction_id' => $transaction_id,
            'type'           => $type
        ]
    );

    return $res !== false && count( $res ) > 0 ? $res[0] : false;
}

function bluem_db_get_request_by_transaction_id_and_entrance_code( $transaction_id, $entrance_code, $type = '' ) {
    $res = bluem_db_get_requests_by_keyvalues(
        [
            'transaction_id' => $transaction_id,
            'entrance_code' => $entrance_code,
        ]
    );

    return $res !== false && count( $res ) > 0 ? $res[0] : false;
}

function bluem_db_get_requests_by_keyvalue(
    $key,
    $value,
    $sort_key = null,
    $sort_dir = "ASC",
    $limit = 0
) {
    return bluem_db_get_requests_by_keyvalues(
        [ $key => $value ],
        $sort_key,
        $sort_dir,
        $limit
    );
}

function bluem_db_get_requests_by_keyvalues(
    $keyvalues = [],
    $sort_key = null,
    $sort_dir = "ASC",
    $limit = 0
) {
    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    $wpdb->show_errors(); //setting the Show or Display errors option to true
    // @todo: Prepare this statement a bit more; https://developer.wordpress.org/reference/classes/wpdb/

    if ( count( $keyvalues ) > 0 ) {
        $kvs = " WHERE ";

        $i = 0; foreach ($keyvalues as $key => $value) {
            if ( empty($key) || $value === "" ) {
                continue;
            }
            $kvs .= "`{$key}` = '{$value}'";
            $i ++;
            if ($i < count($keyvalues)) {
                $kvs .= " AND ";
            }
        }
    }
    $query = "SELECT *  FROM  `" . $wpdb->prefix . "bluem_requests` {$kvs}";
    if ( ! is_null( $sort_key ) && $sort_key !== ""
        && in_array( $sort_dir, [ 'ASC', 'DESC' ] )
    ) {
        $query .= " ORDER BY {$sort_key} {$sort_dir}";
    }

    if ( ! is_null( $limit ) && $limit !== ""
        && is_numeric( $limit ) && $limit > 0
    ) {
        $query .= " LIMIT {$limit}";
    }

    try {
        return $wpdb->get_results(
            $query
        );
    } catch ( Throwable $th ) {
        return false;
    }
}

function bluem_db_get_requests_by_user_id( $user_id = null ) {
    global $current_user;

    if ( is_null( $user_id ) ) {
        $user_id = $current_user->ID;
    }

    $res = bluem_db_get_requests_by_keyvalue(
        'user_id',
        $user_id
    );

    return $res !== false && count( $res ) > 0 ? $res : [];
}

function bluem_db_get_requests_by_user_id_and_type( $user_id = null, $type = "" ) {
    global $current_user;

    if ( is_null( $user_id ) ) {
        $user_id = $current_user->ID;
    }

    // @todo Throw an error when type is not given, or default to wildcard

    $res = bluem_db_get_requests_by_keyvalues(
        [
            'user_id' => $user_id,
            'type'    => $type
        ],
        'timestamp',
        'DESC'
    );

    return $res !== false && count( $res ) > 0 ? $res : [];
}

function bluem_db_get_most_recent_request( $user_id = null, $type = "mandates" ) {
    global $current_user;

    if ( is_null( $user_id ) ) {
        $user_id = $current_user->ID;
    }

    if ( ! in_array(
        $type,
        [ 'mandates', 'payments', 'identity' ]
    )
    ) {
        return false;
    }

    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    $wpdb->show_errors(); //setting the Show or Display errors option to true

    $query = "SELECT *
        FROM  `" . $wpdb->prefix . "bluem_requests`
        WHERE `user_id` = '{$user_id}'
            AND `type` = '{$type}'
        ORDER BY `timestamp` DESC
        LIMIT 1 ";
    try {
        $results = $wpdb->get_results(
            $query
        );

        if ( count( $results ) > 0 ) {
            return $results[0];
        }

        return false;
    } catch ( Throwable $th ) {
        return false;
    }
}


function bluem_db_put_request_payload( $request_id, $data ) {
    $request = bluem_db_get_request_by_id( $request_id );

    if ( $request->payload !== "" ) {
        try {
            $newPayload = json_decode( $request->payload );
        } catch ( Throwable $th ) {
            $newPayload = new Stdclass;
        }
    } else {
        $newPayload = new Stdclass;
    }
    foreach ( $data as $k => $v ) {
        $newPayload->$k = $v;
    }

    bluem_db_update_request(
        $request_id,
        [
            'payload' => json_encode( $newPayload )
        ]
    );
}

function bluem_db_get_logs_for_request( $id ) {
    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    return $wpdb->get_results( "SELECT *  FROM  `" . $wpdb->prefix . "bluem_requests_log` WHERE `request_id` = $id ORDER BY `timestamp` DESC" );
}

function bluem_db_get_links_for_order( $id ) {
    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    return $wpdb->get_results( "SELECT *  FROM  `" . $wpdb->prefix . "bluem_requests_links` WHERE `item_id` = {$id} and `item_type` = 'order'ORDER BY `timestamp` DESC" );
}

function bluem_db_get_links_for_request( $id ) {
    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    return $wpdb->get_results( "SELECT *  FROM  `" . $wpdb->prefix . "bluem_requests_links` WHERE `request_id` = {$id} ORDER BY `timestamp` DESC" );
}

function bluem_db_create_link( $request_id, $item_id, $item_type = "order" ) {
    global $wpdb;

    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    $installed_ver = (float) get_option( "bluem_db_version" );
    if ( $installed_ver <= 1.2 ) {
        return;
    }

    $insert_result = $wpdb->insert(
        $wpdb->prefix . "bluem_requests_links",
        [
            'request_id' => $request_id,
            'item_id'    => $item_id,
            'item_type'  => $item_type
        ]
    );

    if ( $insert_result ) {
        $link_id = $wpdb->insert_id;

        return $link_id;
    } else {
        return - 1;
    }
}
