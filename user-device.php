<?php
/**
 * @source Mobile_Detect class, https://gist.github.com/dcondrey/11342487
 * @source Stack Overflow
 */

/**
 * Class Mobile_Detect
 */
class PBO_User_Device {
	protected static $devices = array(
		"windows"    => "(iemobile|ppc|smartphone|windows phone|windows ce)",
		"nexus"      => "nexus",
		"android"    => "android",
		"blackberry" => "blackberry",
		"iphone"     => "(iphone|ipod)",
		"ipad"       => "ipad",
		"opera"      => "opera mini",
		"palm"       => "(avantgo|blazer|elaine|hiptop|palm|plucker|xiino)",
		"generic"    => "(kindle|mobile|mmp|midp|o2|pda|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap)"
	);

	public static $device = null;
//	public static $is_ipad = false;

	public static function is_mobile() {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$accept     = isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : '';
		if ( isset( $_SERVER['HTTP_X_WAP_PROFILE'] ) || isset( $_SERVER['HTTP_PROFILE'] ) ) {
			return true;
		} elseif ( strpos( $accept, 'text/vnd.wap.wml' ) > 0 || strpos( $accept, 'application/vnd.wap.xhtml+xml' ) > 0 ) {
			return true;
		} else {
			foreach ( self::$devices as $device => $regexp ) {
				//echo $device . ' - ' . $regexp;
				if ( (bool) preg_match( "/" . $regexp . "/i", $user_agent ) ) {
					self::$device = $device;
//					if ( $device === 'ipad' ) {
//						self::$is_ipad = true;
//					}
					return true;
				}
			}
		}

		return false;
	}

	public static function get_user_device_info_ajax_script() {
		?>
		<script type='text/javascript'>
			/* <![CDATA[ */
			jQuery(function ($) {
				$.post(
					'<?php echo admin_url( 'admin-ajax.php', 'relative' )?>',
					"pbo_document_width=" + $(document).width(),
					function (response) {
						//alert(response);
					}
				);
			});
			/* ]]> */
		</script>
		<?php
	}

	public static function set_user_device_info( $args, $ajax = true ) {
		if ( isset( $args['pbo_document_width'] ) && ! empty( $args['pbo_document_width'] ) ) {
			setcookie( 'pbo_document_width', $args['pbo_document_width'], 0, COOKIEPATH, COOKIE_DOMAIN, false );
			if ( $ajax ) {
				echo 'OK, ' . $args['pbo_document_width'];
				die();
			}
		}
	}

	public static function try_to_get_document_width() {
		if ( isset( $_COOKIE['pbo_document_width'] ) ) {
			return (int) $_COOKIE['pbo_document_width'];
		} else {
			add_action( 'wp_print_footer_scripts', [ 'PBO_User_Device', 'get_user_device_info_ajax_script' ] );
		}

		return null;
	}


}