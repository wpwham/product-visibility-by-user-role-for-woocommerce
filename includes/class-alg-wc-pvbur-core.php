<?php
/**
 * Product Visibility by User Role for WooCommerce - Core Class
 *
 * @version 1.8.1
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 * @author  WP Wham
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Core' ) ) :

class Alg_WC_PVBUR_Core {
	
	public $is_wc_version_below_3 = null;
	
	/**
	 * Constructor.
	 *
	 * @version 1.7.3
	 * @since   1.0.0
	 */
	function __construct() {
		$this->is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
		if ( 'yes' === get_option( 'alg_wc_pvbur_enabled', 'yes' ) ) {
			// Core
			if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				if ( 'yes' === get_option( 'alg_wc_pvbur_visibility', 'yes' ) ) {
					add_filter( 'woocommerce_product_is_visible', array( $this, 'product_by_user_role_visibility' ),  PHP_INT_MAX, 2 );
				}
				if ( get_option( 'alg_wc_pvbur_purchasable', 'yes' ) === 'yes' ) {
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
					add_action( 'woocommerce_product_bulk_edit_end',   array( $this, 'add_bulk_and_quick_edit_fields' ),  PHP_INT_MAX );
				}
				if ( 'yes' === get_option( 'alg_wc_pvbur_add_to_quick_edit', 'no' ) ) {
					add_action( 'woocommerce_product_quick_edit_end',  array( $this, 'add_bulk_and_quick_edit_fields' ),  PHP_INT_MAX );
				}
				add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'save_bulk_and_quick_edit_fields' ), PHP_INT_MAX, 2 );
			}
			// Setups conditions where invisible products can be searched or prevented
			add_filter( 'alg_wc_pvbur_can_search', array( $this, 'setups_search_cases' ), 10, 2 );
			
			// Clear product ids caches
			add_action( 'save_post_product', 'wpw_pvbur_clear_cache' );
			
			// Modify query
			if ( get_option( 'alg_wc_pvbur_query', 'yes' ) === 'yes' ) {
				add_action( 'woocommerce_product_query', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );
				add_action( 'pre_get_posts',             array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );
				add_filter( 'get_terms',                 array( $this, 'get_terms_adjust_counts' ),               20, 1 );
				// add_filter( 'woocommerce_layered_nav_count', array( $this, 'adjust_layered_nav_counts' ), 20, 3 );
				add_filter( 'woocommerce_get_filtered_term_product_counts_query', array( $this, 'adjust_layered_nav_query' ), 20, 1 );
			}
		}
	}
	
	/**
	 * Filter WC layered nav widget's query to account for hidden products.
	 *
	 * Adjusts the count and hides the attribute completely if the count becomes 0.
	 * See also adjust_layered_nav_counts().
	 *
	 * @version 1.7.3
	 * @since   1.7.3
	 */
	public function adjust_layered_nav_query( $query ) {
		
		$current_user_roles      = alg_wc_pvbur_get_current_user_all_roles();
		$cached_post__not_in_key = 'awcpvbur_pni_' . md5( implode( '_', $current_user_roles ) );
		if ( ( $cached_post__not_in = get_transient( $cached_post__not_in_key ) ) !== false ) {
			$query['where'] .= " \nAND wp_posts.ID NOT IN ( " . implode( ',', $cached_post__not_in ) . ") ";
		}
		
		return $query;
	}
	
	/**
	 * Filter term counts for an attribute in Woo layered nav to account for hidden products.
	 * 
	 * Alternate solution to the layered nav widget issue -- this way would leave all attributes
	 * displayed, but possibly with a (0) count.  Not currently used, but available as an
	 * alternative to adjust_layered_nav_query() if we need it later.
	 * 
	 * @param  string $link_html The nav link html.
	 * @param  int $count  number of products with attribute
	 * @param  obj $term  Terms
	 * @return string   filtered $link_html
	 * 
	 * @version 1.7.3
	 * @since   1.7.3
	 */
	public function adjust_layered_nav_counts( $link_html, $count, $term ) {
		
		$current_user_roles                 = alg_wc_pvbur_get_current_user_all_roles();
		$cached_term_count_differential_key = 'awcpvbur_tcd_' . md5( implode( '_', $current_user_roles ) );
		$term_count_differentials           = get_transient( $cached_term_count_differential_key );
		
		if ( is_array( $term_count_differentials ) && !empty( $term_count_differentials ) ) {
			
			foreach( $term_count_differentials as  $term_count_differential ) {
				
				if ( is_array( $term_count_differential ) && !empty( $term_count_differential ) ) {
					
					foreach( $term_count_differential as  $term_id => $number ){
						
						if ( $term->term_id == $term_id ) {
							
							$count = (int)$count - (int)$number;
							return '<span class="count">(' . absint( $count ) . ')</span>';
							
						}
						
					}
					
				}
				
			}
			
		}
		return $link_html;
	}
	
	/**
	 * Get all WC product attributes taxonomies.
	 *
	 * @version 1.7.3
	 * @since   1.7.3
	 */
	public function get_all_product_attributes_taxonomies() {
		$attributes_taxonomies = array();
		if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
			$attributes = array_keys( wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_label', 'attribute_name' ) );
			$attributes_taxonomies = array_filter( array_map( 'wc_attribute_taxonomy_name', $attributes ));
		}
		return $attributes_taxonomies;
	}
	
	/**
	 * Adjust term counts to account for hidden products.
	 *
	 * @version 1.7.3
	 * @since   1.6.0
	 */
	function get_terms_adjust_counts( $terms ) {
		$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
		$cached_term_count_differential_key = 'awcpvbur_tcd_' . md5( implode( '_', $current_user_roles ) );
		$term_count_differentials = get_transient( $cached_term_count_differential_key );
		$taxonomies = array_merge(
			array( 'product_cat', 'product_tag' ),
			$this->get_all_product_attributes_taxonomies()
		);
		foreach ( $terms as $i => $term ) {
			if (
				is_a( $term, 'WP_Term' ) &&
				in_array( $term->taxonomy, $taxonomies ) &&
				isset( $term_count_differentials[ $term->taxonomy ][ $term->term_id ] )
			) {
				$terms[$i]->count = $terms[$i]->count - $term_count_differentials[ $term->taxonomy ][ $term->term_id ];
			}
		}
		return $terms;
	}
	
	/**
	 * Setups conditions where invisible products can be searched or prevented
	 *
	 * @version 1.8.1
	 * @since   1.2.1
	 *
	 * @param bool $can_search
	 * @param $query
	 *
	 * @return bool
	 */
	public function setups_search_cases( $can_search, $query ) {
		$force_search = $query->get( 'alg_wc_pvbur_search' );
		if (
			! empty( $force_search ) &&
			filter_var( $force_search, FILTER_VALIDATE_BOOLEAN ) === true
		) {
			return true;
		}
		
		// always filter search
		if ( $query->is_search() ) {
			return true;
		}
		
		// don't check for visible/invisible products in these situations:
		if ( 
			is_admin()
			|| ! (
				( isset( $query->query['post_type'] ) && $query->query['post_type'] === 'product' )
				|| ( isset( $query->query['post_type'] ) && is_array( $query->query['post_type'] ) && in_array( 'product', $query->query['post_type'] ) )
				|| ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] === 'product' )
				|| isset( $query->query['product_cat'] )
				|| isset( $query->query['product_tag'] )
			)
		) {
			return false;
		}
		
		return $can_search;
	}
	
	/**
	 * save_bulk_and_quick_edit_fields.
	 *
	 * @version 1.4.0
	 * @since   1.1.5
	 */
	function save_bulk_and_quick_edit_fields( $post_id, $post ) {

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
		} elseif ( is_array( $_REQUEST['alg_wc_pvbur_visible'] ) && ! in_array( 'alg_wc_pvbur_no_change', $_REQUEST['alg_wc_pvbur_visible'] ) ) {
			if ( in_array( 'alg_wc_pvbur_clear', $_REQUEST['alg_wc_pvbur_visible'] ) ) {
				update_post_meta( $post_id, '_' . 'alg_wc_pvbur_visible', array() );
			} else {
				update_post_meta( $post_id, '_' . 'alg_wc_pvbur_visible', $_REQUEST['alg_wc_pvbur_visible'] );
			}
		}
		if ( ! isset( $_REQUEST['alg_wc_pvbur_invisible'] ) ) {
			update_post_meta( $post_id, '_' . 'alg_wc_pvbur_invisible', array() );
		} elseif ( is_array( $_REQUEST['alg_wc_pvbur_invisible'] ) && ! in_array( 'alg_wc_pvbur_no_change', $_REQUEST['alg_wc_pvbur_invisible'] ) ) {
			if ( in_array( 'alg_wc_pvbur_clear', $_REQUEST['alg_wc_pvbur_invisible'] ) ) {
				update_post_meta( $post_id, '_' . 'alg_wc_pvbur_invisible', array() );
			} else {
				update_post_meta( $post_id, '_' . 'alg_wc_pvbur_invisible', $_REQUEST['alg_wc_pvbur_invisible'] );
			}
		}

		return $post_id;
	}

	/**
	 * add_bulk_and_quick_edit_fields.
	 *
	 * @version 1.4.0
	 * @since   1.1.5
	 */
	function add_bulk_and_quick_edit_fields() {
		$all_roles_options = '';
		$all_roles_options .= '<option value="alg_wc_pvbur_no_change" selected>' . __( '— No change —', 'woocommerce' )                                 . '</option>';
		$all_roles_options .= '<option value="alg_wc_pvbur_clear">'              . __( '— Clear —', 'product-visibility-by-user-role-for-woocommerce' ) . '</option>';
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
	 * @todo    [dev] (maybe) full role name (instead of ID)
	 * @todo    [dev] (maybe) display "bulk settings"
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
	 * @version 1.7.3
	 * @since   1.1.9
	 */
	function pre_get_posts_hide_invisible_products( $query ) {
		
		if ( false === filter_var( apply_filters( 'alg_wc_pvbur_can_search', true, $query ), FILTER_VALIDATE_BOOLEAN ) ) {
			return;
		}

		remove_action( 'woocommerce_product_query', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );
		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts_hide_invisible_products' ), PHP_INT_MAX );

		$all_product_ids    = alg_wc_pvbur_get_all_products_ids( true );
		$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
		$post__in           = $query->get( 'post__in' );
		$post__in           = empty( $post__in ) ? array() : $post__in;
		$post__not_in       = $query->get( 'post__not_in' );
		$post__not_in       = empty( $post__not_in ) ? array() : $post__not_in;
		
		if ( is_array( $all_product_ids ) && count( $all_product_ids ) > 0 ) {
			$cached_post__not_in_key            = 'awcpvbur_pni_' . md5( implode( '_', $current_user_roles ) );
			$cached_term_count_differential_key = 'awcpvbur_tcd_' . md5( implode( '_', $current_user_roles ) );
			if ( false === ( $cached_post__not_in = get_transient( $cached_post__not_in_key ) ) ) {
				$cached_post__not_in     = array();
				$term_count_differential = array();
				foreach ( $all_product_ids as $product_id ) {
					if ( ! alg_wc_pvbur_is_visible( $current_user_roles, $product_id ) ) {
						// exclude product id from queries
						$cached_post__not_in[] = $product_id;
						// figure out which categories/tags to adjust the count down
						$taxonomies = array_merge(
							array( 'product_cat', 'product_tag' ),
							$this->get_all_product_attributes_taxonomies()
						);
						foreach ( $taxonomies as $taxonomy ) {
							// Getting product terms
							$term_ids = wp_get_post_terms( $product_id, $taxonomy, array( 'fields' => 'ids' ) );
							if ( ! empty( $term_ids ) ) {
								foreach ( $term_ids as $term_id ) {
									if ( ! isset( $term_count_differential[ $taxonomy ][ $term_id ] ) ) {
										$term_count_differential[ $taxonomy ][ $term_id ] = 0;
									}
									$term_count_differential[ $taxonomy ][ $term_id ]++;
								}
							}
						}
					}
				}
				set_transient( $cached_post__not_in_key, $cached_post__not_in );
				set_transient( $cached_term_count_differential_key, $term_count_differential );
			}
			if ( is_array( $cached_post__not_in ) ) {
				$post__not_in = array_unique( array_merge( $post__not_in, $cached_post__not_in ) );
			}
		}
		
		// By default we prefer to use post__not_in.
		// However, if $post__in still has stuff in it, it means some other plugin must be tinkering
		// with the query.  So we need to consider it:
		if ( ! empty( $post__in ) ) {
			$post__in = array_diff( $post__in, $post__not_in );
			$post__in = apply_filters( 'alg_wc_pvbur_post__in', $post__in );
			// if every item of the current query is excluded, $post__in will be empty at this point
		}
		
		// Since we can't use post__in and post__not_in at the same time, if $post__in still has
		// stuff in it we'll have to switch to post__in ourselves at this point and hope for the best...
		if ( ! empty( $post__in ) ) {
			$query->set( 'post__in', $post__in );
			$query->set( 'post__not_in', array() );
		} else {
			$query->set( 'post__in', array() );
			$query->set( 'post__not_in', apply_filters( 'alg_wc_pvbur_post__not_in', $post__not_in ) );
		}
		
		do_action( 'alg_wc_pvbur_hide_products_query', $query, $post__not_in );

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

}

endif;

return new Alg_WC_PVBUR_Core();
