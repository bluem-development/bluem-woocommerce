<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function bluem_register_blocks(): void {
	$blocks = [
		'blocks/idin-block',
		// 'blocks/another-block',
	];

	foreach ( $blocks as $block ) {
		register_block_type( __DIR__ . '/' . $block );
	}
}

add_action( 'init', 'bluem_register_blocks' );
