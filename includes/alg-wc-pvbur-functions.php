<?php
/**
 * Product Visibility by User Role for WooCommerce - Functions
 *
 * @version 1.6.0
 * @since   1.1.0
 * @author  Algoritmika Ltd.
 * @author  WP Wham
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'alg_wc_pvbur_get_user_roles_for_settings' ) ) {
	/**
	 * alg_wc_pvbur_get_user_roles_for_settings.
	 *
	 * @version 1.3.0
	 * @since   1.3.0
	 */
	function alg_wc_pvbur_get_user_roles_for_settings() {
		$user_roles_for_settings = get_option( 'alg_wc_pvbur_user_roles_for_settings', array() );
		$user_roles_all          = alg_wc_pvbur_get_user_roles();
		if ( ! empty( $user_roles_for_settings ) ) {
			$user_roles_for_settings_return = array();
			foreach ( $user_roles_for_settings as $user_role ) {
				$user_roles_for_settings_return[ $user_role ] = ( isset( $user_roles_all[ $user_role ] ) ? $user_roles_all[ $user_role ] : $user_role );
			}
			return $user_roles_for_settings_return;
		} else {
			return $user_roles_all;
		}
	}
}

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

if ( ! function_exists( 'alg_wc_pvbur_get_all_products' ) ) {
	/**
	 * Get all products
	 *
	 * @version 1.6.0
	 * @since   1.5.3
	 */
	function alg_wc_pvbur_get_all_products() {
		$args = array(
			'fields'           => 'ids',
			'post_type'        => array( 'product', 'product_variation' ),
			'post_status'      => 'publish',
			'posts_per_page'   => '-1',
			'suppress_filters' => true,
			'meta_query'       => array()
		);
		$query = new WP_Query( $args );
		return $query;
	}
}

if ( ! function_exists( 'alg_wc_pvbur_get_all_products_ids' ) ) {
	/**
	 * Get all products ids
	 *
	 * @version 1.6.0
	 * @since   1.5.3
	 */
	function alg_wc_pvbur_get_all_products_ids( $cache = true ) {
		if ( $cache ) {
			if ( false === ( $product_ids = get_transient( 'awcpvbur_all_pids' ) ) ) {
				$products    = alg_wc_pvbur_get_all_products();
				$product_ids = $products->posts;
				set_transient( 'awcpvbur_all_pids', $product_ids );
			}
		} else {
			$products    = alg_wc_pvbur_get_all_products();
			$product_ids = $products->posts;
		}

		return $product_ids;
	}
}

if ( ! function_exists( 'alg_wc_pvbur_is_visible' ) ) {
	/**
	 * is_visible.
	 *
	 * @version 1.6.1
	 * @since   1.1.0
	 */
	function alg_wc_pvbur_is_visible( $current_user_roles, $product_id ) {
		// Per product
		$roles = get_post_meta( $product_id, '_' . 'alg_wc_pvbur_visible', true );
		if ( is_array( $roles ) && ! empty( $roles ) ) {
			$_intersect = array_intersect( $roles, $current_user_roles );
			if ( empty( $_intersect ) ) {
				// product says which roles can see it, and you ain't one of them
				return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
			} else {
				// product says which roles can see it, and you are one of them
				return alg_wc_pvbur_trigger_is_visible_filter( true, $current_user_roles, $product_id );
			}
		}
		$roles = get_post_meta( $product_id, '_' . 'alg_wc_pvbur_invisible', true );
		if ( is_array( $roles ) && ! empty( $roles ) ) {
			$_intersect = array_intersect( $roles, $current_user_roles );
			if ( empty( $_intersect ) ) {
				// product says which roles can't see it, and you ain't one of them
				return alg_wc_pvbur_trigger_is_visible_filter( true, $current_user_roles, $product_id );
			} else {
				// product says which roles can see it, and you are one of them
				return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
			}
		}
		// Bulk
		if ( 'yes' === apply_filters( 'alg_wc_pvbur', 'no', 'bulk_settings' ) ) {
			$visible_products = array();
			foreach ( $current_user_roles as $user_role_id ) {
				$visible_products = array_merge(
					$visible_products,
					get_option( 'alg_wc_pvbur_bulk_visible_products_' . $user_role_id, array() )
				);
			}
			if ( ! empty( $visible_products ) ) {
				if ( ! in_array( $product_id, $visible_products ) ) {
					return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
				}
			}
			$invisible_products = array();
			foreach ( $current_user_roles as $user_role_id ) {
				$invisible_products = array_merge(
					$invisible_products,
					get_option( 'alg_wc_pvbur_bulk_invisible_products_' . $user_role_id, array() )
				);
			}
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
				$visible_terms = array();
				foreach ( $current_user_roles as $user_role_id ) {
					$visible_terms = array_merge(
						$visible_terms,
						get_option( 'alg_wc_pvbur_bulk_visible_' . $taxonomy . 's_' . $user_role_id, array() )
					);
				}
				if ( ! empty( $visible_terms ) ) {
					$_intersect = array_intersect( $visible_terms, $product_terms_ids );
					if ( empty( $_intersect ) ) {
						return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
					}
				}
				$invisible_terms = array();
				foreach ( $current_user_roles as $user_role_id ) {
					$invisible_terms = array_merge(
						$invisible_terms,
						get_option( 'alg_wc_pvbur_bulk_invisible_' . $taxonomy . 's_' . $user_role_id, array() )
					);
				}
				if ( ! empty( $invisible_terms ) ) {
					$_intersect = array_intersect( $invisible_terms, $product_terms_ids );
					if ( ! empty( $_intersect ) ) {
						return alg_wc_pvbur_trigger_is_visible_filter( false, $current_user_roles, $product_id );
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

if ( ! function_exists( 'wpw_pvbur_clear_cache' ) ) {
	/**
	 * wpw_pvbur_clear_cache.
	 *
	 * @since   1.5.4
	 */
	function wpw_pvbur_clear_cache() {
		global $wpdb;
		return $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_awcpvbur_%'" );
	}
}
