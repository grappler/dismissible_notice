<?php
/**
 * Creates a dismissible--via AJAX--admin nag
 *
 * @package   @Caldera_Warnings_Dismissible_Notice
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link      
 * @copyright 2015 Josh Pollock
 */
/**
 * Version 0.1.0
 */

if ( class_exists( 'Caldera_Warnings_Dismissible_Notice' ) ) {
	return;

}

/**
 * Class Caldera_Warnings_Dismissible_Notice
 *
 * @package   @Caldera_Warnings_Dismissible_Notice
 */
class Caldera_Warnings_Dismissible_Notice {

	/**
	 * Define the $ignore_key to be used later
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected static $ignore_key = '';

	/**
	 * The action for the nonce
	 *
	 * @since 0.1.0
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected static $nonce_action = 'caldera_admin_nag';

	/**
	 * Output the message
	 *
	 * @since 0.1.0
	 *
	 * @param string $message The text of the message.
	 * @param bool $error Optional. Whether to show as error or update. Default is error.
	 * @param string $cap_check Optional. Minimum user capability to show nag to. Default is "manage_options"
	 * @param string|bool $ignore_key Optional. The user meta key to use for storing if this message has been dismissed by current user or not. If false, it will be generated.
	 *
	 * @return string|void Admin notice if is_admin() and not dismissed.
	 */
	public static function notice( $message, $error = true, $cap_check = 'manage_options', $ignore_key = false ) {
		if ( is_admin() && ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) ) {
			if ( current_user_can( $cap_check ) ) {
				$user_id = get_current_user_id();
				if ( ! is_string( $ignore_key ) ) {
					$ignore_key = 'cal_wd_ig_' . substr( md5( $message ), 0, 40 );
				}

				$dismissed = get_user_meta( $user_id, sanitize_key( $ignore_key ), true );
				if ( ! $dismissed ) {
					if ( $error ) {
						$class = 'error';
					} else {
						$class = 'updated';
					}

					$out[] = sprintf( '<div id="%1s" class="%2s notice is-dismissible"><p>', $ignore_key, $class );
					$out[] = $message;
					$out[] = wp_nonce_field( self::$nonce_action );
					$out[] = '</p></div>';
					self::$ignore_key = $ignore_key;
					add_action( 'admin_footer', array( __CLASS__, 'js' ) );
					add_action( 'wp_ajax_caldera_warnings_dismissible_notice', array( __CLASS__, 'ajax_cb' ) );

					return implode( '', $out );

				}

			}

		}

	}

	/**
	 * JavaScript for click event.
	 *
	 * @since 0.1.0
	 *
	 * @uses "admin_footer"
	 */
	public static function js() {
		?>
		<script>
			jQuery(document).ready(function($) {

				$( ".is-dismissible" ).click( function ( event ) {
					event.preventDefault();

					$.post( ajaxurl, {
						action: "caldera_warnings_dismissible_notice",
						url: ajaxurl,
						nag: "<?php echo self::$ignore_key ?>",
						nonce: $( "<?php echo '#' . self::$nonce_action; ?>" ).val()
					});

				} );
			});

		</script>

	<?php
	}

	/**
	 * AJAX callback to mark the message dismissed.
	 *
	 * @since 0.1.0
	 *
	 * @uses "wp_ajax_caldera_warnings_dismissible_notice"
	 *
	 * @return bool
	 */
	public static function ajax_cb() {
		if (  ! isset( $_POST[ 'nonce' ] ) && ! isset( $_POST['message_id'] ) || ! wp_verify_nonce( $_POST[ 'nonce' ], self::$nonce_action ) ) {
			//return false;
		}

		$nag = sanitize_key( $_POST[ 'nag' ] );
		if ( $nag === $_POST[ 'nag' ] ) {
			update_user_meta( get_current_user_id(), $nag, true );
		}

	}

}
