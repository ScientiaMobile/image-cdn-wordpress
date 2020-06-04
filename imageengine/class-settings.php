<?php
/**
 * This file contains the Settings class
 *
 * @package ImageCDN
 */

namespace ImageEngine;

/**
 * The Settings class manages all of the validation and administration of the plugin settings.
 */
class Settings {


	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting( 'image_cdn', 'image_cdn', array( self::class, 'validate_settings' ) );
	}


	/**
	 * Validation of settings.
	 *
	 * @param   array $data  array with form data.
	 * @return  array         array with validated values.
	 */
	public static function validate_settings( $data ) {
		if ( ! isset( $data['relative'] ) ) {
			$data['relative'] = 0;
		}

		if ( ! isset( $data['https'] ) ) {
			$data['https'] = 0;
		}

		$data['url'] = rtrim( $data['url'], '/' );

		$parts = wp_parse_url( $data['url'] );
		if ( ! isset( $parts['scheme'] ) || ! isset( $parts['host'] ) ) {
			add_settings_error( 'url', 'url', 'Invalid URL: Missing scheme (<code>http://</code> or <code>https://</code>) or hostname' );
		} else {

			// Make sure there is a valid scheme.
			if ( ! in_array( $parts['scheme'], array( 'http', 'https' ), true ) ) {
				add_settings_error( 'url', 'url', 'Invalid URL: Must begin with <code>http://</code> or <code>https://</code>' );
			}

			// Make sure the host is resolves.
			if ( ! filter_var( $parts['host'], FILTER_VALIDATE_IP ) ) {
				$ip = gethostbyname( $parts['host'] );
				if ( $ip === $parts['host'] ) {
					add_settings_error( 'url', 'url', 'Invalid URL: Could not resolve hostname' );
				}
			}
		}

		$data['path'] = trim( $data['path'], '/' );
		if ( strlen( $data['path'] ) > 0 ) {
			$data['path'] = '/' . $data['path'];
		}

		return array(
			'url'        => esc_url_raw( $data['url'] ),
			'path'       => $data['path'],
			'dirs'       => esc_attr( $data['dirs'] ),
			'excludes'   => esc_attr( $data['excludes'] ),
			'relative'   => (bool) $data['relative'],
			'https'      => (bool) $data['https'],
			'directives' => self::clean_directives( $data['directives'] ),
			'enabled'    => (bool) $data['enabled'],
		);
	}

	/**
	 * Clean the ImageEngine Directives.
	 *
	 * @param string $directives ImageEngine Directives as a comma-separated list.
	 */
	public static function clean_directives( $directives ) {
		$directives = preg_replace( '#.*imgeng=/+?#', '', $directives );
		$directives = trim( $directives );

		// Ensure there is one leading "/" and none trailing.
		$directives = trim( $directives, '/' );
		$directives = '/' . $directives;
		$directives = rtrim( $directives, '/' );

		return $directives;
	}


	/**
	 * Add settings page.
	 */
	public static function add_settings_page() {
		$page = add_options_page( 'Image CDN', 'Image CDN', 'manage_options', 'image_cdn', array( self::class, 'settings_page' ) );
	}


	/**
	 * Settings page.
	 */
	public static function settings_page() {
		$options     = ImageCDN::get_options();
		$defaults    = ImageCDN::default_options();
		$is_runnable = ImageCDN::should_rewrite();
		include __DIR__ . '/../templates/settings.php';
	}


	/**
	 * Registers the configuration test javascript helpers.
	 */
	public static function register_test_config() {
		$nonce = wp_create_nonce( 'image-cdn-test-config' );
		?>
		<script>
			document.addEventListener('DOMContentLoaded', () => {
				const show_test_results = res => {
					const class_name = 'notice-' + res.type
					document.querySelectorAll('.image-cdn-test').forEach(el => {
						if (!el.classList.contains(class_name)) {
							el.classList.add('hidden')
							return
						}

						el.classList.remove('hidden')
						if (res.type == 'warning' || res.type == 'error') {
							el.querySelector('.image-cdn-result').innerHTML = res.message
							el.querySelector('.image-cdn-local-url').innerHTML = res.local_url
							el.querySelector('.image-cdn-remote-url').innerHTML = res.cdn_url
						}
					})
				}

				document.querySelector('#check-cdn').addEventListener('click', () => {
					show_test_results({'type': 'info'})

					window.scrollTo({
						top: 50,
						left: 0,
						behavior: 'smooth',
					});

					const data = {
						'action': 'image_cdn_test_config',
						'nonce': '<?php echo esc_js( $nonce ); ?>',
						'cdn_url': document.querySelector('#image_cdn_url').value,
						'path': document.querySelector('#image_cdn_path').value,
					}
					console.log(data)

					const success = response => show_test_results(response.data)

					jQuery.post(ajaxurl, data, success, 'json')
						.fail((jqXHR, status) => {
							show_test_results('error', 'unable to start test: ' + status)
						})
				})
			})
		</script>
		<?php
	}

	/**
	 * Runs the configuration test.
	 */
	public static function test_config() {
		check_ajax_referer( 'image-cdn-test-config', 'nonce' );

		$out = array(
			'type'      => 'error',
			'message'   => '',
			'local_url' => '',
			'cdn_url'   => '',
		);

		if ( ! isset( $_POST['cdn_url'] ) ) {
			$out['message'] = 'Malformed request';
			wp_send_json_error( $out );
		}

		// Make sure we can fetch this content from the local WordPress installation and via the CDN.
		$asset        = 'assets/logo.png';
		$local_url    = plugin_dir_url( IMAGE_CDN_FILE ) . $asset;
		$cdn_base_url = trim( esc_url_raw( wp_unslash( $_POST['cdn_url'] ) ), '/' );
		$path         = array_key_exists( 'path', $_POST ) ? sanitize_text_field( wp_unslash( $_POST['path'] ) ) : '';

		$plugin_path = wp_parse_url( plugin_dir_url( IMAGE_CDN_FILE ), PHP_URL_PATH );

		$home_path   = trim( wp_parse_url( get_option( 'home' ), PHP_URL_PATH ), '/' );
		$plugin_path = trim( self::remove_prefix( $plugin_path, $home_path ), '/' );

		$parts = array(
			$cdn_base_url,
			$path,
			$plugin_path,
			$asset,
		);

		$clean_parts = array();
		foreach ( $parts as $part ) {
			$part = trim( $part, '/' );
			if ( ! empty( $part ) ) {
				$clean_parts[] = $part;
			}
		}

		$cdn_url = implode( '/', $clean_parts );

		$out['home_path']   = $home_path;
		$out['plugin_path'] = $plugin_path;

		$out['local_url'] = $local_url;
		$out['cdn_url']   = $cdn_url;

		$local_res = wp_remote_get( $local_url, array( 'sslverify' => false ) );
		if ( is_wp_error( $local_res ) ) {
			$out['message'] = 'Unable to find a local resource to test: ' . $local_res->get_error_message();
			wp_send_json_error( $out );
		}

		if ( $local_res['response']['code'] >= 400 ) {
			$out['message'] = 'Unable to find a local resource to test: server responded with HTTP ' . $local_res['response']['code'];
			wp_send_json_error( $out );
		}

		$cdn_res = wp_remote_get( $cdn_url, array( 'sslverify' => false ) );
		if ( is_wp_error( $cdn_res ) ) {
			$out['message'] = 'Unable to fetch the URL through the CDN: ' . $cdn_res->get_error_message();
			wp_send_json_error( $out );
		}

		if ( 400 <= $cdn_res['response']['code'] ) {
			if ( 502 === $cdn_res['response']['code'] ) {
				if ( array_key_exists( 'x-origin-status', $cdn_res['headers'] ) ) {
					$status         = $cdn_res['headers']['x-origin-status'];
					$out['message'] = "Unable to fetch the URL through the CDN: server responded with HTTP $status";

					if ( array_key_exists( 'x-origin-reason', $cdn_res['headers'] ) ) {
						$reason          = $cdn_res['headers']['x-origin-reason'];
						$out['message'] .= " $reason";
					}
					wp_send_json_error( $out );
				}
			}

			$out['message'] = 'Unable to fetch the URL through the CDN: server responded with HTTP ' . $cdn_res['response']['code'];
			wp_send_json_error( $out );
		}

		if ( ! isset( $cdn_res['headers']['content-type'] ) ) {
			$out['type']    = 'warning';
			$out['message'] = 'Unable to confirm that the CDN is working properly because it didn\'t send a content type';
			wp_send_json_error( $out );
		}

		$cdn_type = $cdn_res['headers']['content-type'];
		if ( strpos( $cdn_type, 'image/png' ) === false ) {
			$out['type']    = 'error';
			$out['message'] = "CDN returned the wrong content type (expected 'image/png', got '$cdn_type'";
			wp_send_json_error( $out );
		}

		$out['type']    = 'success';
		$out['message'] = 'Test successful';
		wp_send_json_success( $out );
	}

	/**
	 * Removes the given prefix (needle) from the haystack.
	 *
	 * @param string $haystack The string which is to have it's prefix removed.
	 * @param string $needle the prefix to be removed.
	 * @return string The haystack without the needle prefix, or the original haystack if no match.
	 */
	protected static function remove_prefix( $haystack, $needle ) {
		$has_prefix = substr( $haystack, 0, strlen( $needle ) ) === $needle;
		if ( $has_prefix ) {
			return substr( $haystack, strlen( $needle ) );
		}

		return $haystack;
	}
}