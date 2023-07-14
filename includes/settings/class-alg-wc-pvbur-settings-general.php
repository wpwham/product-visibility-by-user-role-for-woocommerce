<?php
/**
 * Product Visibility by User Role for WooCommerce - General Section Settings
 *
 * @version 1.8.1
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 * @author  WP Wham
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Settings_General' ) ) :

class Alg_WC_PVBUR_Settings_General extends Alg_WC_PVBUR_Settings_Section {
	
	public $id   = '';
	public $desc = '';
	
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
	 * get_settings.
	 *
	 * @version 1.7.3
	 * @since   1.0.0
	 * @todo    [dev] (maybe) add "Admin" section
	 */
	public static function get_settings() {
		$main_settings = array(
			array(
				'title'    => __( 'Product Visibility by User Role Options', 'product-visibility-by-user-role-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_pvbur_options',
			),
			array(
				'title'    => __( 'Product Visibility by User Role', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => '<strong>' . __( 'Enable plugin', 'product-visibility-by-user-role-for-woocommerce' ) . '</strong>',
				'desc_tip' => 
					__( 'Product Visibility by User Role for WooCommerce', 'product-visibility-by-user-role-for-woocommerce' )
					. ' v' . WPWHAM_PRODUCT_VISIBILITY_BY_USER_ROLE_VERSION . '.<br />'
					. '<a href="https://wpwham.com/documentation/product-visibility-by-user-role-for-woocommerce/?utm_source=documentation_link&utm_campaign=free&utm_medium=product_visibility_user_role" target="_blank" class="button">' .
					__( 'Documentation', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>',
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
					__( 'To set user roles for each product, check "Product visibility" meta box on each product\'s edit page.', 'product-visibility-by-user-role-for-woocommerce' ) .
				'</strong>',
				'type'     => 'title',
				'id'       => 'alg_wc_pvbur_general_options',
			),
			array(
				'title'    => __( 'Hide catalog visibility', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will hide selected products in shop and search results only. Hidden products will still be accessible via direct link, unless "Modify query" is enabled.', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_visibility',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Make non-purchasable', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will make selected products non-purchasable (i.e. product can\'t be added to the cart).', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_purchasable',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Modify query', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will hide selected products completely. Accessing via direct link will return an "error 404 / product not found" page.', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_query',
				'default'  => 'yes',
				'type'     => 'checkbox',
			),
			array(
				'title'    => __( 'Hide menu items', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'Hides nav menu items (i.e. hidden products, product categories and tags).', 'product-visibility-by-user-role-for-woocommerce' ) . ' ' .
					sprintf( __( 'Only products, product categories/tags marked in <a href="%s">bulk settings</a> will be hidden.', 'product-visibility-by-user-role-for-woocommerce' ),
						admin_url( 'admin.php?page=wc-settings&tab=alg_wc_pvbur&section=bulk' ) ) .
					'<br />' . __( 'This option uses the <code>wp_get_nav_menu_items</code> filter.', 'product-visibility-by-user-role-for-woocommerce' ) .
					apply_filters( 'alg_wc_pvbur',
						'<br>' . sprintf( 
							__( 'You will need %s plugin to enable this option.', 'product-visibility-by-user-role-for-woocommerce' ),
							'<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/?utm_source=settings_general&utm_campaign=free&utm_medium=product_visibility_user_role" target="_blank">' . __( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>' 
						),
						'settings' 
					),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_hide_menu_items',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'disabled' => 'disabled' ), 'settings' ),
			),
			array(
				'title'    => __( 'Hide product categories/tags', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'Hides product categories and tags from being displayed on front-end.', 'product-visibility-by-user-role-for-woocommerce' ) . ' ' .
					sprintf( __( 'Only categories/tags marked in <a href="%s">bulk settings</a> will be hidden.', 'product-visibility-by-user-role-for-woocommerce' ),
						admin_url( 'admin.php?page=wc-settings&tab=alg_wc_pvbur&section=bulk' ) ) .
					'<br />' . __( 'Accessing via direct link will return an "error 404 / category not found" page.', 'product-visibility-by-user-role-for-woocommerce' ) .
					apply_filters( 'alg_wc_pvbur',
						'<br>' . sprintf( 
							__( 'You will need %s plugin to enable this option.', 'product-visibility-by-user-role-for-woocommerce' ),
							'<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/?utm_source=settings_general&utm_campaign=free&utm_medium=product_visibility_user_role" target="_blank">' . __( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>' 
						),
						'settings' 
					),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_hide_product_terms',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'disabled' => 'disabled' ), 'settings' ),
			),
			array(
				'title'    => __( 'Redirect', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => sprintf( __( 'Leave blank for no redirect.  This option is useful only if <strong>%s</strong> is enabled', 'product-visibility-by-user-role-for-woocommerce' ),
					__( 'Modify query', 'product-visibility-by-user-role-for-woocommerce' ) ),
				'desc'     => __( 'Instead of showing your 404 page, redirect to a custom URL.  Applies only to products, product categories, and tags which were made invisible by our plugin.', 'product-visibility-by-user-role-for-woocommerce' ) .
					apply_filters( 'alg_wc_pvbur',
						'<br>' . sprintf( 
							__( 'You will need %s plugin to enable this option.', 'product-visibility-by-user-role-for-woocommerce' ),
							'<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/?utm_source=settings_general&utm_campaign=free&utm_medium=product_visibility_user_role" target="_blank">' . __( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>' 
						),
						'settings' 
					),
				'id'       => 'alg_wc_pvbur_redirect',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'width:100%;',
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'readonly' => 'readonly' ), 'settings' ),
			),
			array(
				'desc'     => __( 'Enable Redirect URLs per product', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will add a "Product visibility: Redirect URL" metabox to each product\'s edit page, allowing you to set different redirects per product.', 'product-visibility-by-user-role-for-woocommerce' ) .
					apply_filters( 'alg_wc_pvbur',
						'<br>' . sprintf( 
							__( 'You will need %s plugin to enable this option.', 'product-visibility-by-user-role-for-woocommerce' ),
							'<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/?utm_source=settings_general&utm_campaign=free&utm_medium=product_visibility_user_role" target="_blank">' . __( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>' 
						),
						'settings' 
					),
				'id'       => 'alg_wc_pvbur_redirect_per_product',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'disabled' => 'disabled' ), 'settings' ),
			),
			array(
				'title'    => __( 'Replace description', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will replace the content in the "Description" tab for the selected products.', 'product-visibility-by-user-role-for-woocommerce' ) .
					apply_filters( 'alg_wc_pvbur',
						'<br>' . sprintf( 
							__( 'You will need %s plugin to enable this option.', 'product-visibility-by-user-role-for-woocommerce' ),
							'<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/?utm_source=settings_general&utm_campaign=free&utm_medium=product_visibility_user_role" target="_blank">' . __( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>' 
						),
						'settings' 
					),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_replace_content',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'disabled' => 'disabled' ), 'settings' ),
			),
			array(
				'desc'     => sprintf( __( 'Content to replace with, e.g.: %s', 'product-visibility-by-user-role-for-woocommerce' ),
					'<code>' .
						esc_html(
							'<strong>' .
								sprintf( __( '<a target="_blank" href="%s">Log in</a> to see the product description.', 'product-visibility-by-user-role-for-woocommerce' ),
									wp_login_url() ) .
							'</strong>'
						) .
					'</code>'
				),
				'id'       => 'alg_wc_pvbur_content',
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:100%;height:150px;',
				'alg_wc_pvbur_raw' => true,
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'readonly' => 'readonly' ), 'settings' ),
			),
			array(
				'title'    => __( 'Replace short description', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'This will replace the content in the "Product Short Description" tab for the selected products.', 'product-visibility-by-user-role-for-woocommerce' ) .
					apply_filters( 'alg_wc_pvbur',
						'<br>' . sprintf( 
							__( 'You will need %s plugin to enable this option.', 'product-visibility-by-user-role-for-woocommerce' ),
							'<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/?utm_source=settings_general&utm_campaign=free&utm_medium=product_visibility_user_role" target="_blank">' . __( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>' 
						),
						'settings' 
					),
				'desc'     => __( 'Enable', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_replace_short_content',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'disabled' => 'disabled' ), 'settings' ),
			),
			array(
				'desc'     => sprintf( __( 'Content to replace with, e.g.: %s', 'product-visibility-by-user-role-for-woocommerce' ),
					'<code>' .
						esc_html(
							'<strong>' .
								sprintf( __( '<a target="_blank" href="%s">Log in</a> to see the product description.', 'product-visibility-by-user-role-for-woocommerce' ),
									wp_login_url() ) .
							'</strong>'
						) .
					'</code>'
				),
				'id'       => 'alg_wc_pvbur_short_content',
				'default'  => '',
				'type'     => 'textarea',
				'css'      => 'width:100%;height:150px;',
				'alg_wc_pvbur_raw' => true,
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'readonly' => 'readonly' ), 'settings' ),
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
				'title'    => __( 'User roles to display in settings', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => __( 'Leave blank to show all available user roles.', 'product-visibility-by-user-role-for-woocommerce' ),
				'id'       => 'alg_wc_pvbur_user_roles_for_settings',
				'default'  => array(),
				'type'     => 'multiselect',
				'class'    => 'chosen_select',
				'options'  => alg_wc_pvbur_get_user_roles(),
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
					apply_filters( 'alg_wc_pvbur',
						'<br>' . sprintf( 
							__( 'You will need %s plugin to enable this option.', 'product-visibility-by-user-role-for-woocommerce' ),
							'<a href="https://wpwham.com/products/product-visibility-by-user-role-for-woocommerce/?utm_source=settings_general&utm_campaign=free&utm_medium=product_visibility_user_role" target="_blank">' . __( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) . '</a>' 
						),
						'settings' 
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
		return array_merge( $main_settings, $general_settings, $admin_settings );
	}

}

endif;

return new Alg_WC_PVBUR_Settings_General();
