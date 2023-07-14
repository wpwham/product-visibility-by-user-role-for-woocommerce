<?php
/*
Plugin Name: Product Visibility by User Role for WooCommerce
Plugin URI: https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/
Description: Display WooCommerce products by customer's user role.
Version: 1.8.1
Author: WP Wham
Author URI: https://wpwham.com/
Text Domain: product-visibility-by-user-role-for-woocommerce
Domain Path: /langs
WC tested up to: 7.8
Copyright: © 2019-2023 WP Wham. All rights reserved.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Check if WooCommerce is active
$plugin = 'woocommerce/woocommerce.php';
if (
	! in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) &&
	! ( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
) {
	return;
}

if ( 'product-visibility-by-user-role-for-woocommerce.php' === basename( __FILE__ ) ) {
	// Check if Pro is active, if so then return
	$plugin = 'product-visibility-by-user-role-for-woocommerce-pro/product-visibility-by-user-role-for-woocommerce-pro.php';
	if (
		in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
		( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
	) {
		return;
	}
}

define( 'WPWHAM_PRODUCT_VISIBILITY_BY_USER_ROLE_VERSION', '1.8.1' );

if ( ! class_exists( 'Alg_WC_PVBUR' ) ) :

/**
 * Main Alg_WC_PVBUR Class
 *
 * @class   Alg_WC_PVBUR
 * @version 1.8.1
 * @since   1.0.0
 */
final class Alg_WC_PVBUR {
	
	public $core          = null;
	public $settings      = null;
	public $compatibility = null;
	
	/**
	 * Plugin version.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	public $version = '1.8.1';

	/**
	 * @var   Alg_WC_PVBUR The single instance of the class
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main Alg_WC_PVBUR Instance
	 *
	 * Ensures only one instance of Alg_WC_PVBUR is loaded or can be loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @static
	 * @return  Alg_WC_PVBUR - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Alg_WC_PVBUR Constructor.
	 *
	 * @version 1.4.0
	 * @since   1.0.0
	 * @access  public
	 */
	function __construct() {

		// Set up localisation
		load_plugin_textdomain( 'product-visibility-by-user-role-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );

		// Include required files
		$this->includes();

		// Admin
		if ( is_admin() ) {
			$this->admin();
		}
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @version 1.7.2
	 * @since   1.0.0
	 * @param   mixed $links
	 * @return  array
	 */
	function action_links( $links ) {
		$custom_links = array();
		$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_pvbur' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>';
		if ( 'product-visibility-by-user-role-for-woocommerce.php' === basename( __FILE__ ) ) {
			$custom_links[] = '<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/?utm_source=plugins_page&utm_campaign=free&utm_medium=product_visibility_user_role">' .
				__( 'Unlock All', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>';
		}
		return array_merge( $custom_links, $links );
	}
	
	/**
	 * @since   1.7.2
	 */
	public function enqueue_scripts() {
		global $pagenow;
		
		// check if its a page where we need this
		if (
			$pagenow === 'post.php'
			|| ( $pagenow === 'post-new.php' && isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] === 'product' )
			|| ( $pagenow === 'admin.php' && isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] === 'alg_wc_pvbur' && isset( $_REQUEST['section'] ) && $_REQUEST['section'] === 'bulk' )
		) {
			wp_enqueue_script(
				'wpwham-product-visibility-user-role-admin',
				$this->plugin_url() . '/includes/js/admin.js',
				array( 'jquery' ),
				$this->version,
				false
			);
			wp_localize_script(
				'wpwham-product-visibility-user-role-admin',
				'wpwham_product_visibility_user_role_admin',
				array(
					'i18n' => array(
						'logical_error'                          => __( 'Error: Visible and Invisible are mutually-exclusive, you cannot use both at the same time.', 'product-visibility-by-user-role-for-woocommerce' ),
						'see_documentation'                      => sprintf( __( 'Need help? Check our <a href="%s" target="_blank">Documentation</a>.', 'product-visibility-by-user-role-for-woocommerce' ), 'https://wpwham.com/documentation/product-visibility-by-user-role-for-woocommerce/?utm_source=documentation_link&utm_campaign=free&utm_medium=product_visibility_user_role' ),
						'why_is_invisible_disabled'              => __( 'You have chosen to specify the roles you want <strong>visible</strong>.  All others are automatically <strong>invisible</strong>.  You don\'t have to specify both.', 'product-visibility-by-user-role-for-woocommerce' ),
						'why_is_visible_disabled'                => __( 'You have chosen to specify the roles you want <strong>invisible</strong>.  All others are automatically <strong>visible</strong>.  You don\'t have to specify both.', 'product-visibility-by-user-role-for-woocommerce' ),
						'why_is_invisible_products_disabled'     => __( 'You have chosen to specify the products you want <strong>visible</strong>.  All others are automatically <strong>invisible</strong>.  You don\'t have to specify both.', 'product-visibility-by-user-role-for-woocommerce' ),
						'why_is_visible_products_disabled'       => __( 'You have chosen to specify the products you want <strong>invisible</strong>.  All others are automatically <strong>visible</strong>.  You don\'t have to specify both.', 'product-visibility-by-user-role-for-woocommerce' ),
						'why_is_invisible_product_cats_disabled' => __( 'You have chosen to specify the categories you want <strong>visible</strong>.  All others are automatically <strong>invisible</strong>.  You don\'t have to specify both.', 'product-visibility-by-user-role-for-woocommerce' ),
						'why_is_visible_product_cats_disabled'   => __( 'You have chosen to specify the categories you want <strong>invisible</strong>.  All others are automatically <strong>visible</strong>.  You don\'t have to specify both.', 'product-visibility-by-user-role-for-woocommerce' ),
						'why_is_invisible_product_tags_disabled' => __( 'You have chosen to specify the tags you want <strong>visible</strong>.  All others are automatically <strong>invisible</strong>.  You don\'t have to specify both.', 'product-visibility-by-user-role-for-woocommerce' ),
						'why_is_visible_product_tags_disabled'   => __( 'You have chosen to specify the tags you want <strong>invisible</strong>.  All others are automatically <strong>visible</strong>.  You don\'t have to specify both.', 'product-visibility-by-user-role-for-woocommerce' ),
					),
				)
			);
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @version 1.7.1
	 * @since   1.0.0
	 */
	function includes() {
		
		// Functions
		require_once( 'includes/alg-wc-pvbur-functions.php' );
		
		// Core
		$this->core = require_once( 'includes/class-alg-wc-pvbur-core.php' );
		
		// 3rd party compatibility
		require_once( 'includes/class-alg-wc-pvbur-wpml.php' );
		require_once( 'includes/class-wpwham-pvur-third-party-compatibility.php' );
		$this->compatibility = WPWham_PVUR_Third_Party_Compatibility::get_instance();
		
	}

	/**
	 * add settings to WC status report
	 *
	 * @version 1.6.0
	 * @since   1.6.0
	 * @author  WP Wham
	 */
	public static function add_settings_to_status_report() {
		#region add_settings_to_status_report
		$protected_settings  = array( 'wpwham_product_visibility_user_role_license', 'wpw_pvbur_filler' );
		$settings_general    = Alg_WC_PVBUR_Settings_General::get_settings();
		$settings_bulk       = array();
		$settings_bulk_class = new Alg_WC_PVBUR_Settings_Bulk();
		$settings_bulk_class->init_user_roles();
		foreach ( $settings_bulk_class->user_roles as $k => $v ) {
			$_GET['subsection'] = $k; // hacky way to get all the possible settings pages from here
			$settings_bulk = array_merge(
				$settings_bulk,
				array( array( 'id' => 'wpw_pvbur_filler', 'type' => 'filler', 'title' => "Bulk settings for role $k" ) ),
				$settings_bulk_class->get_settings()
			);
		}
		$settings = array_merge( $settings_general, $settings_bulk );
		?>
		<table class="wc_status_table widefat" cellspacing="0">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Product Visibility by User Role Settings"><h2><?php esc_html_e( 'Product Visibility by User Role Settings', 'product-visibility-by-user-role-for-woocommerce' ); ?></h2></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $settings as $setting ): ?>
				<?php 
				if ( in_array( $setting['type'], array( 'title', 'sectionend' ) ) ) { 
					continue;
				}
				if ( isset( $setting['title'] ) ) {
					$title = $setting['title'];
				} elseif ( isset( $setting['desc'] ) ) {
					$title = $setting['desc'];
				} else {
					$title = $setting['id'];
				}
				$value = get_option( $setting['id'] ); 
				if ( in_array( $setting['id'], $protected_settings ) ) {
					$value = $value > '' ? '(set)' : 'not set';
				}
				?>
				<tr>
					<td data-export-label="<?php echo esc_attr( $title ); ?>"><?php esc_html_e( $title, 'product-visibility-by-user-role-for-woocommerce' ); ?>:</td>
					<td class="help">&nbsp;</td>
					<td><?php echo is_array( $value ) ? print_r( $value, true ) : $value; ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		#endregion add_settings_to_status_report
	}

	/**
	 * admin.
	 *
	 * @version 1.7.2
	 * @since   1.4.0
	 */
	function admin() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Action links
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		// Settings
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_woocommerce_settings_tab' ) );
		require_once( 'includes/settings/class-alg-wc-pvbur-metaboxes.php' );
		require_once( 'includes/settings/class-alg-wc-pvbur-settings-section.php' );
		$this->settings = array();
		$this->settings['general'] = require_once( 'includes/settings/class-alg-wc-pvbur-settings-general.php' );
		$this->settings['bulk']    = require_once( 'includes/settings/class-alg-wc-pvbur-settings-bulk.php' );
		add_action( 'woocommerce_system_status_report', array( $this, 'add_settings_to_status_report' ) );
		// Version updated
		if ( get_option( 'alg_wc_pvbur_version', '' ) !== $this->version ) {
			add_action( 'admin_init', array( $this, 'version_updated' ) );
		}
	}

	/**
	 * Add Product Visibility by User Role settings tab to WooCommerce settings.
	 *
	 * @version 1.4.0
	 * @since   1.0.0
	 */
	function add_woocommerce_settings_tab( $settings ) {
		$settings[] = require_once( 'includes/settings/class-alg-wc-settings-pvbur.php' );
		return $settings;
	}

	/**
	 * version_updated.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function version_updated() {
		update_option( 'alg_wc_pvbur_version', $this->version );
	}

	/**
	 * Get the plugin url.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  string
	 */
	function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  string
	 */
	function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

}

endif;

if ( ! function_exists( 'alg_wc_pvbur' ) ) {
	/**
	 * Returns the main instance of Alg_WC_PVBUR to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  Alg_WC_PVBUR
	 */
	function alg_wc_pvbur() {
		return Alg_WC_PVBUR::instance();
	}
}

alg_wc_pvbur();
