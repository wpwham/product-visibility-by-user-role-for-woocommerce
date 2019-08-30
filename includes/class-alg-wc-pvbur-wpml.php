<?php
/**
 * Product Visibility by User Role for WooCommerce - WPML
 *
 * @version 1.2.3
 * @since   1.2.3
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_WPML' ) ) :

class Alg_WC_PVBUR_WPML {

	public $pre_query;

	function __construct() {
		add_action( 'alg_wc_pvbur_hide_products_query', array( $this, 'fix_wpml_query' ), 10, 2 );
	}

	/**
	 * Fixes wpml query
	 *
	 * @version 1.2.3
	 * @since   1.2.3
	 *
	 * @param $query
	 * @param $invisible_product_ids
	 */
	function fix_wpml_query( $query, $invisible_product_ids ) {
		if (
			function_exists('icl_object_id') &&
			! empty( $query->get( 'p' ) ) &&
			! empty( $invisible_product_ids ) &&
			in_array( $query->get( 'p' ), $invisible_product_ids )
		) {
			$query->set( 'p', 0 );
			$query->query_vars['p']    = 0;
			$query->query_vars['name'] = $query->query['name'];
		}
	}
}

endif;

return new Alg_WC_PVBUR_WPML();
