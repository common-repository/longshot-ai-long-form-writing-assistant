<?php
    require_once __DIR__ . '/../constants.php';
    global $longshot_app_url;

    function redirect_to_login() {
        // Add script to reload the page, as to clear out _GET Params
        // Ref: https://wordpress.stackexchange.com/a/359059
        echo "<script>location.href = '" . esc_url(admin_url('admin.php?page=longshot-ai')) . "';</script>";
        die;
    }

    $app_url = $longshot_app_url;

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		if ( isset( $_POST['logout'] ) ) {
			Longshot_AI_Plugin::logout();
		}
        redirect_to_login();
		// Nothing happens if request is post and not logout
	}

	// GET Request
	if ( isset( $_GET['longshot-connect'] ) ) {
		// Authentication request
        if (!isset( $_GET['nonce'] )) {
            set_transient('longshot_ai_login_message', 'Invalid nonce');
            redirect_to_login();
        }
		$nonce = sanitize_text_field($_GET['nonce']);
		if ( ! wp_verify_nonce( $nonce, 'longshot-ai-login' ) ) {
            set_transient('longshot_ai_login_message', 'Expired login link. Please try again.');
            redirect_to_login();
		}
		$unique_id = sanitize_text_field($_GET['unique_id']);
		$api_key   = sanitize_text_field($_GET['api_key']);

		if ( empty( $unique_id ) || empty( $api_key ) ) {
            set_transient('longshot_ai_login_message', 'Invalid request');
            redirect_to_login();
		}

		$content = Longshot_AI_Plugin::authorize_user( $api_key, $unique_id );
        set_transient('longshot_ai_login_message', $content['message']);
        redirect_to_login();
	}

	$message = '';
	if ( get_transient('longshot_ai_login_message') !== false ) {
		$message = get_transient('longshot_ai_login_message');
        delete_transient('longshot_ai_login_message');
	}
	$connected = Longshot_AI_Plugin::$api_handler->is_logged_in();
	$user      = [];

	if ( $connected ) {
		$user = Longshot_AI_Plugin::$api_handler->get_current_user();
        $team_details = null;
		if ( isset( $user['logged_in'] ) && ! $user['logged_in'] ) {
			$connected = false;
			if ( empty( $message ) ) {
				$message = $user['message'];
			}
		}
        if (Longshot_AI_Plugin::$api_handler->is_team_member()) {
            $team_details = Longshot_AI_Plugin::$api_handler->get_team_credits();
            if ( isset( $team_details['logged_in'] ) && ! $team_details['logged_in'] ) {
              $connected = false;
              if ( empty( $message ) ) {
                $message = "Unable to get team details <br> " . $team_details['message'];
              }
            }
        }
	}
?>
<main class="longshot-ai">
    <header>
        <div class="image-wrapper">
            <a href="https://longshot.ai?referrer=wordpress&site=<?php echo urlencode( get_site_url() ); ?>"
               target="_blank">
                <img src="<?php echo plugins_url( "assets/longshot.png", LONGSHOT_AI_PLUGIN_FILE ); ?>" alt=""/>
            </a>
        </div>
    </header>
    <div id="lsai-container">
        <section class="login-card card">
            <div class="header">
                <h3>Account</h3>
                <?php
                    echo
                    $connected
                        ? "<div class='tag tag-green'><span class='dashicons dashicons-yes'></span>Connected</div>"
                        : "<div class='tag tag-red'><span class='dashicons dashicons-no'></span>Not connected</div>"
                ?>
            </div>
            <div class="content">
                <?php
                    if ( $connected ) {
                        $extension_allowed = Longshot_AI_Plugin::$api_handler->is_extension_allowed();
                        if ($extension_allowed) {
                        ?>
                        <p>
                            You are now connected to Longshot.ai. Now you can use Longshot.ai's capabilities directly from the WordPress Editor.
                        </p>
                      <?php
                        } else {
                          ?><p>
                              <strong>
                                  Longshot.ai WordPress Plugin is not available with your current plan. Please upgrade to
                                    a paid plan to use the plugin.
                              </strong>
                            </p>
                          <?php
                          }
                      ?>
                        <form method="post">
                            <input type="submit" value="Disconnect Account" name="logout"
                                   class="button-primary button button-danger"/>
                          <?php
                            if(!$extension_allowed) {
                          ?>
                            <a href="<?php echo esc_url($app_url); ?>/pricing" class="button button-primary" target="_blank">Buy Plan</a>
                            <?php
                            }
                            ?>
                        </form>
                        <?php
                    } else {
                        ?>
                        <p>
                            Connect your longshot account to start using the longshot.ai features within your WordPress
                            Editor.
                        </p>
                        <?php
                        $login_url    = admin_url( 'admin.php' );
                        $site_url     = get_bloginfo( 'url' );
                        $redirect_url = "$app_url/account/plugins";
                        $nonce        = wp_create_nonce( 'longshot-ai-login' );

                        $redirect_url .= '?' . http_build_query( [
                                'site_url'     => $site_url,
                                'nonce'        => $nonce,
                                'callback_url' => $login_url
                            ] );
                        if ( ! empty( $message ) ) {
                            ?>
                            <p class="message">
                                Error: <?php echo esc_html($message); ?>
                            </p>
                            <?php
                        }
                        ?>
                        <a href="<?php echo esc_url($redirect_url); ?>" target="_blank" class="button-primary button connect-button">Connect to
                            Longshot</a>
                        <?php
                    }
                    $msg = LONGSHOT_AI_PLUGIN::$api_handler->get_persistent_message();
                    if ( ! empty( $msg ) ) {
                        ?>
                        <p class="message">
                            <?php echo esc_html($msg); ?>
                        </p>
                        <?php
                    }
                ?>
            </div>
        </section>
        <div class="more-items-container <?php echo $connected ? 'three' : 'two' ?>">
		<?php
			if ( $connected ) {
				?>
                <section class="card profile-card">
                    <div class="header">
                        <h3>Profile</h3>
                    </div>
                    <ul class="content">
						<?php
                            $plan = '';
                            if (isset($user['plan_name'])) {
                                $plan = $user['plan_name'];
                                $plan = str_replace('-', ' ', $plan);
                                $plan = str_replace('longshot ai', '', $plan);
                                $plan = preg_replace('/\s\S+$/', '', $plan);
                                $plan = ucwords($plan);
                            }
							$details = [ [ 'headline' => 'Account Email', 'value' => $user['email'] ] ];

                            if (Longshot_AI_Plugin::$api_handler->is_team_member()) {
                                $details[] = [ 'headline' => 'Team Credits Used', 'value' => $team_details['team_credits_used']];
                                $details[] = [ 'headline' => 'Team Credits Limit', 'value' => $team_details['team_credit_limit']];
                            } else {
                                $details[] = [ 'headline' => 'Credits Used', 'value' => $user['credits_used'] ];
								$details[] = [ 'headline' => 'Credits Limit', 'value' => $user['credit_limit'] ];
                            }

                            $details[] = [ 'headline' => 'Current Plan', 'value' => $plan ];

							foreach ( $details as $detail ) {
								if ( empty( $detail['value'] ) ) {
									continue;
								}
								?>
                                <li>
                                    <span class="headline"><?php echo esc_html($detail['headline']); ?></span>
                                    <span class="value"><?php echo esc_html($detail['value']); ?></span>
                                </li>
								<?php
							}
						?>
                    </ul>
                </section>
				<?php
			}
		?>
            <section class="card more-from-longshot">
                <div class="header">
                    <h3>More from LongShot...</h3>
                </div>
                <ul class="content">
                    <?php
                        $products = [
                            [ 'link'     => 'https://app.longshot.ai',
                              'headline' => 'LongShot AI App',
                              'value'    => 'Home to longshot.ai with a powerful editor',
                              'icon'     => 'welcome-widgets-menus'
                            ],
                            [ 'link'     => 'https://chrome.google.com/webstore/detail/longshot-ai-long-form-wri/llmkblcpjmcomjoanblldplmaghaopbd',
                              'headline' => 'Chrome Extension',
                              'value'    => 'An extension for using longshot.ai on any website.',
                              'icon'     => 'admin-tools'
                            ],
                            [ 'link'     => 'https://www.longshot.ai/integrations/semrush',
                              'headline' => 'Semrush Integration',
                              'value'    => 'Receive data-driven SEO recommendations to help you generate search-friendly long-form content',
                              'icon'     => 'admin-tools'
                            ]
                        ];
                        foreach ( $products as $product ) {
                            ?>
                            <li class="has-icon">
                                <span class="dashicons dashicons-<?php echo esc_attr($product['icon']); ?>"></span>
                                <a href="<?php echo esc_url($product['link']); ?>" target="_blank">
                                    <span class="headline"><?php echo esc_html($product['headline']); ?></span>
                                    <span class="value"><?php echo esc_html($product['value']); ?></span>
                                </a>
                            </li>
                            <?php
                        }
                    ?>
                </ul>
            </section>
            <section class="card support-cart">
                <div class="header">
                    <h3>Help & Support</h3>
                </div>
                <ul class="content">
                    <?php
                        $contact_mail = 'info@longshot.ai';
                        $details      = [
                            [ 'icon'     => 'video-alt3',
                              'headline' => 'Create a 1000 word blog',
                              'link'     => 'https://youtu.be/JcXBXiX-wFQ'
                            ],
                            [
                              'icon' => 'welcome-write-blog',
                              'headline' => 'Longshot Recipes',
                              'link' => 'https://help.longshot.ai/longshot-content-recipes/f6Y5hjGUoYi5Fgxe1XpGqZ'
                            ],
                            [ 'icon'     => 'editor-help',
                              'headline' => 'Get help with LongShot.ai',
                              'link'     => 'https://help.longshot.ai'
                            ],
                            [ 'icon'     => 'email',
                              'headline' => 'Have a question?',
                              'link'     => "mailto:$contact_mail",
                              'value'    => "Email us at $contact_mail"
                            ]
                        ];
                        foreach ( $details as $detail ) {
                            ?>
                            <li class="has-icon">
                                <span class="dashicons dashicons-<?php echo esc_attr($detail['icon']) ?>"></span>
                                <a href="<?php echo esc_url($detail['link']); ?>" target="_blank">
                                    <span class="headline"><?php echo esc_html($detail['headline']); ?></span>
                                    <?php echo isset($detail['value']) ? "<span class='value'>".esc_html($detail['value'])."</span>" : ""; ?>
                                </a>
                            </li>
                            <?php
                        }
                    ?>
                </ul>
            </section>
        </div>
    </div>
</main>
