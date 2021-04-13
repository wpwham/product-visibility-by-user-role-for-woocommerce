<?php
/**
 * Product Visibility by User Role for WooCommerce - 3rd Party Compatibility Class
 *
 * @version 1.7.1
 * @since   1.7.1
 * @author  WP Wham
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPWham_PVUR_Third_Party_Compatibility' ) ):

class WPWham_PVUR_Third_Party_Compatibility {
	
	protected static $instance = null;
	
	/**
	 * Constructor.
	 *
	 * @version 1.7.1
	 * @since   1.7.1
	 */
	public function __construct() {
		
		// WooCommerce Composite Products
		add_filter( 'woocommerce_composite_component_options_query_args', array( $this, 'woocommerce_composite_products_flag_query' ), 10, 3 );
		add_filter( 'alg_wc_pvbur_can_search', array( $this, 'woocommerce_composite_products_modify_query' ), 10, 2 );
		
	}
	
	public static function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * WooCommerce Composite Products compatibility.
	 * Flag queries initiated by WooCommerce Composite Products plugin, so we can handle them differently later on.
	 *
	 * @version 1.7.1
	 * @since   1.7.1
	 */
	public function woocommerce_composite_products_flag_query( $args, $query_args, $component_data ) {
		$args['wpwham_is_composite_product_query'] = true;
		return $args;
	}
	
	/**
	 * WooCommerce Composite Products compatibility.
	 * If query was flagged, don't modify query.
	 *
	 * @version 1.7.1
	 * @since   1.7.1
	 */
	public function woocommerce_composite_products_modify_query( $can_modify, $query ) {
		if ( $query->get( 'wpwham_is_composite_product_query' ) ) {
			$can_modify = false;
		}
		return $can_modify;
	}
	
}

endif;
