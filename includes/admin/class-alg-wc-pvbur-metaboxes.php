<?php
/**
 * Product Visibility by User Role for WooCommerce - Metaboxes
 *
 * @version 1.2.1
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_PVBUR_Metaboxes' ) ) :

class Alg_WC_PVBUR_Metaboxes {

	/**
	 * Constructor.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 */
	function __construct() {
		add_action( 'add_meta_boxes',    array( $this, 'add_pvbur_metabox' ) );
		add_action( 'save_post_product', array( $this, 'save_pvbur_meta_box' ), PHP_INT_MAX, 2 );
	}

	/**
	 * add_pvbur_metabox.
	 *
	 * @version 1.1.2
	 * @since   1.0.0
	 */
	function add_pvbur_metabox() {
		add_meta_box(
			'alg-wc-product-visibility-by-user-role-meta-box',
			__( 'Product visibility', 'product-visibility-by-user-role-for-woocommerce' ),
			array( $this, 'display_pvbur_metabox' ),
			'product',
			'side',
			'low'
		);
	}

	/**
	 * display_pvbur_metabox.
	 *
	 * @version 1.1.0
	 * @since   1.0.0
	 * @todo    (maybe) placeholder for textarea
	 */
	function display_pvbur_metabox() {
		$current_post_id = get_the_ID();
		$html = '';
		$html .= '<table class="widefat striped">';
		foreach ( $this->get_meta_box_options() as $option ) {
			$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
			if ( $is_enabled ) {
				if ( 'title' === $option['type'] ) {
					$html .= '<tr>';
					$html .= '<th colspan="3" style="text-align:left;font-weight:bold;">' . $option['title'] . '</th>';
					$html .= '</tr>';
				} else {
					$custom_attributes = '';
					$the_post_id   = ( isset( $option['product_id'] ) ) ? $option['product_id'] : $current_post_id;
					$the_meta_name = ( isset( $option['meta_name'] ) )  ? $option['meta_name']  : '_' . $option['name'];
					if ( get_post_meta( $the_post_id, $the_meta_name ) ) {
						$option_value = get_post_meta( $the_post_id, $the_meta_name, true );
					} else {
						$option_value = ( isset( $option['default'] ) ) ? $option['default'] : '';
					}
					$css = ( isset( $option['css'] ) ) ? $option['css']  : '';
					$input_ending = '';
					if ( 'select' === $option['type'] ) {
						if ( isset( $option['multiple'] ) ) {
							$custom_attributes = ' multiple';
							$option_name       = $option['name'] . '[]';
							$class             = 'chosen_select';
						} else {
							$option_name       = $option['name'];
							$class             = '';
						}
						if ( isset( $option['custom_attributes'] ) ) {
							$custom_attributes .= ' ' . $option['custom_attributes'];
						}
						$options = '';
						foreach ( $option['options'] as $select_option_key => $select_option_value ) {
							$selected = '';
							if ( is_array( $option_value ) ) {
								foreach ( $option_value as $single_option_value ) {
									if ( '' != ( $selected = selected( $single_option_value, $select_option_key, false ) ) ) {
										break;
									}
								}
							} else {
								$selected = selected( $option_value, $select_option_key, false );
							}
							$options .= '<option value="' . $select_option_key . '" ' . $selected . '>' . $select_option_value . '</option>';
						}
					} elseif ( 'textarea' === $option['type'] ) {
						if ( '' === $css ) {
							$css = 'min-width:300px;';
						}
					} else {
						$input_ending = ' id="' . $option['name'] . '" name="' . $option['name'] . '" value="' . $option_value . '">';
						if ( isset( $option['custom_attributes'] ) ) {
							$input_ending = ' ' . $option['custom_attributes'] . $input_ending;
						}
						if ( isset( $option['placeholder'] ) ) {
							$input_ending = ' placeholder="' . $option['placeholder'] . '"' . $input_ending;
						}
					}
					switch ( $option['type'] ) {
						case 'price':
							$field_html = '<input style="' . $css . '" class="short wc_input_price" type="number" step="0.0001"' . $input_ending;
							break;
						case 'date':
							$field_html = '<input style="' . $css . '" class="input-text" display="date" type="text"' . $input_ending;
							break;
						case 'textarea':
							$field_html = '<textarea style="' . $css . '" id="' . $option['name'] . '" name="' . $option['name'] . '">' .
								$option_value . '</textarea>';
							break;
						case 'select':
							$field_html = '<select' . $custom_attributes . ' style="' . $css . '" id="' . $option['name'] . '" name="' .
								$option_name . '"' . ' class="' . $class . '">' . $options . '</select>';
							break;
						default:
							$field_html = '<input style="' . $css . '" class="short" type="' . $option['type'] . '"' . $input_ending;
							break;
					}
					$html .= '<tr>';
					$maybe_tooltip = ( isset( $option['tooltip'] ) && '' != $option['tooltip'] ) ? wc_help_tip( $option['tooltip'], true ) : '';
					$html .= '<th style="text-align:left;width:25%;">' . $option['title'] . $maybe_tooltip . '</th>';
					if ( isset( $option['desc'] ) && '' != $option['desc'] ) {
						$html .= '<td style="font-style:italic;width:25%;">' . $option['desc'] . '</td>';
					}
					$html .= '<td>' . $field_html . '</td>';
					$html .= '</tr>';
				}
			}
		}
		$html .= '</table>';
		$html .= '<input type="hidden" name="alg_wc_pvbur_save_post" value="alg_wc_pvbur_save_post">';
		echo $html;
	}

	/**
	 * save_pvbur_meta_box.
	 *
	 * @version 1.2.1
	 * @since   1.0.0
	 */
	function save_pvbur_meta_box( $post_id, $post ) {
		// Check that we are saving with current metabox displayed.
		if ( ! isset( $_POST[ 'alg_wc_pvbur_save_post' ] ) ) {
			return;
		}
		// Save options
		foreach ( $this->get_meta_box_options() as $option ) {
			if ( 'title' === $option['type'] ) {
				continue;
			}
			$is_enabled = ( isset( $option['enabled'] ) && 'no' === $option['enabled'] ) ? false : true;
			if ( $is_enabled ) {
				$option_value  = ( isset( $_POST[ $option['name'] ] ) ? $_POST[ $option['name'] ] : $option['default'] );
				$_post_id      = ( isset( $option['product_id'] )     ? $option['product_id']     : $post_id );
				$_meta_name    = ( isset( $option['meta_name'] )      ? $option['meta_name']      : '_' . $option['name'] );
				update_post_meta( $_post_id, $_meta_name, $option_value );
			}
		}
		do_action( 'alg_wc_pvbur_save_metabox', $post_id );
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 1.1.1
	 * @since   1.0.0
	 * @todo    (maybe) variations
	 */
	function get_meta_box_options() {
		$options = array(
			array(
				'title'    => __( 'Visible', 'product-visibility-by-user-role-for-woocommerce' ),
				'tooltip'  => __( 'Select user roles that product is visible for. If no roles selected - product will be visible for all roles.', 'product-visibility-by-user-role-for-woocommerce' ),
				'name'     => 'alg_wc_pvbur_visible',
				'default'  => '',
				'type'     => 'select',
				'options'  => alg_wc_pvbur_get_user_roles(),
				'multiple' => true,
				'css'      => 'height:300px;width:100%;',
			),
			array(
				'title'    => __( 'Invisible', 'product-visibility-by-user-role-for-woocommerce' ),
				'tooltip'  => __( 'Select user roles that product is hidden for. If no roles selected - product will be visible for all roles.', 'product-visibility-by-user-role-for-woocommerce' ),
				'name'     => 'alg_wc_pvbur_invisible',
				'default'  => '',
				'type'     => 'select',
				'options'  => alg_wc_pvbur_get_user_roles(),
				'multiple' => true,
				'css'      => 'height:300px;width:100%;',
			),
		);
		return $options;
	}

}

endif;

return new Alg_WC_PVBUR_Metaboxes();
