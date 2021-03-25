<?php


register_activation_hook(__FILE__, 'bluem_db_create_requests_table');
// no need for a deactivation hook yet.


function bluem_db_create_requests_table()
{
    global $wpdb, $bluem_db_version;
    $installed_ver = (float)get_option("bluem_db_version");

    if ($installed_ver < $bluem_db_version) {

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
        dbDelta($sql);

        $sql2 = "CREATE TABLE IF NOT EXISTS " . "bluem_requests" . "_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            request_id mediumint(9) NOT NULL,
            timestamp timestamp DEFAULT NOW() NOT NULL,
            description varchar(512) NOT NULL,
            user_id mediumint(9) NULL,
            PRIMARY KEY  (id)
            ) $charset_collate;";
        dbDelta($sql2);

        update_option(
            "bluem_db_version",
            $bluem_db_version
        );
    }
}


function bluem_db_check()
{
    global $bluem_db_version;
    if ((float)get_site_option('bluem_db_version') !== (float)$bluem_db_version) {
        bluem_db_create_requests_table();
    }
}

add_action('plugins_loaded', 'bluem_db_check');



// request specific functions

function bluem_db_create_request($request_object)
{
    global $wpdb;
    date_default_timezone_set('Europe/Amsterdam');
    $wpdb->time_zone = 'Europe/Amsterdam';

    if (!bluem_db_validated_request($request_object)) {
        return false;
    }

    $insert_result = $wpdb->insert(
        "bluem_requests",
        $request_object
    );
    if ($insert_result) {
        $request_id = $wpdb->insert_id;
        bluem_db_request_log(
            $request_id,
            "Created request"
        );
    }
    return $insert_result;
}

function bluem_db_request_log($request_id, $description, $log_data = []) {
    global $wpdb, $current_user;
    date_default_timezone_set('Europe/Amsterdam');
    $wpdb->time_zone = 'Europe/Amsterdam';

    $insert_result = $wpdb->insert(
        "bluem_requests_log",
        [
            'request_id'=>$request_id,
            'description'=>$description,
            'timestamp'=>date("Y-m-d H:i:s"),
            'user_id'=>$current_user->ID
        ]
    );
    return $insert_result;
}
function bluem_db_update_request($request_id, $request_object)
{
    global $wpdb;
    date_default_timezone_set('Europe/Amsterdam');
    $wpdb->time_zone = 'Europe/Amsterdam';

    
    if(!bluem_db_validated_request_wellformed($request_object)) {
        return false;
    }
    $update_result = $wpdb->update(
        "bluem_requests",
        $request_object,
        [
            'id' => $request_id
            ]
        );
        
    if ($update_result) {
        bluem_db_request_log(
            $request_id,
            "Updated request. New data: ".json_encode($request_object)
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
 * @return void
 */
function bluem_db_validated_request_wellformed($request) {

    // @todo: check all available fields on their format
    return true;

}

/**
 * check if all required fields are present and well-formed
 *
 * @param [type] $request
 * @return void
 */
function bluem_db_validated_request($request) {

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
    if (!bluem_db_validated_request_wellformed($request)) {
        return false;
    }

    return true;

}


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

function bluem_db_get_request_by_id($request_id)
{
    $res = bluem_db_get_request_by_keyvalue(
        'id', $request_id
    );
    return count($res)>0?$res[0]:false;
}
function bluem_db_get_request_by_transaction_id($transaction_id)
{
    $res = bluem_db_get_request_by_keyvalue(
        'transaction_id', $transaction_id
    );
    return count($res)>0?$res[0]:false;
}

function bluem_db_get_request_by_keyvalue($key,$value)
{
    global $wpdb;

    if (!in_array(
        $key,
        bluem_db_get_request_fields()
    )
    ) {

        return false;
    }

    
    $wpdb->show_errors(); //setting the Show or Display errors option to true
    // @todo: Prepare this statement a bit more; https://developer.wordpress.org/reference/classes/wpdb/
    $query = "SELECT *  FROM  `bluem_requests` WHERE `{$key}` = '{$value}'";
    try { 
        return $wpdb->get_results(
            $query
        );
    } catch(Throwable $th) {
        return false;
    }
}