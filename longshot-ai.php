<?php
	/**
	 * Plugin Name: LongShot AI - Long Form Writing Assistant
	 * Description: LongShot is an AI writing assistant that helps research, generate, and optimize long-form content. With a comprehensive list of features, you can say goodbye to writer's block and hello to productivity. Writing blogs will never feel this effortless.
	 * Version: 2.0.0
	 * Requires PHP: 7.0
	 * Author: LongShot AI
	 * Author URI: https://longshot.ai
	 * License: GPL2.0-or-later
	 */

	// Prevent direct access to script
	defined( 'ABSPATH' ) or die( 'Sorry, you are not allowed to access this page directly.' );
	require_once 'api.php';
	require_once 'secure.php';
	require_once 'utils.php';
	require_once 'constants.php';
	require_once 'rest.php';

	if ( ! class_exists( 'Longshot_AI_Plugin' ) ) {
		final class Longshot_AI_Plugin {
			static $instance;
			static $api_handler;
			static $utils;

			function __construct() {
				self::$instance    = $this;
				self::$api_handler = new LongshotAI_api_handler();
				self::$utils       = Longshot_AI_Utils::get();
			}

			/**
			 * Init all handlers
			 */
			function register_services() {
				// Load constants
				$this->load_constants();
				LongshotAIRestApi::get();

				// Add meta fields to post
				add_action( 'init', array( $this, 'add_meta_fields' ) );

				// Add action links in admin
				add_filter( 'plugin_action_links_' . LONGSHOT_AI_PLUGIN_NAME, array( $this, 'add_action_links' ) );

				// Register javascript file
				add_action( 'admin_init', array( $this, 'register_scripts' ) );

				// Add icons to <head>
				add_action( 'admin_enqueue_scripts', array( $this, 'add_mdi_icons' ) );

				// Add admin menu
				add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

				// Add custom column in post list
				add_filter( 'manage_posts_columns', array( $this, 'add_custom_columns' ), 10, 1 );

				// Render custom columns
				add_action( 'manage_post_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );

				// Send javascript to the frontend
				add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_scripts' ) );

				// Style post list
				add_action( 'admin_enqueue_scripts', array( $this, 'post_list_styles' ) );

				// Save semantic score on post save
				add_action( 'save_post', array( self::$api_handler, 'save_scores' ), 10, 2 );

				// Add meta description to post
				add_action( 'wp_head', array( $this, 'add_meta_description' ), 0 );

				self::$api_handler->register_services();
			}

			/**
			 * Load global level constants
			 */
			function load_constants() {
				define( 'LONGSHOT_AI_PLUGIN_FILE', __FILE__ ); // Plugin file
				define( 'LONGSHOT_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // Plugin directory
				define( 'LONGSHOT_AI_PLUGIN_NAME', plugin_basename( LONGSHOT_AI_PLUGIN_FILE ) ); // Plugin name
			}

			/**
			 * Add custom meta fields to post
			 */
			function add_meta_fields() {
				global $longshot_post_meta_keys;
				foreach ( $longshot_post_meta_keys as $field ) {
					register_post_meta( 'post', "longshot_ai_" . $field["name"], [
						'show_in_rest' => true,
						'single'       => true,
						'type'         => $field["type"]
					] );
				}
			}

			/**
			 * Add transient for not showing the feedback popup
			 */
			function skip_feedback_transient() {
				global $longshot_show_popup_after, $longshot_feedback_skip_popup_name;

				set_transient( $longshot_feedback_skip_popup_name, true, $longshot_show_popup_after );
			}

			private function show_feedback(): bool {
				global $longshot_feedback_skip_popup_name;

				if ( ( get_option( 'longshot_ai_feedback_dismissed' ) != '' || get_option( 'longshot_ai_feedback_given' ) != '' ) ) {
					return false;
				}

				if ( get_transient( $longshot_feedback_skip_popup_name ) != '' ) {
					return false;
				}

				return true;
			}

			/**
			 * Add action links in the plugins panel
			 */
			function add_action_links( $links ): array {
				$my_links = array(
					"<a href='https://longshot.ai'>About</a>",
					"<a href='https://help.longshot.ai'>Support</a>",
					"<a href='" . admin_url( 'admin.php?page=longshot-ai' ) . "'>Settings</a>"
				);

				return array_merge( $links, $my_links );
			}

			/**
			 * Add material design icons to related pages
			 */
			function add_mdi_icons( $hook ) {
				$allowed_pages = [ "toplevel_page_longshot", "edit.php", "post.php", "post-new.php" ];

				if ( ! in_array( $hook, $allowed_pages ) ) {
					return;
				}
				// Add material design icons
				wp_enqueue_style( 'longshot-ai-mdi-icons', plugin_dir_url( __FILE__ ) . 'templates/styles/mdi.min.css', array() );
			}

			/**
			 * Add Menu to main sidebar
			 */
			function add_admin_menu() {
				global $submenu;
				wp_enqueue_style( 'longshot-ai-admin-styles' );
				add_menu_page(
					'Longshot AI Dashboard',
					'Longshot AI',
					'manage_options',
					'longshot-ai',
					array( $this, 'admin_page' ),
					self::$utils->get_encoded_icon(),
					3
				);
				add_submenu_page(
					'longshot-ai',
					'Longshot AI Settings',
					'Settings',
					'manage_options',
					'longshot-ai',
					array( $this, 'admin_page' )
				);

				$links        = [
					[ 'title' => 'Help & Support', 'url' => 'https://help.longshot.ai', 'icon' => 'external' ]
				];
				$profile_link = [
					'title' => 'Manage Account',
					'url'   => 'https://app.longshot.ai/account/profile',
					'icon'  => 'external'
				];
				if ( self::$api_handler->is_logged_in() ) {
					$submenu['longshot-ai'][] = [
						$profile_link['title'] . " <span style='font-size: 14px; vertical-align: -2px;' class='dashicons dashicons-" . $profile_link['icon'] . "'></span>",
						'manage_options',
						$profile_link['url']
					];
				}
				foreach ( $links as $link ) {
					$submenu['longshot-ai'][] = [
						$link['title'] . " <span style='font-size: 14px; vertical-align: -2px;' class='dashicons dashicons-" . $link['icon'] . "'></span>",
						'manage_options',
						$link['url']
					];
				}
				$submenu['longshot-ai'][0][0] = 'Dashboard <span style="font-size: 14px; vertical-align: -2px;" class="dashicons dashicons-admin-home"></span>';
			}

			function admin_page() {
				require_once LONGSHOT_AI_PLUGIN_DIR . 'templates/admin-page.php';
			}

			/**
			 * Add custom column in post list
			 */
			function add_custom_columns( $columns ): array {
				if ( ! self::$api_handler->is_extension_allowed() ) {
					return $columns;
				}

				$semantic_seo_help_link = "https://help.longshot.ai/features/spugYdxTqyRTMdsYUsbziu/what%E2%80%99s-the-semantic-seo-feature/t3M97bu1QsJ85SH2ZfAmXq";

				return array_merge(
					$columns,
					array(
						'semantic-seo' => __(
							'<div style="text-align: center">
                <span>Semantic SEO</span>
                <a href="' . $semantic_seo_help_link . '" target="_blank">
                    <span class="mdi mdi-information" title="What is Semantic SEO"></span>
                </a>
            </div>',
							'textdomain' )
					) );
			}

			/**
			 * Render custom column based on it's value
			 */
			function render_custom_columns( $column, $post_id ) {
				if ( ! self::$api_handler->is_extension_allowed() ) {
					return;
				}
				if ( $column == 'semantic-seo' ) {
					$score = get_post_meta( $post_id, 'longshot_ai_semantic_seo_score', true );
					$title = "Semantic SEO Score: $score";
					if ( empty( $score ) ) {
						$score = 'N/A';
						$title = 'Not calculated yet';
					}
					$color = self::$utils->get_semantic_seo_color( $score );
					$style = "background-color: " . $color['bg'] . "; color: " . $color['fg'];
					echo "<span class='semantic-seo-score' data-tooltip='$title' style='$style'>" . esc_html( $score ) . "</span>";
				}
			}

			/**
			 * Add default options when plugin is activated
			 */
			function add_default_options() {
				global $longshot_options;
				foreach ( $longshot_options as $opt ) {
					if ( ! get_option( $opt ) ) {
						add_option( "longshot_ai_" . $opt );
					}
				}
				// Add transient for not showing the welcome page
				add_action( 'admin_init', array( $this, 'skip_feedback_transient' ) );
			}

			/**
			 * Register static assets
			 */
			function register_scripts() {
				global $longshot_feedback_skip_popup_name;
				$asset_file = include( LONGSHOT_AI_PLUGIN_DIR . 'build/index.asset.php' );
				wp_register_script(
					'longshot-ai-js-frontend',
					plugins_url( '/build/index.js', LONGSHOT_AI_PLUGIN_FILE ),
					$asset_file['dependencies'],
					$asset_file['version'] );
				wp_register_style( 'longshot-ai-css-frontend', plugins_url( '/build/index.css', LONGSHOT_AI_PLUGIN_FILE ) );
				wp_register_style( 'longshot-ai-post-list-css', plugins_url( '/build/post-list.css', LONGSHOT_AI_PLUGIN_FILE ) );
				wp_register_style( 'longshot-ai-admin-styles', plugins_url( '/templates/styles/login-page.css', LONGSHOT_AI_PLUGIN_FILE ) );


				$show_feedback = $this->show_feedback();

				// Send the plugin specific data to the frontend
				wp_localize_script( 'longshot-ai-js-frontend', 'longshot_ai_ajax_object', array(
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'plugins_url'   => plugins_url( '', LONGSHOT_AI_PLUGIN_FILE ),
					'show_feedback' => $show_feedback
				) );
			}

			/**
			 * Send static assets to the frontend
			 */
			function enqueue_scripts() {
				if ( ! self::$api_handler->is_extension_allowed() ) {
					return;
				}
				wp_enqueue_script( 'longshot-ai-js-frontend' );
				wp_enqueue_style( 'longshot-ai-css-frontend' );
			}

			function post_list_styles( $page ) {
				if ( $page !== 'edit.php' ) {
					return;
				}
				if ( ! self::$api_handler->is_extension_allowed() ) {
					return;
				}
				wp_enqueue_style( 'longshot-ai-post-list-css' );
			}

			function add_meta_description() {
				if ( ! self::$api_handler->is_extension_allowed() ) {
					return;
				}
				if ( ! is_singular( "post" ) ) {
					return;
				}

				$post_id = get_the_ID();
				// Get post meta
				$meta = get_post_meta( $post_id, 'longshot_ai_meta_description', true );
				// If meta is not empty, use it
				if ( ! empty( $meta ) ) {
					echo "<meta name='description' content='" . esc_attr( $meta ) . "'>";
				}
				// FIXME: multiple meta description tags due to various plugins
			}

			/**
			 * Authorize users with the Longshot API
			 */
			static function authorize_user( $api_key, $unique_id ): array {
				$url = self::$api_handler->base_uri . '/fetch/user/details/wordpress_plugin';

				$res = wp_remote_get( $url, [
					'headers' => [
						'Authorization' => 'Basic ' . $api_key,
						'Content-Type'  => 'application/json',
					],
					'body'    => [ 'unique_id' => $unique_id ],
				] );

				if ( is_wp_error( $res ) ) {
					return [
						'connected' => false,
						'message'   => $res->get_error_message(),
					];
				}

				$status  = wp_remote_retrieve_response_code( $res );
				$content = json_decode( wp_remote_retrieve_body( $res ), true );
				if ( $status != 200 ) {
					return [
						'connected' => false,
						'message'   => $content['detail'] ?? 'Invalid API key or unique ID'
					];
				}

				update_option( 'longshot_ai_api_key', Longshot_AI_Secure::encrypt( $api_key ) );
				update_option( 'longshot_ai_unique_id', Longshot_AI_Secure::encrypt( $unique_id ) );

				$extension_allowed = false;
				$auth_key          = $unique_id;

				if ( isset( $content['user_extension_allowed'] ) ) {
					$extension_allowed = wp_validate_boolean( $content['user_extension_allowed'] );
				}
				if ( ! $extension_allowed && isset( $content['team_extension_allowed'] ) ) {
					$extension_allowed = wp_validate_boolean( $content['team_extension_allowed'] ) && wp_validate_boolean( $content['plan_details']['basic_integrations'] );
					$auth_key          = sanitize_text_field( $content['team_admin_id'] );
				}
				update_option( 'longshot_ai_extension_allowed', Longshot_AI_Secure::encrypt( $extension_allowed ) );
				update_option( 'longshot_ai_auth_id', Longshot_AI_Secure::encrypt( $auth_key ) );
				self::$api_handler->reload_keys();
				update_option( 'longshot_ai_author_id', get_current_user_id() );

				return [ 'connected' => true, 'message' => 'You are all set to use longshot with wordpress' ];
			}

			/**
			 * Logout
			 */
			static function logout() {
				global $longshot_options;
				foreach ( $longshot_options as $opt ) {
					update_option( "longshot_ai_" . $opt, '' );
				}
				self::$api_handler->reload_keys();
			}

			/**
			 * Function to get singleton instance of class
			 */
			static function get_instance(): self {
				if ( ! self::$instance ) {
					self::$instance = new self();
				}

				return self::$instance;
			}
		}

		// Initialize plugin
		new Longshot_AI_Plugin();
	}

	$instance = Longshot_AI_Plugin::get_instance();
	$instance->register_services();

	register_activation_hook( __FILE__, array( $instance, 'add_default_options' ) );
