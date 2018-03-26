<?php
/**
 * Product Visibility by User Role for WooCommerce - General Section Settings
 *
 * @version 1.1.5
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Settings_General' ) ) :

class Alg_WC_PVBUR_Settings_General extends Alg_WC_PVBUR_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->id   = '';
		$this->desc = __( 'General', 'product-visibility-by-user-role-for-woocommerce' );
		parent::__construct();
	}

	/**
	 * add_settings.
	 *
	 * @version 1.1.5
	 * @since   1.0.0
	 */
	function add_settings( $settings ) {
		$main_settings = array(
			array(
				'title'    => __( 'Product Visibility by User Role Options', 'product-visibility-by-user-role-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_pvbur_options',
			),
			array(
				'title'    => __( 'Product Visibility by User Role for WooCommerce', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable plugin', 'product-visibility-by-user-role-for-woocommerce' ) . '</strong>',
				'id'       => 'alg_wc_pvbur_enabled',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_pvbur_options',
			),
		);
		$general_settings = array(
			array(
				'title'    => __( 'General Options', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => '<strong>' .
					__( 'To set user roles for each product, check "Product visibility" meta box on each product\'s edit page.',
						'product-visibility-by-user-role-for-woocommerce' ) .
				'</strong>',
				'type'     => 'title',
				'id'       => 'alg_wc_pvbur_general_options',
			),
			array(
				'title'    => __( 'Hide catalog visibility', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will hide selected products in shop and search results. However product still will be accessible via direct link.',
					'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_visibility',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Make non-purchasable', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will make selected products non-purchasable (i.e. product can\'t be added to the cart).',
					'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_purchasable',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Modify query', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will hide selected products completely (including direct link).',
					'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_query',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_pvbur_general_options',
			),
		);
		$admin_settings = array(
			array(
				'title'    => __( 'Admin Options', 'product-visibility-by-user-role-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_pvbur_admin_options',
			),
			array(
				'title'    => __( 'Admin products list column', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will add "User Roles" column to the admin products list.', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'Add', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_add_column_visible_user_roles',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Product quick edit', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will add "User Roles" options to the product quick edit screen.', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'Add', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_add_to_quick_edit',
				'default'  => 'no',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Products bulk edit', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will add "User Roles" options to the products bulk edit screen.', 'product-visibility-by-user-role-for-woocommerce' ) .
					apply_filters( 'alg_wc_pvbur', '<br>' . sprintf( __( 'You will need %s plugin to enable this option.', 'product-visibility-by-user-role-for-woocommerce' ),
					'<a href="https://wpcodefactory.com/item/product-visibility-by-user-role-for-woocommerce/" target="_blank">' .
						__( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) .
					'</a>' ), 'settings'
				),
				'desc'     => __( 'Add', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_add_to_bulk_edit',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'disabled' => 'disabled' ), 'settings' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_pvbur_admin_options',
			),
		);
		return array_merge( $main_settings, $general_settings, $admin_settings, $settings );
	}

}

endif;

return new Alg_WC_PVBUR_Settings_General();
