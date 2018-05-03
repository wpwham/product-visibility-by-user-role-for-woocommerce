<?php
/**
 * Product Visibility by User Role for WooCommerce - Core Class
 *
 * @version 1.1.8
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Core' ) ) :

class Alg_WC_PVBUR_Core {

	/**
	 * Constructor.
	 *
	 * @version 1.1.7
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

			if ( is_admin() ) {
				add_action( 'updated_option', array( $this, 'sync_product_visibility_with_post_meta' ), 10, 3 );
				add_action( 'save_post_product', array( $this, 'sync_product_visibility_with_bulk_option' ) );
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
	 * Syncs product visibility from bulk option with post meta
	 *
	 * @version 1.1.7
	 * @since   1.1.7
	 */
	public function sync_product_visibility_with_post_meta( $option_name, $oldvalue, $newvalue ) {
		if ( strpos( $option_name, 'alg_wc_pvbur_bulk_' ) === false ) {
			return;
		}

		$visibility = strpos( $option_name, '_invisible_' ) !== false ? 'invisible' : 'visible';
		$find       = '_products_';
		$pos        = strrpos( $option_name, $find );
		$role       = $pos === false ? $option_name : substr( $option_name, $pos + strlen( $find ) );

		remove_action( 'updated_option', array( $this, 'sync_product_visibility_with_post_meta' ), 10, 3 );

		$products = $newvalue;
		foreach ( $products as $product_id ) {
			$post_meta_value = get_post_meta( $product_id, "_alg_wc_pvbur_{$visibility}", true );
			if ( empty( $post_meta_value ) ) {
				$post_meta_value = array();
			}
			array_push( $post_meta_value, $role );
			$post_meta_value = array_unique( $post_meta_value );
			update_post_meta( $product_id, "_alg_wc_pvbur_{$visibility}", $post_meta_value );
		}

		if (
			! is_array( $oldvalue ) ||
			! is_array( $newvalue )
		) {
			return;
		}

		if ( count( $oldvalue ) > count( $newvalue ) ) {
			$products = array_diff( $oldvalue, $newvalue );
			foreach ( $products as $product_id ) {
				$post_meta_value = get_post_meta( $product_id, "_alg_wc_pvbur_{$visibility}", true );
				if ( empty( $post_meta_value ) ) {
					$post_meta_value = array();
				}
				$index = array_search( $role, $post_meta_value );
				if ( $index !== false ) {
					unset( $post_meta_value[ $index ] );
					update_post_meta( $product_id, "_alg_wc_pvbur_{$visibility}", $post_meta_value );
				}
			}
		}
	}

	/**
	 * Syncs product visibility from post meta with bulk option on product save
	 *
	 * @version 1.1.7
	 * @since   1.1.7
	 */
	function sync_product_with_bulk_option_by_visibility_type( $visibility_type = 'invisible', $product_id ) {
		$roles = array();

		if ( isset( $_REQUEST["alg_wc_pvbur_{$visibility_type}"] ) ) {
			$roles = $_REQUEST["alg_wc_pvbur_{$visibility_type}"];
			foreach ( $roles as $role ) {
				$option = get_option( "alg_wc_pvbur_bulk_{$visibility_type}_products_{$role}", array() );
				array_push( $option, $product_id );
				$option = array_unique( $option );
				update_option( "alg_wc_pvbur_bulk_{$visibility_type}_products_{$role}", $option );
			}
		}

		$all_roles = array_keys( alg_wc_pvbur_get_user_roles() );
		foreach ( array_diff( $all_roles, $roles ) as $role ) {
			$option = get_option( "alg_wc_pvbur_bulk_{$visibility_type}_products_{$role}", array() );
			$index  = array_search( $product_id, $option );
			if ( $index !== false ) {
				unset( $option[ $index ] );
				update_option( "alg_wc_pvbur_bulk_{$visibility_type}_products_{$role}", $option );
			}
		}
	}

	/**
	 * Syncs product visibility with bulk option on product save
	 *
	 * @version 1.1.7
	 * @since   1.1.7
	 */
	function sync_product_visibility_with_bulk_option( $product_id ) {
		$this->sync_product_with_bulk_option_by_visibility_type( 'invisible', $product_id );
		$this->sync_product_with_bulk_option_by_visibility_type( 'visible', $product_id );
	}

	/**
	 * product_by_user_role_visibility.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 */
	function product_by_user_role_visibility( $visible, $product_id ) {
		$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
		return ( ! alg_wc_pvbur_product_is_visible( $current_user_roles, $product_id ) ? false : $visible );
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
	 * product_by_user_role_pre_get_posts.
	 *
	 * @version 1.1.8
	 * @since   1.0.0
	 * @todo    (maybe) check `is_admin` and ajax
     * @todo    Improve performance by using some transient maybe
	 */
	function product_by_user_role_pre_get_posts( $query ) {
		if ( is_admin() ) {
			return;
		}
		remove_action( 'pre_get_posts', array( $this, 'product_by_user_role_pre_get_posts' ) );

		$user_roles         = array_keys( alg_wc_pvbur_get_user_roles() );
		$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();

		$post__not_in = $query->get( 'post__not_in' );
		$product_ids  = array();
		foreach ( $user_roles as $role ) {
			$option_inv  = get_option( "alg_wc_pvbur_bulk_invisible_products_{$role}", array() );
			$option_vis  = get_option( "alg_wc_pvbur_bulk_visible_products_{$role}", array() );
			$product_ids = array_merge( $option_inv, $option_vis, $product_ids );
		}
		$product_ids = array_unique( $product_ids );
		foreach ( $product_ids as $product_id ) {
			if ( ! alg_wc_pvbur_product_is_visible( $current_user_roles, $product_id ) ) {
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
		$current_user_roles = alg_wc_pvbur_get_current_user_all_roles();
		return ( ! alg_wc_pvbur_product_is_visible( $current_user_roles, $this->get_product_id_or_variation_parent_id( $_product ) ) ? false : $purchasable );
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
