<?php
/**
 * Product Visibility by User Role for WooCommerce - Review Suggestion
 *
 * @version 1.8.4
 * @since   1.8.4
 * @author  WP Wham
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Review' ) ) :

class Alg_WC_PVBUR_Review {

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
	 * @version 1.8.4
	 * @since   1.8.4
	 */
	function review_suggestion() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

        /* Show this on Product Visibility by User Role settings page only?
		if ( ! ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] === 'alg_wc_pvbur' ) ) {
			return;
		}
        */

		$dismissed = get_option( 'alg_wc_pvbur_review_dismissed' );
		if ( $dismissed === 'permanently' || ( is_numeric( $dismissed ) && $dismissed > time() ) ) {
			return;
		}

		if ( isset( $_GET['alg_wc_pvbur_dismiss_review'] ) && isset( $_GET['_wpnonce'] ) ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'alg_wc_pvbur_dismiss' ) ) {
				if ( isset( $_GET['later'] ) ) {
					update_option( 'alg_wc_pvbur_review_dismissed', time() + ( DAY_IN_SECONDS * 30 ) );
				} else {
					update_option( 'alg_wc_pvbur_review_dismissed', 'permanently' );
				}
				wp_redirect( remove_query_arg( array( 'alg_wc_pvbur_dismiss_review', '_wpnonce', 'later' ) ) );
				exit;
			}
		}

		$installed = get_option( 'alg_wc_pvbur_installed_time' );
		if ( ! $installed ) {
			update_option( 'alg_wc_pvbur_installed_time', time() );
			return;
		}

		if ( ( $installed + ( DAY_IN_SECONDS * 7 ) ) > time() ) {
			return;
		}

		if ( ! $this->has_minimum_usage() ) {
			return;
		}

		$review_url = 'https://wordpress.org/support/plugin/product-visibility-by-user-role-for-woocommerce/reviews/?rate=5#new-post';
		$dismiss_url = wp_nonce_url( add_query_arg( 'alg_wc_pvbur_dismiss_review', '1' ), 'alg_wc_pvbur_dismiss' );
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
	 * @version 1.8.4
	 * @since   1.8.4
	 */
	function has_minimum_usage() {
		$products = get_posts( array(
			'post_type'      => 'product',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => '_alg_wc_pvbur_visible',   'value' => '', 'compare' => '!=' ),
				array( 'key' => '_alg_wc_pvbur_invisible', 'value' => '', 'compare' => '!=' ),
			),
		) );
		return ! empty( $products );
	}
}

endif;

return new Alg_WC_PVBUR_Review();
