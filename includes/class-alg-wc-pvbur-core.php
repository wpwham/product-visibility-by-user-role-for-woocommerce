<?php
/**
 * Product Visibility by User Role for WooCommerce - Core Class
 *
 * @version 1.4.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Core' ) ) :

class Alg_WC_PVBUR_Core {

	/**
	 * Constructor.
	 *
	 * @version 1.4.0
	 * @since   1.0.0
	 */
	function __construct() {
		$this->is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		if ( 'yes' === get_option( 'alg_wc_pvbur_enabled', 'yes' ) ) {
			// Core
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( 'yes' === get_option( 'alg_wc_pvbur_visibility', 'yes' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'product_by_user_role_visibility' ), PHP_INT_MAX, 2 );
				}
				if ( 'yes' === get_option( 'alg_wc_pvbur_purchasable', 'no' ) ) {
					add_filter( 'woocommerce_is_purchasable',     array( $this, 'product_by_user_role_purchasable' ), PHP_INT_MAX, 2 );
				}
			}
			// Admin products list
			if ( 'yes' === get_option( 'alg_wc_pvbur_add_column_visible_user_roles', 'no' ) ) {
				add_filter( 'manage_edit-product_columns',        array( $this, 'add_product_columns' ),   PHP_INT_MAX );
				add_action( 'manage_product_posts_custom_column', array( $this, 'render_product_column' ), PHP_INT_MAX );
			}
			// Quick and bulk edit
			if ( 'yes' === apply_filters( 'alg_wc_pvbur', 'no', 'products_bulk_edit' ) || 'yes' === get_option( 'alg_wc_pvbur_add_to_quick_edit', 'no' ) ) {
				if ( 'yes' === apply_filters( 'alg_wc_pvbur', 'no', 'products_bulk_edit' ) ) {
					add_action( 'woocommerce_product_bulk_edit_end',  array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
				}
				if ( 'yes' === get_option( 'alg_wc_pvbur_add_to_quick_edit', 'no' ) ) {
					add_action( 'woocommerce_product_quick_edit_end', array( $this, 'add_bulk_and_quick_edit_fields' ), PHP_INT_MAX );
				}
				add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'save_bulk_and_quick_edit_fields' ), PHP_INT_MAX, 2 );
			}

			// Setups conditions where invisible products can be searched or prevented
			add_filter( 'alg_wc_pvbur_can_search', array( $this, 'setups_search_cases' ), 10, 2 );

			// Clears invisible products ids cache
			add_action( 'alg_wc_pvbur_save_metabox', array( $this, 'clear_invisible_product_ids_cache' ) );

			if ( 'yes' === get_option( 'alg_wc_pvbur_query', 'no' ) ) {
				add_action( 'woocommerce_product_query', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );
				add_action( 'pre_get_posts', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );
			}
// TODO - PRO
			// Redirect meta box
			if ( 'yes' === get_option( 'alg_wc_pvbur_redirect_per_product', 'no' ) ) {
				add_action( 'add_meta_boxes',    array( $this, 'add_pvbur_redirect_metabox' ) );
				add_action( 'save_post_product', array( $this, 'save_pvbur_redirect_meta_box' ), PHP_INT_MAX, 2 );
			}

			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				add_action( 'template_redirect', array( $this, 'redirect_if_product_is_invisible' ) );
				add_filter( 'wp_get_nav_menu_items', array( $this, 'hide_empty_wp_nav_menu_items' ), 10, 3 );
				add_filter( 'get_terms', array( $this, 'hide_product_terms' ), 10, 4 );
			}

		}
	}

	/**
	 * Clears invisible products ids cache on metabox saving
	 *
	 * @version 1.2.1
	 * @since   1.2.1
	 * @param $post_id
	 */
	public function clear_invisible_product_ids_cache( $post_id ) {
		global $wpdb;
		$transient_like = '%_transient_awcpvbur_inv_pids_%';
		$sql            = $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name like %s", $transient_like );
		$results        = $wpdb->query( $sql );
	}

	/**
	 * Setups conditions where invisible products can be searched or prevented
	 *
	 * @version 1.2.2
	 * @since   1.2.1
	 *
	 * @param bool $can_search
	 * @param $query
	 *
	 * @return bool
	 */
	public function setups_search_cases( $can_search = true, $query ) {
		$force_search = $query->get( 'alg_wc_pvbur_search' );
		if (
			! empty( $force_search ) &&
			filter_var( $force_search, FILTER_VALIDATE_BOOLEAN ) === true
		) {
			return true;
		}

		if (
			is_admin() ||
			( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
			( current_filter() == 'pre_get_posts' && ! $query->is_single() && ! $query->is_search() ) ||
			! is_main_query() ||
			empty( $query->query ) ||
			( isset( $query->query['post_type'] ) && $query->query['post_type'] == 'nav_menu_item' )
		) {
			return false;
		}

		return $can_search;
	}

	/**
	 * save_bulk_and_quick_edit_fields.
	 *
	 * @version 1.1.5
	 * @since   1.1.5
	 */
	public function save_bulk_and_quick_edit_fields( $post_id, $post ) {

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'product' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Check nonce.
		if ( ! isset( $_REQUEST['woocommerce_quick_edit_nonce'] ) || ! wp_verify_nonce( $_REQUEST['woocommerce_quick_edit_nonce'], 'woocommerce_quick_edit_nonce' ) ) { // WPCS: input var ok, sanitization ok.
			return $post_id;
		}

		// Check bulk or quick edit.
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) ) { // WPCS: input var ok.
			if ( 'no' === get_option( 'alg_wc_pvbur_add_to_quick_edit', 'no' ) ) {
				return $post_id;
			}
		} else {
			if ( 'no' === apply_filters( 'alg_wc_pvbur', 'no', 'products_bulk_edit' ) ) {
				return $post_id;
			}
		}

		// Save.
		if ( ! isset( $_REQUEST['alg_wc_pvbur_visible'] ) ) {
			update_post_meta( $post_id, '_' . 'alg_wc_pvbur_visible', array() );
		} elseif ( is_array( $_REQUEST['alg_wc_pvbur_visible'] ) && ! in_array( 'alg_no_change', $_REQUEST['alg_wc_pvbur_visible'] ) ) {
			update_post_meta( $post_id, '_' . 'alg_wc_pvbur_visible', $_REQUEST['alg_wc_pvbur_visible'] );
		}
		if ( ! isset( $_REQUEST['alg_wc_pvbur_invisible'] ) ) {
			update_post_meta( $post_id, '_' . 'alg_wc_pvbur_invisible', array() );
		} elseif ( is_array( $_REQUEST['alg_wc_pvbur_invisible'] ) && ! in_array( 'alg_no_change', $_REQUEST['alg_wc_pvbur_invisible'] ) ) {
			update_post_meta( $post_id, '_' . 'alg_wc_pvbur_invisible', $_REQUEST['alg_wc_pvbur_invisible'] );
		}

		return $post_id;
	}

	/**
	 * add_bulk_and_quick_edit_fields.
	 *
	 * @version 1.2.5
	 * @since   1.1.5
	 */
	function add_bulk_and_quick_edit_fields() {
		$all_roles_options = '';
		$all_roles_options .= '<option value="alg_no_change" selected>' . __( '— No change —', 'woocommerce' ) . '</option>';
		foreach ( alg_wc_pvbur_get_user_roles_for_settings() as $role_id => $role_desc ) {
			$all_roles_options .= '<option value="' . $role_id . '">' . $role_desc . '</option>';
		}
		?><br class="clear" />
		<label>
			<span class="title"><?php esc_html_e( 'User roles: Visible', 'product-visibility-by-user-role-for-woocommerce' ); ?></span>
			<select multiple id="alg_wc_pvbur_visible" name="alg_wc_pvbur_visible[]">
				<?php echo $all_roles_options; ?>
			</select>
		</label>
		<label>
			<span class="title"><?php esc_html_e( 'User roles: Invisible', 'product-visibility-by-user-role-for-woocommerce' ); ?></span>
			<select multiple id="alg_wc_pvbur_invisible" name="alg_wc_pvbur_invisible[]">
				<?php echo $all_roles_options; ?>
			</select>
		</label><?php
	}

	/**
	 * add_product_columns.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function add_product_columns( $columns ) {
		$columns[ 'alg_wc_pvbur_user_roles' ] = __( 'User Roles', 'product-visibility-by-user-role-for-woocommerce' );
		return $columns;
	}

	/**
	 * render_product_column.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 * @todo    (maybe) full role name (instead of ID)
	 * @todo    (maybe) display "bulk settings"
	 */
	function render_product_column( $column ) {
		if ( 'alg_wc_pvbur_user_roles' === $column ) {
			$html       = '';
			$product_id = get_the_ID();
			if ( $roles = get_post_meta( $product_id, '_' . 'alg_wc_pvbur_visible', true ) ) {
				if ( is_array( $roles ) && ! empty( $roles ) ) {
					$html .= '<span style="color:green;">' . implode( ', ', $roles ) . '</span>';
				}
			}
			if ( $roles = get_post_meta( $product_id, '_' . 'alg_wc_pvbur_invisible', true ) ) {
				if ( is_array( $roles ) && ! empty( $roles ) ) {
					if ( ! empty ( $html ) ) {
						$html .= '<br>';
					}
					$html .= '<span style="color:red;">' . implode( ', ', $roles ) . '</span>';
				}
			}
			echo $html;
		}
	}

	/**
	 * pre_get_posts_hide_invisible_products.
	 *
	 * @version 1.2.1
	 * @since   1.1.9
	 */
	function pre_get_posts_hide_invisible_products( $query ) {
		if ( false === filter_var( apply_filters( 'alg_wc_pvbur_can_search', true, $query ), FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		remove_action( 'woocommerce_product_query', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );
		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );

		$post__not_in          = $query->get( 'post__not_in' );
		$post__not_in          = empty( $post__not_in ) ? array() : $post__not_in;
		$current_user_roles    = alg_wc_pvbur_get_current_user_all_roles();
		$invisible_product_ids = alg_wc_pvbur_get_invisible_products_ids( $current_user_roles,true );

		if ( is_array( $invisible_product_ids ) && count( $invisible_product_ids ) > 0 ) {
			foreach ( $invisible_product_ids as $invisible_product_id ) {
				$filter = apply_filters( 'alg_wc_pvbur_is_visible', false, $current_user_roles, $invisible_product_id );
				if ( ! filter_var( $filter, FILTER_VALIDATE_BOOLEAN ) ) {
					$post__not_in[] = $invisible_product_id;
				}
			}
		}

		$post__not_in = array_unique( $post__not_in );
		$query->set( 'post__not_in', apply_filters( 'alg_wc_pvbur_post__not_in', $post__not_in, $invisible_product_ids  ) );
		do_action( 'alg_wc_pvbur_hide_products_query', $query, $invisible_product_ids );

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );
		add_action( 'woocommerce_product_query', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );
	}

	/**
	 * product_by_user_role_purchasable.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function product_by_user_role_purchasable( $purchasable, $_product ) {
		$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
		return ( ! alg_wc_pvbur_is_visible( $current_user_roles, $this->get_product_id_or_variation_parent_id( $_product ) ) ? false : $purchasable );
	}

	/**
	 * product_by_user_role_visibility.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function product_by_user_role_visibility( $visible, $product_id ) {
		$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
		return ( ! alg_wc_pvbur_is_visible( $current_user_roles, $product_id ) ? false : $visible );
	}

	/**
	 * get_product_id_or_variation_parent_id.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function get_product_id_or_variation_parent_id( $_product ) {
		if ( ! $_product || ! is_object( $_product ) ) {
			return 0;
		}
		if ( $this->is_wc_version_below_3 ) {
			return $_product->id;
		} else {
			return ( $_product->is_type( 'variation' ) ) ? $_product->get_parent_id() : $_product->get_id();
		}
	}

// TODO - PRO

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

}

endif;

return new Alg_WC_PVBUR_Core();
