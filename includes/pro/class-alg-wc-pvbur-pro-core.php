<?php
/**
 * Product Visibility by User Role for WooCommerce - Core Class
 *
 * @version 1.3.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Pro_PVBUR_Core' ) ) :

	class Alg_WC_Pro_PVBUR_Core {

		/**
		 * Constructor.
		 *
		 * @version 1.3.0
		 * @since   1.0.0
		 */
		function __construct() {
			$this->is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
			if ( 'yes' === get_option( 'alg_wc_pvbur_enabled', 'yes' ) ) {

				if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
					add_action( 'template_redirect', array( $this, 'redirect_if_product_is_invisible' ) );
					add_filter( 'wp_get_nav_menu_items', array( $this, 'hide_empty_wp_nav_menu_items' ), 10, 3 );
					add_filter( 'get_terms', array( $this, 'hide_product_terms' ), 10, 4 );
				}

				add_action( 'alg_wc_pvbur_hide_products_query', array( $this, 'hide_all_products' ), 9, 2 );
				add_action( 'alg_wc_pvbur_hide_products_query', array( $this, 'hide_invisible_products_with_bulk_options' ), 10, 2 );
				add_action( 'alg_wc_pvbur_hide_products_query', array( $this, 'fix_wpml_query_bulk' ), 11, 2 );
				add_filter( 'posts_clauses', array( $this, 'fix_singular_tax_query' ), 10, 2 );

				if ( 'yes' === get_option( 'alg_wc_pvbur_redirect_per_product', 'no' ) ) {
					add_action( 'add_meta_boxes',    array( $this, 'add_pvbur_redirect_metabox' ) );
					add_action( 'save_post_product', array( $this, 'save_pvbur_redirect_meta_box' ), PHP_INT_MAX, 2 );
				}
			}
		}

		/**
		 * add_pvbur_redirect_metabox.
		 *
		 * @version 1.3.0
		 * @since   1.3.0
		 * @todo    [dev] add option to existing "Product visibility" meta box (i.e. instead of adding new "Product visibility: Redirect" meta box)
		 */
		function add_pvbur_redirect_metabox() {
			add_meta_box(
				'alg-wc-product-visibility-by-user-role-redirect-meta-box',
				__( 'Product visibility: Redirect URL', 'product-visibility-by-user-role-for-woocommerce' ),
				array( $this, 'display_redirect_pvbur_metabox' ),
				'product',
				'side',
				'low'
			);
		}

		/**
		 * display_redirect_pvbur_metabox.
		 *
		 * @version 1.3.0
		 * @since   1.3.0
		 */
		function display_redirect_pvbur_metabox() {
			echo '<input type="url" name="alg_wc_pvbur_redirect" value="' . get_post_meta( get_the_ID(), '_alg_wc_pvbur_redirect', true ) . '" style="width:100%;">';
		}

		/**
		 * save_pvbur_redirect_meta_box.
		 *
		 * @version 1.3.0
		 * @since   1.3.0
		 */
		function save_pvbur_redirect_meta_box( $post_id, $post ) {
			if ( isset( $_POST[ 'alg_wc_pvbur_redirect' ] ) ) {
				update_post_meta( $post_id, '_alg_wc_pvbur_redirect', $_POST[ 'alg_wc_pvbur_redirect' ] );
			}
		}

		/**
		 * Fixes pre_get_posts when singular is true and tax_query is set.
		 * In other words, fixes singular view if category option is in use on bulk settings
		 *
		 * @version 1.3.0
		 * @since   1.3.0
		 *
		 * @param $clauses
		 * @param $query
		 *
		 * @return mixed
		 */
		public function fix_singular_tax_query( $clauses, $query ) {
			if (
				! $query->is_singular ||
				empty( $query->get( 'pvbur_tax_query' ) ) ||
				! empty( $query->get( 'pvbur_singular_tax_query_fix' ) )
			) {
				return $clauses;
			}
			global $wpdb;
			$query->parse_tax_query( $query->query_vars );
			$tax_clauses      = $query->tax_query->get_sql( $wpdb->posts, 'ID' );
			$clauses['join']  .= $tax_clauses['join'];
			$clauses['where'] .= $tax_clauses['where'];
			$query->set( 'pvbur_singular_tax_query_fix', 'yes' );
			return $clauses;
		}

		/**
		 * Fixes wpml query
		 *
		 * @version 1.2.9
		 * @since   1.2.9
		 *
		 * @param $query
		 * @param $invisible_product_ids
		 */
		public function fix_wpml_query_bulk( $query, $invisible_product_ids ) {
			if (
				! function_exists( 'icl_object_id' ) ||
				! $query->is_single() ||
				empty( $query->get( 'p' ) ) ||
				empty( $query->get( 'post__not_in' ) ) ||
				! in_array( $query->get( 'p' ), $query->get( 'post__not_in' ) )
			) {
				return;
			}
			$query->set( 'p', 0 );
			$query->query_vars['p']    = 0;
			$query->query_vars['name'] = $query->query['name'];
		}

		/**
		 * Hides all products if necessary
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 *
		 * @param $query
		 * @param array $invisible_products_post_meta
		 */
		public function hide_all_products( $query, $invisible_products_post_meta = array() ) {

			if ( 'yes' !== apply_filters( 'alg_wc_pvbur', 'no', 'bulk_settings' ) ) {
				return;
			}

			$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
			foreach ( $current_user_roles as $role ) {
				$hide_all = get_option( "alg_wc_pvbur_bulk_hide_all_from_{$role}", 'no' );
				if ( $hide_all == 'yes' ) {
					$query->set( 'post__in', array( - 1 ) );
					remove_action( 'alg_wc_pvbur_hide_products_query', array( $this, 'hide_invisible_products_with_bulk_options' ), 10, 2 );
				}
			}
		}

		/**
		 * Hides product terms
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 *
		 * @param $terms
		 * @param $taxonomy
		 *
		 * @return mixed
		 */
		public function hide_product_terms( $terms, $taxonomy, $query_vars, $term_query ) {
			if (
				is_admin() ||
				'yes' !== apply_filters( 'alg_wc_pvbur', 'no', 'bulk_settings' ) ||
				! filter_var( apply_filters( 'pvbur_hide_product_terms', filter_var( get_option( 'alg_wc_pvbur_hide_product_terms', false ), FILTER_VALIDATE_BOOLEAN ) ), FILTER_VALIDATE_BOOLEAN ) ||
				count( array_intersect( $taxonomy, array( 'product_cat', 'product_tag' ) ) ) == 0
			) {
				return $terms;
			}

			// Hide all if necessary
			$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
			foreach ( $current_user_roles as $role ) {
				$hide_all = get_option( "alg_wc_pvbur_bulk_hide_all_from_{$role}", 'no' );
				if ( $hide_all == 'yes' ) {
					foreach ( $terms as $key => $term ) {
						if (
							! is_a( $term, 'WP_Term' ) ||
							! in_array( $term->taxonomy, array( 'product_cat', 'product_tag' ) )
						) {
							continue;
						}
						unset( $terms[ $key ] );
					}

					return $terms;
				}
			}

			// Hide only marked categories/tags
			foreach ( $terms as $key => $term ) {
				if (
					! is_a( $term, 'WP_Term' ) ||
					! in_array( $term->taxonomy, array( 'product_cat', 'product_tag' ) )
				) {
					continue;
				}
				$visible   = alg_wc_pvbur_get_visible_product_terms( $term->taxonomy );
				$invisible = alg_wc_pvbur_get_invisible_product_terms( $term->taxonomy );
				if ( ! empty( $invisible ) && in_array( $term->term_id, $invisible ) ) {
					unset( $terms[ $key ] );
				}
				if ( ! empty( $visible ) && ! in_array( $term->term_id, $visible ) ) {
					unset( $terms[ $key ] );
				}
			}

			return $terms;
		}

		/**
		 * Gets visible products from bulk options
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 */
		private function get_bulk_visible_products( $current_user_roles ) {
			$visible = array();
			foreach ( $current_user_roles as $role ) {
				$visible = get_option( "alg_wc_pvbur_bulk_visible_products_{$role}", array() );
				$visible = empty( $visible ) ? array() : $visible;
			}

			return $visible;
		}

		/**
		 * Gets invisible products from bulk options
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 */
		private function get_bulk_invisible_products( $current_user_roles, $invisible_products_post_meta=array() ) {
			$invisible = array();
			foreach ( $current_user_roles as $role ) {
				$invisible = get_option( "alg_wc_pvbur_bulk_invisible_products_{$role}", array() );
				$invisible = empty( $invisible ) ? array() : $invisible;
				$invisible = array_merge( $invisible_products_post_meta, $invisible );
			}

			return $invisible;
		}

		/**
		 * Gets post__in invisible products query
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 */
		private function get_post__in_query( $query, $visible, $invisible ) {
			$post__in = $query->get( 'post__in' );
			$post__in = empty( $post__in ) ? array() : $post__in;
			$post__in = array_merge( array_diff( $visible, $invisible ), $post__in );

			return $post__in;
		}

		/**
		 * Gets post__not_in invisible products query
		 *
		 * @version 1.2.0
		 * @since   1.2.0
		 */
		private function get_post__not_in_query( $query, $invisible=array() ) {
			$post__not_in = $query->get( 'post__not_in' );
			$post__not_in = empty( $post__not_in ) ? array() : $post__not_in;
			$post__not_in = array_merge( $invisible, $post__not_in );

			return $post__not_in;
		}

		/**
		 * Hides invisible products using bulk options
		 *
		 * @version 1.3.0
		 * @since   1.2.0
		 */
		public function hide_invisible_products_with_bulk_options( $query, $invisible_products_post_meta = array() ) {
			if (
				! empty( $query->get( 'pvbur_tax_query' ) ) ||
				'yes' !== apply_filters( 'alg_wc_pvbur', 'no', 'bulk_settings' ) ||
				'yes' !== get_option( 'alg_wc_pvbur_bulk_options_section_enabled', 'no' )
			) {
				return;
			}

			$current_user_roles           = alg_wc_pvbur_get_current_user_all_roles();
			$invisible_products_post_meta = empty( $invisible_products_post_meta ) ? array() : $invisible_products_post_meta;

			// Get visible and invisible from Bulk options
			$visible   = $this->get_bulk_visible_products( $current_user_roles );
			$invisible = $this->get_bulk_invisible_products( $current_user_roles, $invisible_products_post_meta );

			// Create post__in and post__not_in
			$post__in     = array();
			$post__not_in = array();
			if ( ! empty( $visible ) ) {
				$post__in = array_unique( $this->get_post__in_query( $query, $visible, $invisible ) );
			} else {
				$post__not_in = array_unique( $this->get_post__not_in_query( $query, $invisible ) );
			}

			// Set post__in or post__not_in
			if ( ! empty( $post__in ) ) {
				unset( $query->query['post__not_in'] );
				$query->set( 'post__in', $post__in );
			} elseif ( ! empty( $post__not_in ) ) {
				unset( $query->query['post__in'] );
				$query->set( 'post__not_in', $post__not_in );
			}

			// Categories and Tags
			$tax_query = $query->get( 'tax_query' );
			$tax_query = empty( $tax_query ) ? array() : $tax_query;

			$categories_visible = array();
			$categories_invisible = array();
			$tags_visible = array();
			$tags_invisible = array();

			foreach ( $current_user_roles as $role ) {
				if ( '' != ( $_categories_visible = get_option( "alg_wc_pvbur_bulk_visible_product_cats_{$role}", array() ) ) ){
					$categories_visible = array_merge( $_categories_visible, $categories_visible );
				}
				if ( '' != ( $_categories_invisible = get_option( "alg_wc_pvbur_bulk_invisible_product_cats_{$role}", array() ) ) ){
					$categories_invisible = array_merge( $_categories_invisible, $categories_invisible );
				}
				if ( '' != ( $_tags_visible = get_option( "alg_wc_pvbur_bulk_visible_product_tags_{$role}", array() ) ) ){
					$tags_visible = array_merge( $_tags_visible, $tags_visible );
				}
				if ( '' != ( $_tags_invisible = get_option( "alg_wc_pvbur_bulk_invisible_product_tags_{$role}", array() ) ) ){
					$tags_invisible = array_merge( $_tags_invisible, $tags_invisible );
				}
			}

			$tax_query_child = array();
			if ( ! empty( $categories_visible ) ) {
				$tax_query_child[] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_taxonomy_id',
					'terms'    => $categories_visible,
					'operator' => 'IN',
				);
			}

			if ( ! empty( $categories_invisible ) ) {
				$tax_query_child[] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_taxonomy_id',
					'terms'    => $categories_invisible,
					'operator' => 'NOT IN',
				);
			}

			if ( ! empty( $tags_visible ) ) {
				$tax_query_child[] = array(
					'taxonomy' => 'product_tag',
					'field'    => 'term_taxonomy_id',
					'terms'    => $tags_visible,
					'operator' => 'IN',
				);
			}

			if ( ! empty( $tags_invisible ) ) {
				$tax_query_child[] = array(
					'taxonomy' => 'product_tag',
					'field'    => 'term_taxonomy_id',
					'terms'    => $tags_invisible,
					'operator' => 'NOT IN',
				);
			}

			if ( count( $tax_query_child ) > 0 ) {
				$tax_query = array_merge( $tax_query, $tax_query_child );
				$query->set( 'tax_query', $tax_query );
				$query->set( 'pvbur_tax_query', $tax_query );
			}
		}

		/**
		 * Hides nav menus items that have empty product categories for the current user role
		 *
		 * @version 1.3.0
		 * @since   1.1.7
		 *
		 * @param $items
		 * @param $menu
		 * @param $args
		 *
		 * @return mixed
		 */
		function hide_empty_wp_nav_menu_items( $items, $menu, $args ) {
			if (
				'yes' !== apply_filters( 'alg_wc_pvbur', 'no', 'bulk_settings' ) ||
				! filter_var( apply_filters( 'pvbur_hide_menu_items', filter_var( get_option( 'alg_wc_pvbur_hide_menu_items', false ), FILTER_VALIDATE_BOOLEAN ) ), FILTER_VALIDATE_BOOLEAN )
			) {
				return $items;
			}

			$cat_items = wp_list_filter( $items, array(
				'object' => 'product_cat',
			) );

			$tag_items = wp_list_filter( $items, array(
				'object' => 'product_tag',
			) );

			$product_cat_items = $cat_items + $tag_items;

			$products_items = wp_list_filter( $items, array(
				'object' => 'product',
			) );

			// Hide all if necessary
			$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
			foreach ( $current_user_roles as $role ) {
				$hide_all = get_option( "alg_wc_pvbur_bulk_hide_all_from_{$role}", 'no' );
				if ( $hide_all == 'yes' ) {
					foreach ( $product_cat_items as $key => $term ) {
						unset( $items[ $key ] );
					}
					foreach ( $products_items as $key => $term ) {
						unset( $items[ $key ] );
					}

					return $items;
				}
			}

			// Hide only marked categories/tags
			foreach ( $product_cat_items as $key => $item ) {
				$product_cat_or_tag_id = $item->object_id;
				$taxonomy              = $item->object;
				$visible               = alg_wc_pvbur_get_visible_product_terms( $taxonomy );
				$invisible             = alg_wc_pvbur_get_invisible_product_terms( $taxonomy );
				if ( ! empty( $invisible ) && in_array( $product_cat_or_tag_id, $invisible ) ) {
					unset( $items[ $key ] );
				}
				if ( ! empty( $visible ) && ! in_array( $product_cat_or_tag_id, $visible ) ) {
					unset( $items[ $key ] );
				}
			}

			// Hide only marked products
			$visible_products   = $this->get_bulk_visible_products( $current_user_roles );
			$invisible_products = $this->get_bulk_invisible_products( $current_user_roles );
			foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
				$visible_taxonomy[ $taxonomy ]   = alg_wc_pvbur_get_visible_product_terms( $taxonomy );
				$invisible_taxonomy[ $taxonomy ] = alg_wc_pvbur_get_invisible_product_terms( $taxonomy );
			}
			foreach ( $products_items as $key => $item ) {
				$product_id = $item->object_id;
				// Products
				if ( ! empty( $invisible_products ) && in_array( $product_id, $invisible_products ) ) {
					unset( $items[ $key ] );
					continue;
				}
				if ( ! empty( $visible_products ) && ! in_array( $product_id, $visible_products ) ) {
					unset( $items[ $key ] );
					continue;
				}
				// Product categories / tags
				$is_hidden = false;
				foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
					$product_taxonomy_terms = get_the_terms( $product_id, $taxonomy );
					if ( ! empty( $product_taxonomy_terms ) ) {
						foreach( $product_taxonomy_terms as $product_taxonomy_term ) {
							if ( ! empty( $invisible_taxonomy[ $taxonomy ] ) && in_array( $product_taxonomy_term->term_id, $invisible_taxonomy[ $taxonomy ] ) ) {
								unset( $items[ $key ] );
								$is_hidden = true;
								break;
							}
							if ( ! empty( $visible_taxonomy[ $taxonomy ] ) && ! in_array( $product_taxonomy_term->term_id, $visible_taxonomy[ $taxonomy ] ) ) {
								unset( $items[ $key ] );
								$is_hidden = true;
								break;
							}
						}
					}
					if ( $is_hidden ) {
						break;
					}
				}
			}

			return $items;
		}

		/**
		 * Redirects to a page different from 404, in case a product is considered invisible
		 *
		 * Note: If product category/tag is invisible, redirect to 404 if there isn't a different page to redirect
		 *
		 * @version 1.3.0
		 * @since   1.1.6
		 * @todo    [fix] for "Bulk Settings > Product Categories / Tags"
		 */
		public function redirect_if_product_is_invisible() {
			global $wp_query;

			if (
				$wp_query->get( 'post_type' ) != 'product' ||
				! $wp_query->is_404 ||
				'no' === get_option( 'alg_wc_pvbur_query', 'no' )
			) {
				return;
			}

			$product_id         = null;
			$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();

			$hide_all = false;
			if ( $wp_query->is_404 ) {
				if (
					is_array( $post_not_in = $wp_query->get( 'post__not_in' ) ) &&
					count( $post_not_in ) > 0
				) {
					$product_id = $post_not_in[0];
				}

				// Hide all if necessary
				foreach ( $current_user_roles as $role ) {
					$hide_all = get_option( "alg_wc_pvbur_bulk_hide_all_from_{$role}", 'no' );
					if ( $hide_all == 'yes' ) {
						break;
					}
				}

			} elseif ( $wp_query->is_single() ) {
				global $post;
				$product_id = $post->ID;
			}

			$page_to_redirect = (
					'yes' === get_option( 'alg_wc_pvbur_redirect_per_product', 'no' ) &&
					! empty( $product_id ) &&
					'' != ( $page_to_redirect_per_product = get_post_meta( $product_id, '_alg_wc_pvbur_redirect', true ) )
				) ? $page_to_redirect_per_product : get_option( 'alg_wc_pvbur_redirect', '' );
			$page_to_redirect = apply_filters( 'pvbur_invisible_product_redirect', $page_to_redirect, $product_id );
			if ( empty( $page_to_redirect ) ) {
				return;
			}

			if ( ! empty( $product_id ) || $hide_all == 'yes' ) {
				if (
					! alg_wc_pvbur_is_visible( $current_user_roles, $product_id ) ||
					$hide_all == 'yes'
				) {

					if ( ! empty( $page_to_redirect ) ) {
						wp_redirect( $page_to_redirect );
						die();
					} else {
						global $wp_query;
						$wp_query->set_404();
						status_header( 404 );
					}
				}
			}
		}

	}

endif;

return new Alg_WC_Pro_PVBUR_Core();
