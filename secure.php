<?php

	final class Longshot_AI_Secure {
		private static $method = 'aes-256-ctr';
		private static $encryption_iv = '1234567890123456';


		private static function get_key(): string {
			$logged_in_key = defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : '';
			$logged_in_salt = defined( 'LOGGED_IN_SALT' ) ? LOGGED_IN_SALT : '';
			$salt = defined('LONGSHOT_ENCRYPTION_SALT') ? LONGSHOT_ENCRYPTION_SALT : '';

			return $logged_in_key . $logged_in_salt . $salt;
		}
		public static function encrypt($value): string {
			$key = self::get_key();
			$encryption = openssl_encrypt($value, self::$method, $key, 0, self::$encryption_iv);
			return base64_encode($encryption);
		}

		public static function decrypt($value): string {
			$key = self::get_key();
			$value = base64_decode($value);
			return openssl_decrypt($value, self::$method, $key, 0, self::$encryption_iv);
		}
	}
