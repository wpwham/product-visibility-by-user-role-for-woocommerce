<?php
/**
 * Product Visibility by User Role for WooCommerce - Bulk Section Settings
 *
 * @version 1.1.2
 * @since   1.1.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Settings_Bulk' ) ) :

class Alg_WC_PVBUR_Settings_Bulk extends Alg_WC_PVBUR_Settings_Section {

	/**
	 * Constructor.
	 *
	 * @version 1.1.2
	 * @since   1.1.0
	 */
	function __construct() {
		$this->id   = 'bulk';
		$this->desc = __( 'Bulk Settings', 'product-visibility-by-user-role-for-woocommerce' );
		parent::__construct();
		add_action( 'woocommerce_admin_field_' . 'alg_wc_pvbur_title_and_save_button', array( $this, 'output_alg_wc_pvbur_title_and_save_button' ) );
		add_action( 'alg_wc_pvbur_output_sections_' . 'bulk',                          array( $this, 'output_subsections' ) );
	}

	/**
	 * get_current_subsection.
	 *
	 * @version 1.1.2
	 * @since   1.1.2
	 */
	function get_current_subsection() {
		return ( isset( $_GET['subsection'] ) ? $_GET['subsection'] : 'guest' );
	}

	/**
	 * output_subsections.
	 *
	 * @version 1.1.2
	 * @since   1.1.2
	 */
	function output_subsections() {
		$current_subsection = $this->get_current_subsection();
		$subsections = array_merge( alg_wc_pvbur_get_user_roles(), array( 'alg_wc_pvbur_all_roles' => __( 'All Roles', 'product-visibility-by-user-role-for-woocommerce' ) ) );
		echo '<p>';
		echo '<ul class="subsubsub">';
		$array_keys = array_keys( $subsections );
		foreach ( $subsections as $id => $label ) {
			$id = (string) $id;
			echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . 'alg_wc_pvbur' . '&section=' . 'bulk' . '&subsection=' . sanitize_title( $id ) ) .
				'" class="' . ( $current_subsection === $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) === $id ? '' : '|' ) . ' </li>';
		}
		echo '</ul><br class="clear" />';
		echo '</p>';
	}

	/**
	 * output_alg_wc_pvbur_title_and_save_button.
	 *
	 * @version 1.1.1
	 * @since   1.1.1
	 */
	function output_alg_wc_pvbur_title_and_save_button( $value ) {
		$save_button = '<input name="save" class="button-primary woocommerce-save-button" type="submit" value="' .
			__( 'Save all changes', 'product-visibility-by-user-role-for-woocommerce' ) . '">';
		if ( ! empty( $value['title'] ) ) {
			echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
		}
		if ( ! empty( $value['desc'] ) ) {
			echo wpautop( wptexturize( wp_kses_post( $value['desc'] ) ) );
		}
		echo $save_button;
		echo '<table class="form-table">' . "\n\n";
		if ( ! empty( $value['id'] ) ) {
			do_action( 'woocommerce_settings_' . sanitize_title( $value['id'] ) );
		}
	}

	/**
	 * add_settings.
	 *
	 * @version 1.1.2
	 * @since   1.1.0
	 */
	function add_settings( $settings ) {
		$bulk_settings = array(
			array(
				'title'    => __( 'Bulk Settings', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc'     => __( 'This section allows you to set which products, product categories or tags are visible or invisible to certain role.', 'product-visibility-by-user-role-for-woocommerce' ) . ' ' .
					__( 'If you fill in "Visible" option, then users with selected role will be able to see only chosen products.', 'product-visibility-by-user-role-for-woocommerce' ) . ' ' .
					__( 'If you fill in "Invisible" option, then chosen products will be hidden for users with that role.', 'product-visibility-by-user-role-for-woocommerce' ) . ' ' .
					__( 'If you leave any of the options empty - option will be ignored (i.e. all products will be visible).', 'product-visibility-by-user-role-for-woocommerce' ),
				'type'     => 'title',
				'id'       => 'alg_wc_pvbur_bulk_options',
			),
			array(
				'title'    => __( 'Enable/Disable', 'product-visibility-by-user-role-for-woocommerce' ),
				'desc_tip' => apply_filters( 'alg_wc_pvbur', sprintf( __( 'You will need %s plugin to enable this section.', 'product-visibility-by-user-role-for-woocommerce' ),
					'<a href="https://wpcodefactory.com/item/product-visibility-by-user-role-for-woocommerce/" target="_blank">' .
						__( 'Product Visibility by User Role for WooCommerce Pro', 'product-visibility-by-user-role-for-woocommerce' ) .
					'</a>' ), 'settings'
				),
				'desc'     => '<strong>' . __( 'Enable "Bulk Settings" section', 'product-visibility-by-user-role-for-woocommerce' ) . '</strong>',
				'id'       => 'alg_wc_pvbur_bulk_options_section_enabled',
				'default'  => 'no',
				'type'     => 'checkbox',
				'custom_attributes' => apply_filters( 'alg_wc_pvbur', array( 'disabled' => 'disabled' ), 'settings' ),
			),
			array(
				'type'     => 'sectionend',
				'id'       => 'alg_wc_pvbur_bulk_options',
			),
		);
		$user_roles = alg_wc_pvbur_get_user_roles();
		$title_type = 'alg_wc_pvbur_title_and_save_button';
		if ( 'alg_wc_pvbur_all_roles' != ( $current_subsection = $this->get_current_subsection() ) ) {
			$user_roles = array( $current_subsection => $user_roles[ $current_subsection ] );
			$title_type = 'title';
		}
		foreach ( $user_roles as $user_role_id => $user_role_title ) {
			$bulk_settings = array_merge( $bulk_settings, array(
				array(
					'title'    => $user_role_title,
					'type'     => $title_type,
					'id'       => 'alg_wc_pvbur_bulk_options_' . $user_role_id,
				),
				array(
					'title'    => __( 'Products', 'product-visibility-by-user-role-for-woocommerce' ),
					'desc'     => __( 'Visible', 'product-visibility-by-user-role-for-woocommerce' ),
					'id'       => 'alg_wc_pvbur_bulk_visible_products_' . $user_role_id,
					'default'  => '',
					'type'     => 'multiselect',
					'class'    => 'chosen_select',
					'options'  => $this->get_products(),
					'custom_attributes' => $this->get_custom_attributes()
				),
				array(
					'desc'     => __( 'Invisible', 'product-visibility-by-user-role-for-woocommerce' ),
					'id'       => 'alg_wc_pvbur_bulk_invisible_products_' . $user_role_id,
					'default'  => '',
					'type'     => 'multiselect',
					'class'    => 'chosen_select',
					'options'  => $this->get_products(),
					'custom_attributes' => $this->get_custom_attributes()
				),
				array(
					'title'    => __( 'Product Categories', 'product-visibility-by-user-role-for-woocommerce' ),
					'desc'     => __( 'Visible', 'product-visibility-by-user-role-for-woocommerce' ),
					'id'       => 'alg_wc_pvbur_bulk_visible_product_cats_' . $user_role_id,
					'default'  => '',
					'type'     => 'multiselect',
					'class'    => 'chosen_select',
					'options'  => $this->get_terms( 'product_cat' ),
					'custom_attributes' => $this->get_custom_attributes()
				),
				array(
					'desc'     => __( 'Invisible', 'product-visibility-by-user-role-for-woocommerce' ),
					'id'       => 'alg_wc_pvbur_bulk_invisible_product_cats_' . $user_role_id,
					'default'  => '',
					'type'     => 'multiselect',
					'class'    => 'chosen_select',
					'options'  => $this->get_terms( 'product_cat' ),
					'custom_attributes' => $this->get_custom_attributes()
				),
				array(
					'title'    => __( 'Product Tags', 'product-visibility-by-user-role-for-woocommerce' ),
					'desc'     => __( 'Visible', 'product-visibility-by-user-role-for-woocommerce' ),
					'id'       => 'alg_wc_pvbur_bulk_visible_product_tags_' . $user_role_id,
					'default'  => '',
					'type'     => 'multiselect',
					'class'    => 'chosen_select',
					'options'  => $this->get_terms( 'product_tag' ),
					'custom_attributes' => $this->get_custom_attributes()
				),
				array(
					'desc'     => __( 'Invisible', 'product-visibility-by-user-role-for-woocommerce' ),
					'id'       => 'alg_wc_pvbur_bulk_invisible_product_tags_' . $user_role_id,
					'default'  => '',
					'type'     => 'multiselect',
					'class'    => 'chosen_select',
					'options'  => $this->get_terms( 'product_tag' ),
					'custom_attributes' => $this->get_custom_attributes()
				),
				array(
					'type'     => 'sectionend',
					'id'       => 'alg_wc_pvbur_bulk_options_' . $user_role_id,
				),
			) );
		}
		return array_merge( $bulk_settings, $settings );
	}

	private function get_custom_attributes() {
		if ( 'no' === apply_filters( 'alg_wc_pvbur', 'no', 'premium_version' ) ) {
			return array( 'disabled' => 'disabled' );
		}

		return array();
	}

	/**
	 * get_products.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 * @todo    (maybe) use `wc_get_products()`
	 */
	function get_products( $products = array(), $post_status = 'any', $block_size = 256, $add_variations = false ) {
		$offset = 0;
		while( true ) {
			$args = array(
				'post_type'      => 'product',
				'post_status'    => $post_status,
				'posts_per_page' => $block_size,
				'offset'         => $offset,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$loop = new WP_Query( $args );
			if ( ! $loop->have_posts() ) {
				break;
			}
			foreach ( $loop->posts as $post_id ) {
				$products[ $post_id ] = get_the_title( $post_id );
				if ( $add_variations ) {
					$_product = wc_get_product( $post_id );
					if ( $_product->is_type( 'variable' ) ) {
						foreach ( $_product->get_children() as $child_id ) {
							$products[ $child_id ] = get_the_title( $child_id );
						}
					}
				}
			}
			$offset += $block_size;
		}
		return $products;
	}

	/**
	 * get_terms.
	 *
	 * @version 1.1.0
	 * @since   1.1.0
	 */
	function get_terms( $args ) {
		if ( ! is_array( $args ) ) {
			$_taxonomy = $args;
			$args = array(
				'taxonomy'   => $_taxonomy,
				'orderby'    => 'name',
				'hide_empty' => false,
			);
		}
		global $wp_version;
		if ( version_compare( $wp_version, '4.5.0', '>=' ) ) {
			$_terms = get_terms( $args );
		} else {
			$_taxonomy = $args['taxonomy'];
			unset( $args['taxonomy'] );
			$_terms = get_terms( $_taxonomy, $args );
		}
		$_terms_options = array();
		if ( ! empty( $_terms ) && ! is_wp_error( $_terms ) ){
			foreach ( $_terms as $_term ) {
				$_terms_options[ $_term->term_id ] = $_term->name;
			}
		}
		return $_terms_options;
	}

}

endif;

return new Alg_WC_PVBUR_Settings_Bulk();
