<?php

	final class Longshot_AI_Utils {
		private $semantic_seo_colors = [
			'a+'  => [ 'bg' => '#bfeab2', 'fg' => '#1e8200' ],
			'a'   => [ 'bg' => '#e9f8e5', 'fg' => '#56c732' ],
			'b'   => [ 'bg' => '#fff3cc', 'fg' => '#ffc400' ],
			'c'   => [ 'bg' => '#fff6e5', 'fg' => '#ff8800' ],
			'd'   => [ 'bg' => '#ffe5e5', 'fg' => '#ff0000' ],
			'n/a' => [ 'bg' => '#dedede', 'fg' => '#616161' ],
		];
		private $encoded_icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgICAgICAgdmlld0JveD0iMCAwIDE2MDAgMTMyNi42NjY3IgogICAgICAgIGhlaWdodD0iMTMyNi42NjY3IgogICAgICAgIHdpZHRoPSIxNjAwIgogICAgICAgIGZpbGw9IiNmZmYiCiAgICAgICAgdmVyc2lvbj0iMS4xIgo+CiAgICA8ZGVmcz4KICAgICAgICA8bGluZWFyR3JhZGllbnQKICAgICAgICAgICAgICAgIGlkPSJsaW5lYXJHcmFkaWVudDMwIgogICAgICAgICAgICAgICAgZ3JhZGllbnRUcmFuc2Zvcm09Im1hdHJpeCgwLDk1NS43NDc4Niw5NTUuNzQ3ODYsMCw2MDAsMTkuNjI2MDcpIgogICAgICAgICAgICAgICAgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiCiAgICAgICAgICAgICAgICB5Mj0iMCIKICAgICAgICAgICAgICAgIHgyPSIxIgogICAgICAgICAgICAgICAgeTE9IjAiCiAgICAgICAgICAgICAgICB4MT0iMCI+CiAgICAgICAgICAgIDxzdG9wCiAgICAgICAgICAgICAgICAgICAgaWQ9InN0b3AyNCIKICAgICAgICAgICAgICAgICAgICBvZmZzZXQ9IjAiCiAgICAgICAgICAgICAgICAgICAgc3R5bGU9InN0b3Atb3BhY2l0eToxO3N0b3AtY29sb3I6I2I1MDFmMiIvPgogICAgICAgICAgICA8c3RvcAogICAgICAgICAgICAgICAgICAgIGlkPSJzdG9wMjYiCiAgICAgICAgICAgICAgICAgICAgb2Zmc2V0PSIwLjgwNDMzNjk5IgogICAgICAgICAgICAgICAgICAgIHN0eWxlPSJzdG9wLW9wYWNpdHk6MTtzdG9wLWNvbG9yOiM2MjAwZWEiLz4KICAgICAgICAgICAgPHN0b3AKICAgICAgICAgICAgICAgICAgICBpZD0ic3RvcDI4IgogICAgICAgICAgICAgICAgICAgIG9mZnNldD0iMSIKICAgICAgICAgICAgICAgICAgICBzdHlsZT0ic3RvcC1vcGFjaXR5OjE7c3RvcC1jb2xvcjojNjIwMGVhIi8+CiAgICAgICAgPC9saW5lYXJHcmFkaWVudD4KICAgIDwvZGVmcz4KICAgIDxnCiAgICAgICAgICAgIHRyYW5zZm9ybT0ibWF0cml4KDEuMzMzMzMzMywwLDAsLTEuMzMzMzMzMywwLDEzMjYuNjY2NykiCiAgICAgICAgICAgIGlkPSJnMTAiPgogICAgICAgIDxnCiAgICAgICAgICAgICAgICBpZD0iZzEyIj4KICAgICAgICAgICAgPGcKICAgICAgICAgICAgICAgICAgICBpZD0iZzE0Ij4KICAgICAgICAgICAgICAgIDxnCiAgICAgICAgICAgICAgICAgICAgICAgIGlkPSJnMjAiPgogICAgICAgICAgICAgICAgICAgIDxnCiAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZD0iZzIyIj4KICAgICAgICAgICAgICAgICAgICAgICAgPHBhdGgKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZD0icGF0aDMyIgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGQ9Ik0gOTE0Ljc0Myw4ODAuMTQyIEMgOTE0LjU0LDg3OS45NzkgODkzLjk1Nyw4NjMuNDg0IDg3MS4wNDQsODM5LjYgdiAwIGwgLTc0LjUwNiwtMTAuOTU2IGMgLTAuODEyLC0wLjM5MSAtMS41NjYsLTAuNzU2IC0yLjM3OCwtMS4xNDYgdiAwIEwgNzM1LjIwNyw3NzIuMzggYyAtMC43NTgsLTAuNzE2IC0xLjIzNSwtMS42NiAtMS4zNTgsLTIuNjY5IHYgMCBjIC0wLjA1OCwtMC40MzUgLTAuMDQ4LC0wLjg2MSAwLjAzMywtMS4zMDYgdiAwIGMgMC4yNTYsLTEuNDY5IDEuMjI4LC0yLjcwMyAyLjU4NywtMy4yOTQgdiAwIGwgNzAuMDIxLC0zMC4zMDIgYyAtMS42MzUsLTkuNzkxIC0xLjI4MywtMTguOTc5IDAuNjczLC0yNy41NjYgdiAwIEwgNzE0LjY1MSw2MzUuNjY0IEggMjQ4LjU0NSBjIC00LjY1LDAgLTkuMjQ3LC0xLjA0OCAtMTMuNDM3LC0zLjA2OCB2IDAgbCAtOTUuNzYyLC00Ni4wMTMgYyAtMTMuMjYsMTEuMjM5IC0zMC4zOSwxOC4wNDkgLTQ5LjEzNCwxOC4wNDkgdiAwIGMgLTQyLjAyMSwwIC03Ni4wODMsLTM0LjA2MyAtNzYuMDgzLC03Ni4wOCB2IDAgYyAwLC00Mi4wMTggMzQuMDYyLC03Ni4wODIgNzYuMDgzLC03Ni4wODIgdiAwIGMgNDIuMDE0LDAgNzYuMDc2LDM0LjA2NCA3Ni4wNzYsNzYuMDgyIHYgMCBjIDAsMC43MDMgLTAuMDg1LDEuMzg0IC0wLjEwNiwyLjA4NCB2IDAgbCA4OS40MjgsNDIuOTY1IGggNDY5LjY0NSBjIDYuODczLDAgMTMuNTU1LDIuMjg3IDE4Ljk4NCw2LjQ4OCB2IDAgbCAxMDIuNTg4LDc5LjM3OSBjIDguMjIsLTIuOTUzIDIxLjM0MiwtNS45OCAzOS41MzUsLTQuOTQ5IHYgMCBsIDE3LjA1NSwtNzQuNTggYyAwLjMzMiwtMS40NTQgMS4zNzQsLTIuNjMzIDIuNzY5LC0zLjE0OCB2IDAgYyAxLjM4OSwtMC41MjQgMi45NTEsLTAuMjk0IDQuMTUzLDAuNTg4IHYgMCBsIDY0Ljk1NSw0Ny45MDIgYyAwLjU0NSwwLjc3IDEuMDU4LDEuNDgzIDEuNjA0LDIuMjU0IHYgMCBsIDIyLjE1OSw3MS4wNjYgYyAxNy41NDEsMTEuNjkyIDM1LjU1MywyNS41OTggNTMuNTY5LDQxLjM0MiB2IDAgYyAxMDMuNzE2LDkwLjY1OSAxMjcuMzI5LDE2OC43NjQgMTMyLjE3NiwyMDcuMjQ4IHYgMCBjIDEuNiwxMi42NjcgMC45MzYsMTkuODQ0IDAuOTE1LDIwLjE0NyB2IDAgYyAtMC4xNzEsMS43OCAtMS40MDEsMy4yNzcgLTMuMTExLDMuODAxIHYgMCBjIC0xLjUwMiwwLjQ1NiAtMTQuNjY0LDQuMjM1IC0zNy41NzMsNC4yMzUgdiAwIGMgLTQ0LjU3NSwwIC0xMjYuMDQ2LC0xNC4zMDggLTIzMC4yOSwtOTUuMjMyIG0gNjUuNSwtMTA1LjMzNyBjIC0xOC4yMzcsNS4xODggLTMxLjUxOSwyMi4zIC0zMi4wMjYsNDEuMjU0IHYgMCBjIC0wLjgwNywzMC40MTUgMjcuNzA4LDUyLjU1MiA1Ni45NzUsNDQuMjI3IHYgMCBjIDE4LjIzNywtNS4xOTEgMzEuNTE4LC0yMi4zMDEgMzIuMDIxLC00MS4yNTQgdiAwIGMgMC42OTYsLTI2LjAzMSAtMjAuMDkxLC00NS45OTUgLTQ0LjQ0LC00NS45OTUgdiAwIGMgLTQuMTA0LDAgLTguMzA5LDAuNTY3IC0xMi41MywxLjc2OCBtIC01OTYuMzU4LDQ1LjUyIEggMzUzLjMwMiAyNDguNTQ1IGMgLTE3LjEzNiwwIC0zMS4wMzIsLTEzLjg5NiAtMzEuMDMyLC0zMS4wMzEgdiAwIGMgMCwtMTcuMTM2IDEzLjg5NiwtMzEuMDMyIDMxLjAzMiwtMzEuMDMyIHYgMCBoIDEwNC43NTcgMzAuNTgzIDIxMC4yNzMgYyAxNy4xMzUsMCAzMS4wMzIsMTMuODk2IDMxLjAzMiwzMS4wMzIgdiAwIGMgMCwxNy4xMzUgLTEzLjg5NywzMS4wMzEgLTMxLjAzMiwzMS4wMzEgdiAwIHogTSA4NTkuMjA1LDQ1MS4wMDMgSCA4MzAuNjIyIDI0OC41NDUgYyAtMTcuMTM2LDAgLTMxLjAzMiwtMTMuODk3IC0zMS4wMzIsLTMxLjAzMiB2IDAgYyAwLC0xNy4xMzYgMTMuODk2LC0zMS4wMzIgMzEuMDMyLC0zMS4wMzIgdiAwIGggNTgyLjA3NyAyOC41ODMgMTUzLjI2NyBjIDE3LjEzNSwwIDMxLjAzMiwxMy44OTYgMzEuMDMyLDMxLjAzMiB2IDAgYyAwLDE3LjEzNSAtMTMuODk3LDMxLjAzMiAtMzEuMDMyLDMxLjAzMiB2IDAgeiBNIDI0OC41NDUsMjY2LjM0MSBjIC0xNy4xMzYsMCAtMzEuMDMyLC0xMy44OTcgLTMxLjAzMiwtMzEuMDMyIHYgMCBjIDAsLTE3LjEzNiAxMy44OTYsLTMxLjAzMiAzMS4wMzIsLTMxLjAzMiB2IDAgaCA3NjMuOTI3IGMgMTcuMTM1LDAgMzEuMDMyLDEzLjg5NiAzMS4wMzIsMzEuMDMyIHYgMCBjIDAsMTcuMTM1IC0xMy44OTcsMzEuMDMyIC0zMS4wMzIsMzEuMDMyIHYgMCB6IG0gMCwtMTg0LjY1MiBjIC0xNy4xMzYsMCAtMzEuMDMyLC0xMy44OTYgLTMxLjAzMiwtMzEuMDMyIHYgMCBjIDAsLTE3LjEzNCAxMy44OTYsLTMxLjAzMSAzMS4wMzIsLTMxLjAzMSB2IDAgaCAzNDUuNjEzIGMgMTcuMTM1LDAgMzEuMDMyLDEzLjg5NyAzMS4wMzIsMzEuMDMxIHYgMCBjIDAsMTcuMTM2IC0xMy44OTcsMzEuMDMyIC0zMS4wMzIsMzEuMDMyIHYgMCB6Ii8+CiAgICAgICAgICAgICAgICAgICAgPC9nPgogICAgICAgICAgICAgICAgPC9nPgogICAgICAgICAgICA8L2c+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4K';
		private static $instance;
	    private $log_file = __DIR__ . '/test.log';
	    private $log_enabled = false;   // Disable logger in production

		function __construct() {
			self::$instance = $this;
		}

		static function get(): self {
			if ( self::$instance == null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function get_semantic_seo_color( $grade ): array {
			$grade = strtolower( $grade );
			// If the grade is not in the array, return the default values.
			if ( ! array_key_exists( $grade, $this->semantic_seo_colors ) ) {
				return $this->semantic_seo_colors['n/a'];
			}

			return $this->semantic_seo_colors[ $grade ];
		}

		function get_semantic_seo_grade( float $score ): string {
			// Also update get_semantic_seo_grade in src/utils.ts, if updated here
			if ( $score > 8 ) {
				return 'A+';
			}
			if ( $score > 6 ) {
				return 'A';
			}
			if ( $score > 4 ) {
				return 'B';
			}
			if ( $score > 2.5 ) {
				return 'C';
			}

			return 'D';
		}

		/**
		 * @return string
		 */
		public function get_encoded_icon(): string {
			return $this->encoded_icon;
		}

		/**
		 * Logger
		 *
		 * @param string | array $message
		 * @param string $type
		 * @param $file
		 *
		 * @return void
		 */
		public function logger( $message, string $type = 'info', $file = null ) {
			if ( ! $this->log_enabled ) {
				return;
			}
			// If file is not present, create the file
			if ( ! is_null( $file ) && ! file_exists( $file ) ) {
				mkdir( $file, 0777, true );
			}

			if ( ( $file === null ) || ( $file === '' ) ) {
				$file = $this->log_file;
			}
			if ( is_array( $message ) ) {
				$message = json_encode( $message );
			}
			$log = date( 'Y-m-d H:i:s' ) . ' [' . $type . '] ' . $message . PHP_EOL;
			file_put_contents( $file, $log, FILE_APPEND );
		}
	}

	final class Longshot_AI_WpHTML {
		// Stores instance of dom document
		private $dom;
		private $ID = 'longshot-ai-post-content'; // Unique identifier

		function __construct( string $html ) {
			$html = stripslashes( $html );

			$this->dom                     = new DOMDocument();
			$this->dom->preserveWhiteSpace = false;

			// Adding meta is necessary as to avoid errors in the DOMDocument
			// ID is added to div as to get full html from the request
			$this->dom->loadHTML( "<meta http-equiv='Content-Type' content='charset=utf-8' /><div id='$this->ID'>$html</div>" );
		}

		/**
		 * Get wp block attributes for given html tag
		 *
		 * @param $tag
		 * @param $style
		 *
		 * @return array|null
		 */
		function getWpBlock( $tag, $style ) {
			// Return type:
			// [ block-name, block attributes, ?html element attributes ] || null
			switch ( $tag ) {
				case 'blockquote':
					return [ 'quote', [], [ 'class' => 'wp-block-quote' ] ];
				case 'p':
					$align = 'left';

					$style_array = explode( ';', $style );
					foreach ( $style_array as $style_item ) {
						$style_item = explode( ': ', $style_item );
						Longshot_AI_Utils::get()->logger( $style_item );
						if ( $style_item[0] === 'text-align' ) {
							$align = trim($style_item[1]);
							break;
						}
					}

					Longshot_AI_Utils::get()->logger( print_r($style_array, 1) . ' ' . $align );

					return [ 'paragraph', [ 'align' => $align ] ];
				case 'h1':
				case 'h2':
				case 'h3':
				case 'h4':
				case 'h5':
				case 'h6':
					return [ 'heading', [ 'level' => intval( $tag[1] ) ] ];
				case 'ul':
					return [ 'list', [] ];
				case 'ol':
					return [ 'list', [ "ordered" => true ] ];
				case 'hr':
					return [ 'separator', [] ];
				case 'div':
					return [ 'group', [] ];
				default:
					return null;
			}
		}

		/**
		 * Get the HTML of an element in block format
		 *
		 * @param $node
		 * @param $parent
		 *
		 * @return mixed|string
		 */
		function getWpBlockContent( $node, $parent = null ) {
			$tag = $node->nodeName;
			if ( ! $tag ) {
				return '';
			}
			if ( $tag === '#text' ) {
				return $node->nodeValue;
			}

			// list-item must contain only text or other list
			if ( $parent == 'li' && ! in_array( $parent, [ 'ul', 'ol' ] ) ) {
				$tag = null;
			}

			$block   = $this->getWpBlock( $tag, $node->getAttribute( 'style' ) );
			$content = '';

			// Child of blockquotes are not block
			if ( $parent == 'blockquote' ) {
				$block = null;
			}

			if ( $block ) {
				$content .= "<!-- wp:$block[0] " . ( count( $block[1] ) === 0 ? '' : json_encode( $block[1] ) ) . " -->\n";
			}
			if ( $tag ) {
				$content .= "<$tag";

				if ( $block && count( $block ) === 3 ) {
					foreach ( $block[2] as $key => $value ) {
						$content .= " $key=\"$value\"";
					}
				}

				$content .= ">";
			}

			$children = $node->childNodes;
			if ( count( $children ) ) {
				foreach ( $children as $child ) {
					// Recurse for child nodes
					$content .= $this->getWpBlockContent( $child, $tag ?? $parent );
				}
			} else {
				$content .= $node->nodeValue;
			}
			if ( $tag ) {
				$content .= "</$tag>\n";
			}
			if ( $block ) {
				$content .= "<!-- /wp:$block[0] -->\n";
			}

			return $content;
		}

		/**
		 * Get wrapper around the content
		 * @return DOMElement|null
		 */
		function get_dom() {
			return $this->dom->getElementById( $this->ID );
		}

		/**
		 * Get html in block format
		 * @return string
		 */
		function getWpHTML(): string {
			$wrapper = $this->get_dom();
			$content = '';
			foreach ( $wrapper->childNodes as $child ) {
				$content .= $this->getWpBlockContent( $child );
			}

			return $content;
		}
	}

	new Longshot_AI_Utils();
