<?php
/**
 * Product Visibility by User Role for WooCommerce - Functions
 *
 * @version 1.1.0
 * @since   1.1.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'alg_wc_pvbur_get_user_roles' ) ) {
	/**
	 * alg_wc_pvbur_get_user_roles.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function alg_wc_pvbur_get_user_roles() {
		global $wp_roles;
		$all_roles = ( isset( $wp_roles ) && is_object( $wp_roles ) ) ? $wp_roles->roles : array();
		$all_roles = apply_filters( 'editable_roles', $all_roles );
		$all_roles = array_merge( array(
			'guest' => array(
				'name'         => __( 'Guest', 'product-visibility-by-user-role-for-woocommerce' ),
				'capabilities' => array(),
			) ), $all_roles );
		$all_roles_options = array();
		foreach ( $all_roles as $_role_key => $_role ) {
			$all_roles_options[ $_role_key ] = $_role['name'];
		}
		return $all_roles_options;
	}
}
