<?php
	require_once 'constants.php';
	require_once 'secure.php';

	final class LongshotAI_api_handler {
		private $api_key;
		private $unique_id;
		private $auth_id;
		public $base_uri;

		function __construct() {
			global $longshot_api_url;
			$this->base_uri = $longshot_api_url;
		}

		function register_services() {
			$this->reload_keys();

			// Connection Timeout (in seconds)
			add_filter( 'http_request_timeout', function ( $timeout ) {
				return 5 * 60;
			} );

			add_action( 'wp_ajax_longshot_ai_get_seo_score', array( $this, 'get_seo_score' ) );
			add_action( 'wp_ajax_longshot_ai_generate_headline', array( $this, 'generate_headline' ) );
			add_action( 'wp_ajax_longshot_ai_write_more', array( $this, 'write_more' ) );
			add_action( 'wp_ajax_longshot_ai_get_all_seo_data', array( $this, 'get_all_seo_data' ) );
			add_action( 'wp_ajax_longshot_ai_get_semantic_scores', array( $this, 'get_semantic_score' ) );
			add_action( 'wp_ajax_longshot_ai_get_serp_scores', array( $this, 'get_serp_score' ) );
			add_action( 'wp_ajax_longshot_ai_generate_description', array( $this, 'generate_description' ) );
			add_action( 'wp_ajax_longshot_ai_generate_conclusion', array( $this, 'generate_conclusion' ) );
			add_action( 'wp_ajax_longshot_ai_instruct_me', array( $this, 'instruct_me' ) );
			add_action( 'wp_ajax_longshot_ai_get_reading_score', array( $this, 'get_reading_score' ) );
			add_action( 'wp_ajax_longshot_ai_handle_feedback', array( $this, 'handle_feedback' ) );
		}

		function reload_keys() {
			// Authentication for the API
			$this->api_key = Longshot_AI_Secure::decrypt( get_option( 'longshot_ai_api_key' ) );

			// User ID
			$this->unique_id = Longshot_AI_Secure::decrypt( get_option( 'longshot_ai_unique_id' ) );

			// User ID, or Team admin ID if user is in a team
			$this->auth_id = Longshot_AI_Secure::decrypt( get_option( 'longshot_ai_auth_id' ) );
		}

		function get_seo_score() {
			$url  = $this->base_uri . '/predict/blog/score';
			$blog = wp_filter_post_kses( $_POST['blog'] );

			$response = $this->post_request( $url, [ 'url' => $blog ] );
			wp_send_json_success( $response );
		}

		function is_logged_in(): bool {
			$this->reload_keys();

			return $this->api_key !== '' && $this->unique_id !== '';
		}

		function get_current_user( $id = null ) {
			if ( ! $id ) {
				$id = $this->unique_id;
			}
			if ( ! $this->is_logged_in() ) {
				return array(
					'logged_in' => false,
					'message'   => 'You are not logged in'
				);
			}
			$url = $this->base_uri . '/fetch/user/details/wordpress_plugin';

			$response = wp_remote_get( $url, array(
				'headers' => array(
					'Authorization' => 'Basic ' . $this->api_key,
					'Content-Type'  => 'application/json'
				),
				'body'    => array(
					// Unique ID is used to get the user's details
					'unique_id' => $id
				)
			) );
			if ( is_wp_error( $response ) ) {
				return array(
					'logged_in' => false,
					'message'   => $response->get_error_message()
				);
			}
			$status   = wp_remote_retrieve_response_code( $response );
			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			// 400 -> Deactivated account
			if ( $status === 400 ) {
				return array(
					'logged_in' => false,
					'message'   => $response['message'] ?? "Your account has been deactivated by admin. If you think this is a mistake, contact info@longshot.ai"
				);
			}

			// Status 401 -> unauthorized
			if ( $status === 401 ) {
				return array(
					'logged_in' => false,
					'message'   => $response['message'] ?? "You are unauthorized to login at this moment. Please contact info@longshot.ai and we will fix this for you."
				);
			}

			// Any other status -> error
			if ( $status !== 200 ) {
				return array(
					'logged_in' => false,
					'message'   => $response['detail'] ?? $response['message'] ?? "Oops. Could not login, please try again. If the error persists, contact info@longshot.ai"
				);
			}

			return $response;
		}

		function get_team_credits(): array {
			$team_details = $this->get_current_user( $this->auth_id );
			if ( ! isset( $team_details['credit_limit'] ) ) {
				return $team_details;
			}

			return [
				'team_credit_limit' => $team_details['credit_limit'],
				'team_credits_used' => $team_details['credits_used']
			];
		}

		function is_team_member(): bool {
			return $this->unique_id != $this->auth_id;
		}

		function is_extension_allowed(): bool {
			return Longshot_AI_Secure::decrypt( get_option( 'longshot_ai_extension_allowed' ) );
		}

		function get_persistent_message() {
			$message = get_option( 'longshot_ai_persistent_message' );
			if ( $message === false ) {
				return null;
			}

			return $message;
		}

		function generate_headline() {
			$url         = $this->base_uri . '/generate/blog/headline';
			$description = wp_filter_post_kses( $_POST['description'] );

			$response = $this->post_request( $url, [
				'description' => $description,
				'language'    => 'en',
				'style'       => 'descriptive',
				'topic'       => 'general'
			] );
			wp_send_json_success( $response );
		}

		function write_more() {
			$url               = $this->base_uri . '/content/write/for/me';
			$text              = wp_filter_post_kses( $_POST['text'] );
			$language          = sanitize_text_field( $_POST['language'] );
			$niche             = '';
			$generation_length = 1;
			$use_cohere        = wp_validate_boolean( $_POST['use_cohere'] );

			$response = $this->post_request( $url, [
				'text'              => $text,
				'language'          => $language,
				'niche'             => $niche,
				'generation_length' => $generation_length,
				'use_cohere'        => $use_cohere
			] );

			wp_send_json_success( $response );
		}

		function get_all_seo_data() {
			$url      = $this->base_uri . '/get/all/seo/data/for/topic';
			$topic    = sanitize_text_field( $_POST['topic'] );
			$country  = sanitize_text_field( $_POST['country'] );
			$language = sanitize_text_field( $_POST['language'] );

			$response = $this->get_request( $url, [
				'unique_id' => $this->auth_id,
				'topic'     => $topic,
				'country'   => $country,
				'language'  => $language
			] );

			wp_send_json_success( $response );
		}

		function get_semantic_score() {
			$text     = wp_filter_post_kses( $_POST['text'] );
			$topic    = sanitize_text_field( $_POST['topic'] );
			$country  = sanitize_text_field( $_POST['country'] );
			$language = sanitize_text_field( $_POST['language'] );

			$response = $this->fetch_semantic_score( $text, $topic, $country, $language );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array(
					'message' => $response->get_error_message()
				) );
				exit;
			}
			wp_send_json_success( json_decode( wp_remote_retrieve_body( $response ) ) );
		}

		function get_serp_score() {
			$url      = $this->base_uri . '/get/serp/score';
			$topic    = sanitize_text_field( $_POST['topic'] );
			$country  = sanitize_text_field( $_POST['country'] );
			$language = sanitize_text_field( $_POST['language'] );

			$url .= '?' . http_build_query( array(
					'unique_id' => $this->auth_id,
					'topic'     => $topic,
					'country'   => $country,
					'language'  => $language
				) );

			$response = wp_remote_get( $url, array(
				'headers' => array(
					'Authorization' => 'Basic ' . $this->api_key,
					'Content-Type'  => 'application/json'
				)
			) );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array(
					'message' => $response->get_error_message()
				) );
				exit;
			}
			wp_send_json_success( json_decode( wp_remote_retrieve_body( $response ) ) );
		}

		function save_scores( $post_id, $post ) {
			$topic    = get_post_meta( $post_id, 'longshot_ai_post_topic', true );
			$country  = get_post_meta( $post_id, 'longshot_ai_country', true );
			$language = get_post_meta( $post_id, 'longshot_ai_language', true );
			$text     = $post->post_content;

			$semantic_response = $this->fetch_semantic_score( $text, $topic, $country, $language );

			if ( is_wp_error( $semantic_response ) ) {
				return;
			}
			$semantic_response = json_decode( wp_remote_retrieve_body( $semantic_response ) );
			if ( isset( $semantic_response->scores ) && is_array( $semantic_response->scores[0] ) ) {
				update_post_meta( $post_id, 'longshot_ai_semantic_seo_score', $this->calculate_semantic_grade( $semantic_response->scores[0] ) );
			}
			// Update meta description
			$meta_field = 'longshot_ai_meta_description';
			if ( isset( $_POST[ $meta_field ] ) ) {
				$sanitized_meta_description = sanitize_meta( $meta_field, $_POST[ $meta_field ], 'post' );
				update_post_meta( $post_id, $meta_field, $sanitized_meta_description );
			}
		}

		function generate_description() {
			$url         = $this->base_uri . '/generate/content/meta/description';
			$description = sanitize_textarea_field( $_POST['description'] );
			$language    = sanitize_text_field( $_POST['language'] );
			$headline    = sanitize_text_field( $_POST['headline'] );

			$response = $this->post_request( $url, [
				'description' => $description,
				'language'    => $language,
				'headline'    => $headline
			] );
			wp_send_json_success( $response );
		}

		function generate_conclusion() {
			$url      = $this->base_uri . '/generate/blog/conclusion';
			$text     = sanitize_textarea_field( $_POST['context'] );
			$language = sanitize_text_field( $_POST['language'] );

			$response = $this->post_request( $url, [
				'text'     => $text,
				'language' => $language,
				'team_id'  => ''
			] );

			wp_send_json_success( $response );
		}

		function instruct_me() {
			$url     = $this->base_uri . '/generate/instruct';
			$command = sanitize_textarea_field( $_POST['command'] );

			$response = $this->post_request( $url, [ 'text' => $command ] );
			wp_send_json_success( $response );
		}

		function get_reading_score() {
			$url  = $this->base_uri . '/predict/blog/score';
			$blog = wp_filter_post_kses( $_POST['blog'] );

			$response = $this->post_request( $url, [ 'blog' => $blog ] );
			wp_send_json_success( $response );
		}

		function handle_feedback() {
			global $longshot_feedback_skip_popup_name;
			$feedback = sanitize_textarea_field($_POST['feedback']);
			$submitter = sanitize_text_field($_POST['type']);

			try {
				switch ( $submitter ) {
					case 'skip':
						update_option( 'longshot_ai_feedback_dismissed', true );
						break;
					case 'remind':
						set_transient( $longshot_feedback_skip_popup_name, DAY_IN_SECONDS );
						break;
					case 'submit':
						$rating = sanitize_text_field($_POST['rating']);
						update_option( 'longshot_ai_feedback_given', true );
						update_option( 'longshot_ai_feedback_message', $feedback );
						$this->post_request( $this->base_uri . '/save/wordpress/user_feedback', [
							'rating' => intval( $rating ),
							'message' => $feedback,
							'unique_id' => $this->unique_id, // Feedback is by user, not team
						] );
						break;
				}
				wp_send_json_success(['message' => 'Done']);
			} catch (Exception $e) {
				wp_send_json_error(['message' => $e->getMessage()]);
			}
		}

		private function fetch_semantic_score( $text, $topic, $country, $language ) {
			$url = $this->base_uri . '/semantic/score/content';

			return wp_remote_post( $url, array(
				'headers' => array(
					'Authorization' => 'Basic ' . $this->api_key,
					'Content-Type'  => 'application/json'
				),
				'body'    => json_encode( array(
					'text'      => $text,
					'topic'     => $topic,
					'country'   => $country,
					'language'  => $language,
					'unique_id' => $this->unique_id
				) )
			) );
		}

		private function calculate_semantic_grade( $scores ): string {
			$score = 0;
			foreach ( $scores as $query ) {
				$score += $query->scores;
			}
			$avg = $score / count( $scores );

			if ( $avg > 8 ) {
				return 'A+';
			}
			if ( $avg > 6 ) {
				return 'A';
			}
			if ( $avg > 4 ) {
				return 'B';
			}
			if ( $avg > 2.5 ) {
				return 'C';
			}

			return 'D';
		}

		private function post_request( string $url, array $body = [], array $headers = [] ) {
			$default_body    = array(
				'unique_id' => $this->auth_id
			);
			$default_headers = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . $this->api_key
			);
			$body            = array_merge( $default_body, $body );
			$headers         = array_merge( $default_headers, $headers );

			$response = wp_remote_post( $url, [
				'body'    => json_encode( $body ),
				'headers' => $headers
			] );

			self::log_request( $url, $body, 'POST', $response );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
				die;
			}
			$status_code         = wp_remote_retrieve_response_code( $response );
			$object              = json_decode( wp_remote_retrieve_body( $response ) );
			$object->status_code = $status_code;

			return $object;
		}

		private function get_request( $url, $params ) {
			$url      .= '?' . http_build_query( $params );
			$response = wp_remote_get( $url, array(
				'headers' => array(
					'Authorization' => 'Basic ' . $this->api_key
				)
			) );
			self::log_request( $url, [], "GET", $response );
			if ( is_wp_error( $response ) ) {
				wp_send_json_error( array( 'message' => $response->get_error_message() ) );
				die;
			}

			return json_decode( wp_remote_retrieve_body( $response ) );
		}

		static function log_request( string $url, array $body, string $method, $response ) {
			$log_string = "Sent $method request to $url";
			if ( $method == 'POST' ) {
				$log_string .= "\n\tParams: " . json_encode( $body );
			}
			$status_code        = wp_remote_retrieve_response_code( $response );
			$retrieved_response = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );
			$log_string         .= "\n\tStatus: $status_code";
			$log_string         .= "\n\tResponse: " . json_encode( $retrieved_response );

			Longshot_AI_Utils::get()->logger( $log_string );
		}
	}
