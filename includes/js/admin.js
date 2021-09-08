/**
 * Product Visibility by User Role for WooCommerce - admin scripts
 *
 * @version 1.7.3
 * @since   1.7.2
 * @author  WP Wham
 */

(function( $ ){
	
	$( document ).ready( function(){
		
		function logicExplainer( $visibleEl, $invisibleEl, $messageEl, message ) {
			if ( $visibleEl && $visibleEl.val() && $visibleEl.val().length && $invisibleEl && $invisibleEl.val() && $invisibleEl.val().length ) {
				$messageEl.html( message ).show();
			} else if ( $visibleEl && $visibleEl.val() && $visibleEl.val().length ) {
				$invisibleEl.prop( 'disabled', true );
				$messageEl.hide();
			} else if ( $invisibleEl && $invisibleEl.val() && $invisibleEl.val().length ) {
				$visibleEl.prop( 'disabled', true );
				$messageEl.hide();
			} else {
				$visibleEl.prop( 'disabled', false );
				$invisibleEl.prop( 'disabled', false );
				$messageEl.hide();
			}
		}
		
		function logicExplainerBulkSettings( $wrapper, type ) {
			logicExplainer(
				$wrapper.find( '.alg_wc_pvbur_bulk_visible_' + type ),
				$wrapper.find( '.alg_wc_pvbur_bulk_invisible_' + type ),
				$wrapper.find( '.alg_wc_pvbur_bulk_' + type + '_messages' ),
				'<span style="color: red;">'
					+ wpwham_product_visibility_user_role_admin.i18n.logical_error
					+ '</span>'
					+ '<br />'
					+ wpwham_product_visibility_user_role_admin.i18n.see_documentation
			);
		}
		
		function logicExplainerMetabox() {
			logicExplainer(
				$( '#alg_wc_pvbur_visible' ),
				$( '#alg_wc_pvbur_invisible' ),
				$( '#wpwham-product-visibility-by-user-role-meta-box-messages' ),
				'<span style="color: red;">'
					+ wpwham_product_visibility_user_role_admin.i18n.logical_error
					+ '</span>'
					+ '<br /><br />'
					+ wpwham_product_visibility_user_role_admin.i18n.see_documentation
			);
		}
		
		// attach handlers for bulk settings
		$.each( [ 'products', 'product_cats', 'product_tags' ], function( typeIndex, type ) {
			$.each( [ 'visible', 'invisible' ], function( visibilityIndex, visibility ) {
				$( '.alg_wc_pvbur_bulk_' + visibility + '_' + type ).each( function(){
					var $hiddenInput = $( this );
					var $wrapper = $hiddenInput.closest( 'table' );
					
					$( $hiddenInput ).on( 'change', function(){
						logicExplainerBulkSettings( $wrapper, type );
					});
					logicExplainerBulkSettings( $wrapper, type );
					
					$( $hiddenInput ).parent().on( 'click', function(){
						if ( $hiddenInput.prop( 'disabled' ) ) {
							$wrapper.find( '.alg_wc_pvbur_bulk_' + type + '_messages' )
								.html( ( visibility === 'visible' ? 
									wpwham_product_visibility_user_role_admin.i18n['why_is_visible_' + type + '_disabled'] 
									: wpwham_product_visibility_user_role_admin.i18n['why_is_invisible_' + type + '_disabled'] )
									+ '<br />'
									+ wpwham_product_visibility_user_role_admin.i18n.see_documentation ).toggle();
						}
					});
				});
			});
		});
		
		// attach handlers for product page metabox
		var $metabox = $( '#alg-wc-product-visibility-by-user-role-meta-box' );
		if ( $metabox.length ) {
			$( '#alg_wc_pvbur_visible, #alg_wc_pvbur_invisible' ).on( 'change', function(){
				logicExplainerMetabox();
			});
			logicExplainerMetabox();
		
			$( '#alg_wc_pvbur_visible' ).parent().on( 'click', function(){
				if ( $( '#alg_wc_pvbur_visible' ).prop( 'disabled' ) ) {
					$( '#wpwham-product-visibility-by-user-role-meta-box-messages' )
						.html( wpwham_product_visibility_user_role_admin.i18n.why_is_visible_disabled
							+ '<br /><br />'
							+ wpwham_product_visibility_user_role_admin.i18n.see_documentation ).toggle();
				}
			});
			
			$( '#alg_wc_pvbur_invisible' ).parent().on( 'click', function(){
				if ( $( '#alg_wc_pvbur_invisible' ).prop( 'disabled' ) ) {
					$( '#wpwham-product-visibility-by-user-role-meta-box-messages' )
						.html( wpwham_product_visibility_user_role_admin.i18n.why_is_invisible_disabled
							+ '<br /><br />'
							+ wpwham_product_visibility_user_role_admin.i18n.see_documentation ).toggle();
				}
			});
		}
		
	});
	
})( jQuery );
