<?php
/**
 * Product Visibility by User Role for WooCommerce - Core Class
 *
 * @version 1.1.5
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Core' ) ) :

class Alg_WC_PVBUR_Core {

	/**
	 * Constructor.
	 *
	 * @version 1.1.5
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
				if ( 'yes' === get_option( 'alg_wc_pvbur_query', 'no' ) ) {
					add_action( 'pre_get_posts',                  array( $this, 'product_by_user_role_pre_get_posts' ) );
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
		}
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
	 * @version 1.1.5
	 * @since   1.1.5
	 */
	function add_bulk_and_quick_edit_fields() {
		$all_roles_options = '';
		$all_roles_options .= '<option value="alg_no_change" selected>' . __( '— No change —', 'woocommerce' ) . '</option>';
		foreach ( alg_wc_pvbur_get_user_roles() as $role_id => $role_desc ) {
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
	 * is_visible.
	 *
	 * @version 1.1.5
	 * @since   1.1.0
	 */
	function is_visible( $current_user_roles, $product_id ) {
		// Per product
		$roles = get_post_meta( $product_id, '_' . 'alg_wc_pvbur_visible', true );
		if ( is_array( $roles ) && ! empty( $roles ) ) {
			$_intersect = array_intersect( $roles, $current_user_roles );
			if ( empty( $_intersect ) ) {
				return false;
			}
		}
		$roles = get_post_meta( $product_id, '_' . 'alg_wc_pvbur_invisible', true );
		if ( is_array( $roles ) && ! empty( $roles ) ) {
			$_intersect = array_intersect( $roles, $current_user_roles );
			if ( ! empty( $_intersect ) ) {
				return false;
			}
		}
		// Bulk
		if ( 'yes' === apply_filters( 'alg_wc_pvbur', 'no', 'bulk_settings' ) ) {
			foreach ( $current_user_roles as $user_role_id ) {
				$visible_products = get_option( 'alg_wc_pvbur_bulk_visible_products_' . $user_role_id, '' );
				if ( ! empty( $visible_products ) ) {
					if ( ! in_array( $product_id, $visible_products ) ) {
						return false;
					}
				}
				$invisible_products = get_option( 'alg_wc_pvbur_bulk_invisible_products_' . $user_role_id, '' );
				if ( ! empty( $invisible_products ) ) {
					if ( in_array( $product_id, $invisible_products ) ) {
						return false;
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
							return false;
						}
					}
					$invisible_terms = get_option( 'alg_wc_pvbur_bulk_invisible_' . $taxonomy . 's_' . $user_role_id, '' );
					if ( ! empty( $invisible_terms ) ) {
						$_intersect = array_intersect( $invisible_terms, $product_terms_ids );
						if ( ! empty( $_intersect ) ) {
							return false;
						}
					}
				}
			}
		}
		return true;
	}

	/**
	 * product_by_user_role_pre_get_posts.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 * @todo    (maybe) check `is_admin` and ajax
	 */
	function product_by_user_role_pre_get_posts( $query ) {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'pre_get_posts', array( $this, 'product_by_user_role_pre_get_posts' ) );
		$current_user_roles = $this->get_current_user_all_roles();
		// Calculate `post__not_in`
		$post__not_in = $query->get( 'post__not_in' );
		$args = $query->query;
		$args['fields'] = 'ids';
		$loop = new WP_Query( $args );
		foreach ( $loop->posts as $product_id ) {
			if ( ! $this->is_visible( $current_user_roles, $product_id ) ) {
				$post__not_in[] = $product_id;
			}
		}
		$query->set( 'post__not_in', $post__not_in );
		add_action( 'pre_get_posts', array( $this, 'product_by_user_role_pre_get_posts' ) );
	}

	/**
	 * product_by_user_role_purchasable.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function product_by_user_role_purchasable( $purchasable, $_product ) {
		$current_user_roles = $this->get_current_user_all_roles();
		return ( ! $this->is_visible( $current_user_roles, $this->get_product_id_or_variation_parent_id( $_product ) ) ? false : $purchasable );
	}

	/**
	 * product_by_user_role_visibility.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function product_by_user_role_visibility( $visible, $product_id ) {
		$current_user_roles = $this->get_current_user_all_roles();
		return ( ! $this->is_visible( $current_user_roles, $product_id ) ? false : $visible );
	}

	/**
	 * get_current_user_all_roles.
	 *
	 * @version 1.1.4
	 * @since   1.0.0
	 */
	function get_current_user_all_roles() {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			require_once( ABSPATH . 'wp-includes/pluggable.php' );
		}
		$current_user = wp_get_current_user();
		return ( ! empty( $current_user->roles ) ) ? $current_user->roles : array( 'guest' );
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
