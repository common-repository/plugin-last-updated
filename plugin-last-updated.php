<?php
/*
Plugin Name: Plugin Last Updated
Version: 1.0.2
License: GPL
Author: Pete Mall, Range, Aaron Campbell, Sara Cannon
Author URI: http://petemall.com/
Description: Display the last updated date for all plugins in the <a href="http://wordpress.org/extend/plugins/">WordPress.org plugins repo</a>.
*/

add_filter( 'plugin_row_meta', 'range_plu_plugin_meta', 10, 2 );

function range_plu_plugin_meta( $plugin_meta, $plugin_file ) {
	list( $slug ) = explode( '/', $plugin_file );


	$slug_hash = md5( $slug );
	$last_updated = get_transient( "range_plu_{$slug_hash}" );
	if ( false === $last_updated ) {
		$last_updated = range_plu_get_last_updated( $slug );
		set_transient( "range_plu_{$slug_hash}", $last_updated, 86400 );
	}

	if ( $last_updated )
		$plugin_meta['last_updated'] = 'Last Updated: ' . esc_html( $last_updated );

	return $plugin_meta;
}

function range_plu_get_last_updated( $slug ) {
	$request = wp_remote_post(
		'http://api.wordpress.org/plugins/info/1.0/',
		array(
			'body' => array(
				'action' => 'plugin_information',
				'request' => serialize(
					(object) array(
						'slug' => $slug,
						'fields' => array( 'last_updated' => true )
					)
				)
			)
		)
	);
	if ( 200 != wp_remote_retrieve_response_code( $request ) )
		return false;

	$response = unserialize( wp_remote_retrieve_body( $request ) );
	// Return an empty but cachable response if the plugin isn't in the .org repo
	if ( empty( $response ) )
		return '';
	if ( isset( $response->last_updated ) )
		return sanitize_text_field( $response->last_updated );

	return false;
}
