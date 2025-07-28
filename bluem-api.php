<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action('rest_api_init', function () {
	register_rest_route('bluem/v1', '/user-state', [
		'methods'  => 'GET',
		'callback' => function () {
			return [
				'userIdentified'           => bluem_get_idin_state(),
			];
		},
		'permission_callback' => '__return_true',
	]);
});


function bluem_get_idin_state(): array {
	$bluem_config = bluem_woocommerce_get_config();

	$entranceCode   = get_user_meta( get_current_user_id(), 'bluem_idin_entrance_code', true );
	$transactionID  = get_user_meta( get_current_user_id(), 'bluem_idin_transaction_id', true );
	$transactionURL = get_user_meta( get_current_user_id(), 'bluem_idin_transaction_url', true );

	return [
			'entranceCode'   => $entranceCode,
			'transactionID'  => $transactionID,
			'transactionURL' => $transactionURL,
			'config'         => $bluem_config,
			'userIdentified' => !empty($entranceCode) && !empty($transactionID) && !empty($transactionURL),
	];
}


add_action('rest_api_init', function () {
	register_rest_route('bluem/v1', '/identify-start', [
		'methods'  => 'GET',
		'callback' => function () {
			$redirectUrl = 'https://example.com/idin/start'; // you get this from your service
			return new WP_REST_Response(['redirectUrl' => $redirectUrl], 200);
		},
	]);
});
