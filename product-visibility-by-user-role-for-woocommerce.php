<?php
/*
Plugin Name: Product Visibility by User Role for WooCommerce
Plugin URI: https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/
Description: Display WooCommerce products by customer's user role.
Version: 1.6.0
Author: WP Wham
Author URI: https://wpwham.com/
Text Domain: product-visibility-by-user-role-for-woocommerce
Domain Path: /langs
Copyright: © 2019-2020 WP Wham
WC tested up to: 4.4
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

if ( ! class_exists( 'Alg_WC_PVBUR' ) ) :

/**
 * Main Alg_WC_PVBUR Class
 *
 * @class   Alg_WC_PVBUR
 * @version 1.4.0
 * @since   1.0.0
 */
final class Alg_WC_PVBUR {

	/**
	 * Plugin version.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	public $version = '1.6.0';

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
	 * @version 1.2.4
	 * @since   1.0.0
	 * @param   mixed $links
	 * @return  array
	 */
	function action_links( $links ) {
		$custom_links = array();
		$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_pvbur' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>';
		if ( 'product-visibility-by-user-role-for-woocommerce.php' === basename( __FILE__ ) ) {
			$custom_links[] = '<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/">' .
				__( 'Unlock All', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>';
		}
		return array_merge( $custom_links, $links );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @version 1.6.0
	 * @since   1.0.0
	 */
	function includes() {
		require_once( 'includes/alg-wc-pvbur-functions.php' );
		$this->core = require_once( 'includes/class-alg-wc-pvbur-core.php' );
		if ( 'product-visibility-by-user-role-for-woocommerce-pro.php' === basename( __FILE__ ) ) {
			require_once( 'includes/pro/alg-wc-pvbur-pro-functions.php' );
			$this->core_pro = require_once( 'includes/pro/class-alg-wc-pvbur-pro-core.php' );
		}
		require_once( 'includes/class-alg-wc-pvbur-wpml.php' );
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
	 * @version 1.6.0
	 * @since   1.4.0
	 */
	function admin() {
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
