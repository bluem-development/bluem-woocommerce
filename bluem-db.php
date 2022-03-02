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

    if ( $installed_ver < $bluem_db_version ) {
        $charset_collate = $wpdb->get_charset_collate();

        include_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE IF NOT EXISTS `bluem_requests` (
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

        $sql2 = "CREATE TABLE IF NOT EXISTS " . "bluem_requests_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            request_id mediumint(9) NOT NULL,
            timestamp timestamp DEFAULT NOW() NOT NULL,
            description varchar(512) NOT NULL,
            user_id mediumint(9) NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
        dbDelta( $sql2 );


        $sql3 = "CREATE TABLE IF NOT EXISTS " . "bluem_requests_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            request_id mediumint(9) NOT NULL,
            item_id mediumint(9) NOT NULL,
            item_type varchar(32) NOT NULL DEFAULT 'order',
            timestamp timestamp DEFAULT NOW() NOT NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
        dbDelta( $sql3 );


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
        "bluem_requests",
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

    $insert_result = $wpdb->insert(
        "bluem_requests_log",
        [
            'request_id'  => $request_id,
            'description' => $description,
            'timestamp'   => date( "Y-m-d H:i:s" ),
            'user_id'     => $current_user->ID
        ]
    );

    return $insert_result;
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
        "bluem_requests",
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

    $query  = $wpdb->delete( 'bluem_requests', [ 'id' => $request_id ] );
    $query2 = $wpdb->delete( 'bluem_requests_log', [ 'request_id' => $request_id ] );

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
        $i   = 0;
        $kvs = " WHERE ";
        foreach ( $keyvalues as $key => $value ) {
            if ( $key == "" || $value == "" ) {
                return false;
            }
            $kvs .= "`{$key}` = '{$value}'";
            $i ++;
            if ( $i < count( $keyvalues ) ) {
                $kvs .= " AND ";
            }
        }
    }
    $query = "SELECT *  FROM  `bluem_requests`{$kvs}";
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
        FROM  `bluem_requests`
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

    return $wpdb->get_results( "SELECT *  FROM  `bluem_requests_log` WHERE `request_id` = $id ORDER BY `timestamp` DESC" );
}


function bluem_db_get_links_for_order( $id ) {
    global $wpdb;
    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';
    return $wpdb->get_results( "SELECT *  FROM  `bluem_requests_links` WHERE `item_id` = {$id} and `item_type` = 'order'ORDER BY `timestamp` DESC" );
}


function bluem_db_get_links_for_request( $id ) {
    global $wpdb;
    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';
    return $wpdb->get_results( "SELECT *  FROM  `bluem_requests_links` WHERE `request_id` = {$id} ORDER BY `timestamp` DESC" );
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
        "bluem_requests_links",
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
