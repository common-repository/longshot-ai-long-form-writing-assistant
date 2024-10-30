<?php

  require_once 'constants.php';
  require_once 'utils.php';
  require_once 'secure.php';

  final class LongshotAIRestApi {
    static $instance = null;
    static $utils = null;
	static $namespace = 'longshot-ai/v1';

    public function __construct() {
      self::$instance = $this;
      self::$utils = Longshot_AI_Utils::get();
      add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Register the REST API routes.
     */
    public function register_rest_routes() {
      register_rest_route(self::$namespace, '/publish-post', [
        'methods' => 'POST',
        'callback' => array($this, 'create_post'),
        'permission_callback' => function() { return true; }  // Allow public access
      ]);

	  register_rest_route(self::$namespace, '/test-user-authority', [
		'methods' => 'POST',
		'callback' => array($this, 'check_user'),
		'permission_callback' => function() { return true; }  // Allow public access
	  ]);
    }

    function create_post($request) {
      $api = Longshot_AI_Plugin::$api_handler;

      if (!$api->is_logged_in()) {
        return new WP_Error('longshot_ai_rest_api_error', 'You must be logged in to use this API.', array('status' => 401));
      }

      $param_maps = [
        [ 'field' => 'id', 'param' => 'ID' ], // Post ID ( If present will update the post)
        [ 'field' => 'author', 'param' => 'post_author' ],  // Default: current WordPress user
        [ 'field' => 'content', 'param' => 'post_content' ], // Post content in html
        [ 'field' => 'title', 'param' => 'post_title' ], // Post title
        [ 'field' => 'description', 'param' => 'post_excerpt' ], // Description
        [ 'field' => 'status', 'param' => 'post_status' ],  // Default: Draft
        [ 'field' => 'last_modified', 'param' => 'post_modified_gmt' ], // Post modified time
        [ 'field' => 'tags', 'param' => 'tags_input' ], // Array of tag names, slugs or IDs
        [ 'field' => 'meta', 'param' => 'meta_input' ], // Meta values for post
      ];

      $new_post = array();
      $params = $request->get_params();

	  $required_params = array('content', 'title');
	  foreach ($required_params as $param) {
		  if (!isset($params[$param])) {
			  return new WP_Error('longshot_ai_rest_api_error', 'Missing required parameter: ' . $param, ['status' => 400]);
		  }
	  }

      foreach ($param_maps as $param_map) {
        $field = $param_map['field'];
        $param = $param_map['param'];
        if (isset($params[$field])) {
          $new_post[$param] = $params[$field];
        }
      }
	  // Convert raw content to HTML
      $wp_html = new Longshot_AI_WpHTML(wp_filter_post_kses($params['content']));
      $content = $wp_html->getWpHTML();
	  // Replace all <br>, </br>, '<br/>' with '<br>'
	  $content = preg_replace('/<\/?br\/?>/', '<br>', $content);
      $content = wp_filter_post_kses($content);

      $new_post['post_content'] = $content;
	  $new_post['post_author'] = get_option('longshot_ai_author_id');

      $post_id = wp_insert_post($new_post, true);

      if (is_wp_error($post_id)) {
        self::$utils->logger('Error creating post: ' . $post_id->get_error_message());
        return new WP_Error('error', 'Error creating post: ' . $post_id->get_error_message(), array('status' => 400));
      }

      return [
        'message' => 'Post Published successfully',
        'post_id' => $post_id,
      ];
    }

	function check_user($request) {
		global $longshot_app_url;
		$allowed_domains = array($longshot_app_url);
		$domain = $request->get_header('origin');

		if (!in_array($domain, $allowed_domains)) {
			return new WP_Error('rest_no_route', 'No route was found matching the URL and request method.', array('status' => 404));
		}

		$unique_id = $request->get_param('unique_id');
		$connected_user = Longshot_AI_Secure::decrypt(get_option('longshot_ai_unique_id'));

		if ($connected_user != $unique_id) {
			return new WP_Error('longshot_ai_rest_api_error', 'User is not authorized to use this API.', array('status' => 401));
		}
		return [
			'message' => 'Account is connected to the plugin.',
			'site_name' => get_bloginfo('name'),
			'site_url' => get_bloginfo('url'),
		];
	}

    static function get() {
      if (self::$instance) {
        return self::$instance;
      }
      return new self();
    }
  }

  new LongshotAIRestApi();
