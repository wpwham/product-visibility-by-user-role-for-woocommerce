<?php
/**
 * Product Visibility by User Role for WooCommerce - Functions
 *
 * @version 1.2.1
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

if ( ! function_exists( 'alg_wc_pvbur_get_invisible_products' ) ) {
	/**
	 * Get invisible products
	 *
	 * @version 1.1.9
	 * @since   1.1.9
	 */
	function alg_wc_pvbur_get_invisible_products( $roles = array() ) {
		$query = new WP_Query( alg_wc_pvbur_get_invisible_products_query_args( $roles ) );

		return $query;
	}
}

if ( ! function_exists( 'alg_wc_pvbur_get_invisible_products_ids' ) ) {
	/**
	 * Get invisible products ids
	 *
	 * @version 1.2.1
	 * @since   1.2.1
	 */
	function alg_wc_pvbur_get_invisible_products_ids( $roles = array(), $cache = true ) {
		if ( $cache ) {
			$invisible_products_ids_query_name = "awcpvbur_inv_pids_" . md5( implode( "_", $roles ) );
			if ( false === ( $invisible_product_ids = get_transient( $invisible_products_ids_query_name ) ) ) {
				$invisible_products    = alg_wc_pvbur_get_invisible_products( $roles );
				$invisible_product_ids = $invisible_products->posts;
				set_transient( $invisible_products_ids_query_name, $invisible_product_ids );
			}
		} else {
			$invisible_products    = alg_wc_pvbur_get_invisible_products( $roles );
			$invisible_product_ids = $invisible_products->posts;
		}

		return $invisible_product_ids;
	}
}

if ( ! function_exists( 'alg_wc_pvbur_get_invisible_products_query_args' ) ) {
	/**
	 * alg_wc_pvbur_get_invisible_products_query_args
	 *
	 * @version 1.1.9
	 * @since   1.1.9
	 */
	function alg_wc_pvbur_get_invisible_products_query_args( $roles = array() ) {
		$query_args = array(
			'fields'         => 'ids',
			'post_type'      => 'product',
			'posts_per_page' => '-1',
			'meta_query'     => array()
		);

		$invisible_meta_query = array();
		$visible_meta_query   = array();

		if ( count( $roles ) > 1 ) {
			$invisible_meta_query['relation'] = 'OR';
		}

		foreach ( $roles as $role ) {
			$invisible_meta_query[] = array(
				'key'     => '_alg_wc_pvbur_invisible',
				'value'   => '"' . $role . '"',
				'compare' => 'LIKE',
			);
		}

		foreach ( $roles as $role ) {
			$visible_meta_query[] = array(
				'key'     => '_alg_wc_pvbur_visible',
				'value'   => '"' . $role . '"',
				'compare' => 'NOT LIKE',
			);
		}

		$visible_meta_query[] = array(
			'key'     => '_alg_wc_pvbur_visible',
			'value'   => 'i:0;',
			'compare' => 'LIKE',
		);

		$query_args['meta_query']['relation'] = 'OR';
		$query_args['meta_query'][]           = $invisible_meta_query;
		$query_args['meta_query'][]           = $visible_meta_query;

		return $query_args;
	}
}



if ( ! function_exists( 'alg_wc_pvbur_is_visible' ) ) {
	/**
	 * is_visible.
	 *
	 * @version 1.2.0
	 * @since   1.1.0
	 */
	function alg_wc_pvbur_is_visible( $current_user_roles, $product_id ) {
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
					$_terms            = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'ids' ) );
					if ( ! empty( $_terms ) ) {
						foreach ( $_terms as $_term ) {
							$product_terms_ids[] = $_term;
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
	function alg_wc_pvbur_trigger_is_visible_filter( $is_visible, $current_user_roles, $product_id ) {
		return apply_filters( 'alg_wc_pvbur_is_visible', $is_visible, $current_user_roles, $product_id );
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