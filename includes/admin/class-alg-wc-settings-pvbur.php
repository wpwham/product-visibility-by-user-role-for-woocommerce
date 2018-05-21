<?php
/**
 * Product Visibility by User Role for WooCommerce - Settings
 *
 * @version 1.1.9
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Settings_PVBUR' ) ) :

class Alg_WC_Settings_PVBUR extends WC_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @version 1.1.3
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id    = 'alg_wc_pvbur';
		$this->label = __( 'Product Visibility', 'product-visibility-by-user-role-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * get_settings.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function get_settings() {
		global $current_section;
		return apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $current_section, array() );
	}

	/**
	 * maybe_reset_settings.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function maybe_reset_settings() {
		global $current_section;
		if ( 'yes' === get_option( $this->id . '_' . $current_section . '_reset', 'no' ) ) {
			foreach ( $this->get_settings() as $value ) {
				if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
					delete_option( $value['id'] );
					$autoload = isset( $value['autoload'] ) ? ( bool ) $value['autoload'] : true;
					add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
				}
			}
		}
	}

	/**
	 * Save settings.
	 *
	 * @version 1.1.9
	 * @since   1.0.0
	 */
	function save() {
		global $current_section;
		if ( $current_section == 'bulk' ) {
			if ( 'yes' === apply_filters( 'alg_wc_pvbur', 'no', 'premium_version' ) ) {
				parent::save();
				$this->maybe_reset_settings();
			}
		} else {
			parent::save();
			$this->maybe_reset_settings();
		}
	}

	/**
	 * Output sections.
	 *
	 * @version 1.1.2
	 * @since   1.1.2
	 */
	public function output_sections() {
		parent::output_sections();
		global $current_section;
		do_action( 'alg_wc_pvbur_output_sections_' . $current_section );
	}

}

endif;

return new Alg_WC_Settings_PVBUR();
