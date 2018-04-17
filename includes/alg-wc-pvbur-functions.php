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

if ( ! function_exists( 'alg_wc_pvbur_trigger_is_visible_filter' ) ) {
	/**
	 * Triggers the is_visible filter.
	 *
	 * @version 1.1.6
	 * @since   1.1.0
	 *
	 * @param $is_visible
	 * @param $current_user_roles
	 * @param $product_id
	 *
	 * @return mixed|void
	 */
	function alg_wc_pvbur_trigger_is_visible_filter( $is_visible, $current_user_roles, $product_id ){
		return apply_filters( 'alg_wc_pvbur_is_visible', $is_visible, $current_user_roles, $product_id );
	}
}

if ( ! function_exists( 'alg_wc_pvbur_product_is_visible' ) ) {
	/**
	 * Checks if product is visible
	 *
	 * @version 1.1.6
	 * @since   1.1.0
	 */
	function alg_wc_pvbur_product_is_visible( $current_user_roles, $product_id ) {
		// Per product
		$roles = get_post_meta( $product_id, '_' . 'alg_wc_pvbur_visible', true );
		if ( is_array( $roles ) && ! empty( $roles ) ) {
			$_intersect = array_intersect( $roles, $current_user_roles );
			if ( empty( $_intersect ) ) {
				return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
			}
		}
		$roles = get_post_meta( $product_id, '_' . 'alg_wc_pvbur_invisible', true );
		if ( is_array( $roles ) && ! empty( $roles ) ) {
			$_intersect = array_intersect( $roles, $current_user_roles );
			if ( ! empty( $_intersect ) ) {
				return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
			}
		}
		// Bulk
		if ( 'yes' === apply_filters( 'alg_wc_pvbur', 'no', 'bulk_settings' ) ) {
			foreach ( $current_user_roles as $user_role_id ) {
				$visible_products = get_option( 'alg_wc_pvbur_bulk_visible_products_' . $user_role_id, '' );
				if ( ! empty( $visible_products ) ) {
					if ( ! in_array( $product_id, $visible_products ) ) {
						return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
					}
				}
				$invisible_products = get_option( 'alg_wc_pvbur_bulk_invisible_products_' . $user_role_id, '' );
				if ( ! empty( $invisible_products ) ) {
					if ( in_array( $product_id, $invisible_products ) ) {
						return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
					}
				}
				$taxonomies = array( 'product_cat', 'product_tag' );
				foreach ( $taxonomies as $taxonomy ) {
					// Getting product terms
					$product_terms_ids = array();
					$_terms = get_the_terms( $product_id, $taxonomy );
					if ( ! empty( $_terms ) ) {
						foreach( $_terms as $_term ) {
							$product_terms_ids[] = $_term->term_id;
						}
					}
					// Checking
					$visible_terms = get_option( 'alg_wc_pvbur_bulk_visible_' . $taxonomy . 's_' . $user_role_id, '' );
					if ( ! empty( $visible_terms ) ) {
						$_intersect = array_intersect( $visible_terms, $product_terms_ids );
						if ( empty( $_intersect ) ) {
							return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
						}
					}
					$invisible_terms = get_option( 'alg_wc_pvbur_bulk_invisible_' . $taxonomy . 's_' . $user_role_id, '' );
					if ( ! empty( $invisible_terms ) ) {
						$_intersect = array_intersect( $invisible_terms, $product_terms_ids );
						if ( ! empty( $_intersect ) ) {
							return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
						}
					}
				}
			}
		}
		return alg_wc_pvbur_trigger_is_visible_filter( true, $current_user_roles, $product_id );
	}
}

if ( ! function_exists( 'alg_wc_pvbur_get_current_user_all_roles' ) ) {
	/**
	 * get_current_user_all_roles.
	 *
	 * @version 1.1.4
	 * @since   1.0.0
	 */
	function alg_wc_pvbur_get_current_user_all_roles() {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			require_once( ABSPATH . 'wp-includes/pluggable.php' );
		}
		$current_user = wp_get_current_user();
		return ( ! empty( $current_user->roles ) ) ? $current_user->roles : array( 'guest' );
	}
}