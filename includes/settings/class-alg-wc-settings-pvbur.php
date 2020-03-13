<?php
/**
 * Product Visibility by User Role for WooCommerce - Settings
 *
 * @version 1.4.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Settings_PVBUR' ) ) :

class Alg_WC_Settings_PVBUR extends WC_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @version 1.4.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id    = 'alg_wc_pvbur';
		$this->label = __( 'Product Visibility', 'product-visibility-by-user-role-for-woocommerce' );
		parent::__construct();
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'maybe_unsanitize_option' ), PHP_INT_MAX, 3 );
		add_action( 'admin_notices', array( $this, 'settings_saved_admin_notice' ) );
	}

	/**
	 * settings_saved_admin_notice.
	 *
	 * @since   1.5.0
	 */
	function settings_saved_admin_notice() {
		if ( ! empty( $_GET['alg_wc_pvbur_settings_saved'] ) ) {
			WC_Admin_Settings::add_message( __( 'Your settings have been saved.', 'woocommerce' ) );
		}
	}

	/**
	 * maybe_unsanitize_option.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function maybe_unsanitize_option( $value, $option, $raw_value ) {
		return ( ! empty( $option['alg_wc_pvbur_raw'] ) ? $raw_value : $value );
	}

	/**
	 * get_settings.
	 *
	 * @version 1.4.0
	 * @since   1.0.0
	 */
	function get_settings() {
		global $current_section;
		return array_merge( apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $current_section, array() ), array(
			array(
				'title'     => __( 'Reset Settings', 'product-visibility-by-user-role-for-woocommerce' ),
				'type'      => 'title',
				'id'        => $this->id . '_' . $current_section . '_reset_options',
			),
			array(
				'title'     => __( 'Reset section settings', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'      => '<strong>' . __( 'Reset', 'product-visibility-by-user-role-for-woocommerce' ) . '</strong>',
				'id'        => $this->id . '_' . $current_section . '_reset',
				'default'   => 'no',
				'type'      => 'checkbox',
			),
			array(
				'type'      => 'sectionend',
				'id'        => $this->id . '_' . $current_section . '_reset_options',
			),
		) );
	}

	/**
	 * maybe_reset_settings.
	 *
	 * @version 1.4.0
	 * @since   1.0.0
	 */
	function maybe_reset_settings() {
		global $current_section;
		if ( 'yes' === get_option( $this->id . '_' . $current_section . '_reset', 'no' ) ) {
			foreach ( $this->get_settings() as $value ) {
				if ( isset( $value['id'] ) ) {
					$id = explode( '[', $value['id'] );
					delete_option( $id[0] );
				}
			}
			add_action( 'admin_notices', array( $this, 'admin_notice_settings_reset' ) );
		}
	}

	/**
	 * admin_notice_settings_reset.
	 *
	 * @version 1.4.0
	 * @since   1.4.0
	 */
	function admin_notice_settings_reset() {
		echo '<div class="notice notice-warning is-dismissible"><p><strong>' .
			__( 'Your settings have been reset.', 'product-visibility-by-user-role-for-woocommerce' ) . '</strong></p></div>';
	}

	/**
	 * Save settings.
	 *
	 * @version 1.4.0
	 * @since   1.0.0
	 */
	function save() {
		global $current_section;
		if ( $current_section == 'bulk' ) {
			if ( 'yes' === apply_filters( 'alg_wc_pvbur', 'no', 'bulk_settings_active' ) ) {
				parent::save();
				$this->maybe_reset_settings();
			}
		} else {
			parent::save();
			$this->maybe_reset_settings();
		}
		wpw_pvbur_clear_cache();
		wp_safe_redirect( add_query_arg( 'alg_wc_pvbur_settings_saved', true ) );
		exit;
	}

	/**
	 * Output sections.
	 *
	 * @version 1.1.2
	 * @since   1.1.2
	 */
	function output_sections() {
		parent::output_sections();
		global $current_section;
		do_action( 'alg_wc_pvbur_output_sections_' . $current_section );
	}

}

endif;

return new Alg_WC_Settings_PVBUR();
