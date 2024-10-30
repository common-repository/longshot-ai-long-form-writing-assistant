<?php
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit;
	}

	// Remove all options
	if ( function_exists( 'delete_option' ) ) {
		$options = [
			"api_key",
			"unique_id",
			"auth_id",
			"extension_allowed",
			"persistent_message",
			"author_id",
		];
		foreach ( $options as $option ) {
			delete_option( "longshot_ai_$option" );
		}
	}

	// Remove post meta
	if ( function_exists( 'unregister_post_meta' ) ) {
		$post_meta_keys = [
			[ "name" => "meta_description", "type" => "string" ],
			[ "name" => "semantic_seo_score", "type" => "string" ],
			[ "name" => "post_topic", "type" => "string" ],
			[ "name" => "country", "type" => "string" ],
			[ "name" => "language", "type" => "string" ],
			[ "name" => "reading_score", "type" => "string" ]
		];
		foreach ( $post_meta_keys as $meta_key ) {
			unregister_post_meta( 'post', "longshot_ai_$meta_key[name]" );
		}
	}
