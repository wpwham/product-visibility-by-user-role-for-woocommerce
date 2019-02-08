<?php
/**
 * Product Visibility by User Role for WooCommerce Pro - Functions
 *
 * @version 1.2.5
 * @since   1.2.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'alg_wc_pvbur_get_invisible_product_terms' ) ) {

	/**
	 * Gets invisible product terms
	 *
	 * @version 1.2.5
	 * @since   1.2.0
	 *
	 * @param string $taxonomy
	 * @param string $role
	 *
	 * @return array
	 */
	function alg_wc_pvbur_get_invisible_product_terms( $taxonomy = 'product_cat', $role = 'current_role' ) {

		if ( $role == 'current_role' ) {
			$user_roles = alg_wc_pvbur_get_current_user_all_roles();
		} else {
			if ( is_string( $role ) ) {
				$user_roles = array( $role );
			} else {
				$user_roles = $role;
			}
		}

		$invisible_all = array();
		foreach ( $user_roles as $role ) {
			if ( $taxonomy == 'product_cat' ) {
				$invisible = get_option( "alg_wc_pvbur_bulk_invisible_product_cats_{$role}", array() );
				$invisible = empty( $invisible ) ? array() : $invisible;
			} else {
				$invisible = get_option( "alg_wc_pvbur_bulk_invisible_product_tags_{$role}", array() );
				$invisible = empty( $invisible ) ? array() : $invisible;
			}
			if ( ! empty( $invisible ) ) {
				$invisible_all = array_merge( $invisible_all, $invisible );
			}
		}
		$invisible_all = array_unique( $invisible_all );

		return $invisible_all;
	}
}

if ( ! function_exists( 'alg_wc_pvbur_get_visible_product_terms' ) ) {

	/**
	 * Gets visible product terms
	 * @version 1.2.5
	 * @since   1.2.0
	 *
	 * @param string $taxonomy
	 * @param string $role
	 *
	 * @return array
	 */
	function alg_wc_pvbur_get_visible_product_terms( $taxonomy = 'product_cat', $role = 'current_role' ) {

		if ( $role == 'current_role' ) {
			$user_roles = alg_wc_pvbur_get_current_user_all_roles();
		} else {
			if ( is_string( $role ) ) {
				$user_roles = array( $role );
			} else {
				$user_roles = $role;
			}
		}

		$visible_all = array();
		foreach ( $user_roles as $role ) {
			if ( $taxonomy == 'product_cat' ) {
				$visible = get_option( "alg_wc_pvbur_bulk_visible_product_cats_{$role}", array() );
				$visible = empty( $visible ) ? array() : $visible;
			} else {
				$visible = get_option( "alg_wc_pvbur_bulk_visible_product_tags_{$role}", array() );
				$visible = empty( $visible ) ? array() : $visible;
			}
			if ( ! empty( $visible ) ) {
				$visible_all = array_merge( $visible_all, $visible );
			}
		}
		$visible_all = array_unique( $visible_all );

		return $visible_all;
	}
}
