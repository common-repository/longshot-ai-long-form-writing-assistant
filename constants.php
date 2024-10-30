<?php
	global $longshot_post_meta_keys, $longshot_options, $longshot_app_url, $longshot_feedback_skip_popup_name, $longshot_show_popup_after;

	$longshot_post_meta_keys = [
		[ "name" => "meta_description", "type" => "string" ],
		[ "name" => "semantic_seo_score", "type" => "string" ],
		[ "name" => "post_topic", "type" => "string" ],
		[ "name" => "country", "type" => "string" ],
		[ "name" => "language", "type" => "string" ],
		[ "name" => "reading_score", "type" => "string" ]
	];
	$longshot_options        = [
		"api_key",
		"unique_id",
		"auth_id",
		"extension_allowed",
		"persistent_message",
		"author_id",
		"feedback_given",
		"feedback_message",
		"feedback_dismissed"
	];

	$longshot_feedback_skip_popup_name = "feedback_skip_popup";
	$longshot_show_popup_after         = DAY_IN_SECONDS*3;

	$longshot_app_url = "https://app.longshot.ai";
	$longshot_api_url = "https://api-v2.longshot.ai/api";
