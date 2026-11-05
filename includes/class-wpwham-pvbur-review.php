<?php
/**
 * Product Visibility by User Role for WooCommerce - Review Suggestion
 *
 * @version 1.8.6
 * @since   1.8.4
 * @author  WP Wham
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WPWham_PVBUR_Review' ) ) :

class WPWham_PVBUR_Review {

	/**
	 * Constructor.
	 *
	 * @version 1.8.4
	 * @since   1.8.4
	 */
	function __construct() {
		add_action( 'admin_init', array( $this, 'review_suggestion' ) );
	}

	/**
	 * Review suggestion notice in admin.
	 *
	 * @version 1.8.6
	 * @since   1.8.4
	 */
	function review_suggestion() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

        /* Show this on Product Visibility by User Role settings page only?
		if ( ! ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] === 'alg_wc_pvbur' ) ) {
			return;
		}
		*/

		$dismissed = get_option( 'wpwham_pvbur_review_dismissed' );
		if ( $dismissed === 'permanently' || ( is_numeric( $dismissed ) && $dismissed > time() ) ) {
			return;
		}

		if ( isset( $_GET['wpwham_pvbur_dismiss_review'] ) && isset( $_GET['_wpnonce'] ) ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'wpwham_pvbur_dismiss' ) ) {
				if ( isset( $_GET['later'] ) ) {
					update_option( 'wpwham_pvbur_review_dismissed', time() + ( DAY_IN_SECONDS * 30 ) );
				} else {
					update_option( 'wpwham_pvbur_review_dismissed', 'permanently' );
				}
				wp_redirect( remove_query_arg( array( 'wpwham_pvbur_dismiss_review', '_wpnonce', 'later' ) ) );
				exit;
			}
		}

		$installed = get_option( 'wpwham_pvbur_installed_time' );
		if ( ! $installed ) {
			update_option( 'wpwham_pvbur_installed_time', time() );
			return;
		}

		if ( ( $installed + ( DAY_IN_SECONDS * 7 ) ) > time() ) {
			return;
		}

		if ( ! $this->has_minimum_usage() ) {
			return;
		}

		$review_url = 'https://wordpress.org/support/plugin/product-visibility-by-user-role-for-woocommerce/reviews/?rate=5#new-post';
		$dismiss_url = wp_nonce_url( add_query_arg( 'wpwham_pvbur_dismiss_review', '1' ), 'wpwham_pvbur_dismiss' );
		$later_url = add_query_arg( 'later', '1', $dismiss_url );
		?>
		<div class="updated woocommerce-message">
			<a class="woocommerce-message-close notice-dismiss" href="<?php echo esc_url( $later_url ); ?>"><?php esc_html_e( 'Dismiss', 'product-visibility-by-user-role-for-woocommerce' ); ?></a>
			<p><?php esc_html_e( 'Finding Product Visibility by User Role useful? We\'d appreciate a 5-star review!', 'product-visibility-by-user-role-for-woocommerce' ); ?></p>
			<p><a href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Sure, you deserve it', 'product-visibility-by-user-role-for-woocommerce' ); ?></a></p>
			<p><a href="<?php echo esc_url( $later_url ); ?>"><?php esc_html_e( 'Maybe later', 'product-visibility-by-user-role-for-woocommerce' ); ?></a></p>
			<p><a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'I already did!', 'product-visibility-by-user-role-for-woocommerce' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Check if user has at least one product with visibility or invisibility settings.
	 *
	 * @version 1.8.6
	 * @since   1.8.4
	 */
	function has_minimum_usage() {
		$cached = get_transient( 'wpwham_pvbur_has_usage' );
		if ( $cached !== false ) {
			return $cached === 'yes';
		}
		global $wpdb;
		$result = $wpdb->get_var(
			"SELECT post_id FROM {$wpdb->postmeta}
			WHERE meta_key IN ( '_alg_wc_pvbur_visible', '_alg_wc_pvbur_invisible' )
			AND meta_value != ''
			LIMIT 1"
		);
		$has_usage = ! empty( $result );
		set_transient( 'wpwham_pvbur_has_usage', $has_usage ? 'yes' : 'no', DAY_IN_SECONDS );
		return $has_usage;
	}
}

endif;

return new WPWham_PVBUR_Review();
