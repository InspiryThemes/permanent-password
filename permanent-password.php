<?php
/**
 * Plugin Name: Permanent Password
 * Description: A light-weight plugin to add a permanent password to any user on your website.
 * Plugin URI: https://github.com/InspiryThemes/permanent-password
 * Author: Inspiry Themes
 * Author URI: http://inspirythemes.com
 * Contributors: mrasharirfan, inspirythemes
 * Version: 1.0.0
 * License: GPL2
 * Text Domain: pp
 * Domain Path: /languages/
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permanent_Password.
 *
 * Plugin Core Class.
 *
 * @since 1.0.0
 */

if ( ! class_exists( 'Permanent_Password' ) ) :

	class Permanent_Password {

		/**
		 * Version.
		 *
		 * @var    string
		 * @since    1.0.0
		 */
		public $version = '1.0.0';

		/**
		 * Permanent Password Instance.
		 *
		 * @var    Inspiry_Memberships
		 * @since    1.0.0
		 */
		protected static $_instance;

		/**
		 * Method: Creates an instance of the class.
		 *
		 * @since 1.0.0
		 */
		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;

		}

		/**
		 * Method: Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			$this->define_constants();
			$this->init_hooks();

		}

		/**
		 * Method: Define constants.
		 *
		 * @since 1.0.0
		 */
		public function define_constants() {

			// Plugin version
			if ( ! defined( 'PP_VERSION' ) ) {
				define( 'PP_VERSION', $this->version );
			}

			// Plugin Name
			if ( ! defined( 'PP_BASE_NAME' ) ) {
				define( 'PP_BASE_NAME', plugin_basename( __FILE__ ) );
			}

			// Plugin Directory URL
			if ( ! defined( 'PP_BASE_URL' ) ) {
				define( 'PP_BASE_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Directory Path
			if ( ! defined( 'PP_BASE_DIR' ) ) {
				define( 'PP_BASE_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Docs URL
			if ( ! defined( 'PP_DOCS_URL' ) ) {
				define( 'PP_DOCS_URL', '#' );
			}

			// Plugin Issue Reporting URL
			if ( ! defined( 'PP_ISSUE_URL' ) ) {
				define( 'PP_ISSUE_URL', 'https://github.com/InspiryThemes/permanent-password/issues' );
			}

		}

		/**
		 * Method: Initialization hooks.
		 *
		 * @since 1.0.0
		 */
		public function init_hooks() {

			// Display user settings on profile page.
			add_action( 'show_user_profile', array( $this, 'pp_user_setting' ) );
			add_action( 'edit_user_profile', array( $this, 'pp_user_setting' ) );

			// Save user settings on profile page.
			add_action( 'personal_options_update', array( $this, 'pp_save_user_setting' ) );
			add_action( 'edit_user_profile_update', array( $this, 'pp_save_user_setting' ) );

			// Check and prepare data in case user password needs to be updated to permanent password.
			add_action( 'profile_update', array( $this, 'pp_check_user_password' ), 10, 2 );

			// Update user password to permanent password.
			add_action( 'pp_protect_password', array( $this, 'pp_update_protect_password' ), 10, 3 );

		}

		/**
		 * Method: Show setting field in user profile page.
		 *
		 * @since 1.0.0
		 */
		public function pp_user_setting( $user ) {

			$current_user = wp_get_current_user();
			if ( in_array( 'administrator', (array) $current_user->roles ) ) : ?>

				<h3><?php esc_html_e( 'Make password permanent', 'pp' ); ?></h3>

				<table class="form-table">

					<tr>
						<th>
							<label for="pp_check"><?php esc_html_e( 'Password Check', 'pp' ); ?></label>
						</th>

						<td>
							<?php $checked	= get_the_author_meta( 'pp_check', $user->ID ); ?>
							<input
									type="checkbox"
									name="pp_check"
									id="pp_check"
									class="regular-text"
									<?php echo ( 'on' === $checked ) ? 'checked' : false; ?> />
							<span class="description"><?php esc_html_e( 'Check to make the password of this user permanent.', 'pp' ); ?></span>
						</td>

					</tr>

					<tr>

						<th>
							<label for="pp_password"><?php esc_html_e( 'Permanent Password', 'pp' ); ?></label>
						</th>

						<td>
							<?php $pp_password	= get_the_author_meta( 'pp_password', $user->ID ); ?>
							<input
									type="password"
									name="pp_password"
									id="pp_password"
									class="regular-text"
									value="<?php echo ( ! empty( $pp_password ) ) ? $pp_password : ''; ?>" /><br>
							<span class="description"><?php esc_html_e( 'Enter a permanent password for this user.', 'pp' ); ?></span>
						</td>

					</tr>

				</table>
				<?php

			endif;

		}

		/**
		 * Method: Save setting field in user profile page.
		 *
		 * @since 1.0.0
		 */
		public function pp_save_user_setting( $user_id ) {

			$current_user = wp_get_current_user();
			if ( ! in_array( 'administrator', (array) $current_user->roles ) )
				return false;

			$pp_password = get_user_meta( $user_id, 'pp_password', true );

			if ( isset( $_POST[ 'pp_check' ] ) ) {
				update_user_meta( $user_id, 'pp_check', $_POST[ 'pp_check' ] );
			} else {
				update_user_meta( $user_id, 'pp_check', false );
			}

			if ( isset( $_POST[ 'pp_password' ] ) ) {
				update_user_meta( $user_id, 'pp_password', $_POST[ 'pp_password' ] );
			} else {
				update_user_meta( $user_id, 'pp_password', false );
			}

			if ( isset( $_POST[ 'pp_password' ] ) && $pp_password !== $_POST[ 'pp_password' ] ) {
				update_user_meta( $user_id, 'pp_password_updated', true );
			} else {
				update_user_meta( $user_id, 'pp_password_updated', false );
			}

		}

		/**
		 * Method: Check user password.
		 *
		 * @since 1.0.0
		 */
		public function pp_check_user_password( $user_id, $old_user_data ) {

			if ( empty( $user_id ) || empty( $old_user_data ) ) {
				return false;
			}

			$old_pass 	= $old_user_data->data->user_pass;
			$user_data 	= get_userdata( $user_id );
			$new_pass	= $user_data->data->user_pass;

			$permanent 	= get_user_meta( $user_id, 'pp_check', true );
			$permanent 	= ( 'on' === $permanent ) ? true : false;

			$pp_password = get_user_meta( $user_id, 'pp_password', true );
			$pp_password = ( ! empty( $pp_password ) ) ? wp_hash_password( $pp_password ) : false;

			$pp_password_updated = get_user_meta( $user_id, 'pp_password_updated', true );

			/**
			 * Check to see if user password has been tried to change
			 * or user permanent password has been updated. If any of
			 * the conditions are true then proceed.
			 */
			if ( ! empty( $permanent ) && ! empty( $pp_password ) && ( $old_pass !== $new_pass || ! empty( $pp_password_updated ) ) ) {

				$user_data 	= array(
					'user_id'	=> $user_id,
					'pp_pass'	=> $pp_password, // Hashed permanent password.
					'new_pass'	=> $new_pass
				);

				/**
				 * Schedule the change of password
				 *
				 * @param int 	 - unix timestamp of when to run the event
				 * @param string - pp_protect_password
				 * @param array  - arguments required by updating function
				 *
				 * @since 1.0.0
				 */
				wp_schedule_single_event( current_time( 'timestamp' ), 'pp_protect_password', $user_data );

			}

		}

		/**
		 * Method: Changes user password on saving different one
		 * on permanent password account.
		 *
		 * @since 1.0.0
		 */
		public function pp_update_protect_password( $user_id, $pp_pass, $new_pass ) {

			if ( empty( $user_id ) || empty( $pp_pass ) || empty( $new_pass ) ) {
				return false;
			}

			$permanent 	= get_user_meta( $user_id, 'pp_check', true );
			$permanent 	= ( 'on' === $permanent ) ? true : false;

			$pp_password = get_user_meta( $user_id, 'pp_password', true );

			if ( ! empty( $permanent ) && ! empty( $pp_password ) && ( $pp_pass !== $new_pass ) ) {

				/**
				 * Clear the 'profile_update' hook before updating the password
				 * otherwise you will get caught in recursive call. Because
				 * wp_update_user calls wp_insert_user which in turn calls
				 * 'profile_update'.
				 */
				remove_action( 'profile_update', array( $this, 'pp_check_user_password' ) );

				// Update the password.
				$return = wp_update_user( array(
					'ID' 		=> $user_id,
					'user_pass' => $pp_password
				) );

				// Error handling.
				if ( is_wp_error( $return ) ) {
					$errors[] = $return->get_error_message();
					add_action( 'admin_notices', array( $this, 'pp_password_update_error_notice' ) );
				}

				// Clear schedule hook.
				wp_clear_scheduled_hook( 'pp_protect_password', array( $user_id, $pp_pass, $new_pass ) );

			}

		}

		/**
		 * Method: Adds notice to admin panel in case of
		 * error in password update.
		 *
		 * @since 1.0.0
		 */
		public function pp_password_update_error_notice() {

			$class 		= 'notice notice-error is-dismissible';
			$message 	= __( 'Yikes! An error occurred while updating your permanent password.', 'pp' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );

		}

	}

endif;


/**
 * Returns the main instance of Permanent_Password.
 *
 * @since 1.0.0
 */
function PP() {
	return Permanent_Password::instance();
}

PP();
