=== Product Visibility by User Role for WooCommerce ===
Contributors: wpwham
Tags: woo commerce, woocommerce, product, visibility, user role
Requires at least: 4.4
Tested up to: 5.1
Stable tag: 1.4.1
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Display WooCommerce products by customer's user role.

== Description ==

**Product Visibility by User Role for WooCommerce** plugin lets you show/hide WooCommerce products depending on customer's user role.

You can choose how products should be hidden:

* Hide products in shop and search results.
* Make products non-purchasable.
* Hide products completely.

In free version you can set included or excluded user roles for each product individually. If you want to set user roles visibility options in bulk (for multiple products at once, product categories or product tags), please check [Product Visibility by User Role for WooCommerce Pro](https://wpfactory.com/item/product-visibility-by-user-role-for-woocommerce/) plugin. Pro version also has options to hide menu items, hide product terms, set custom redirect page (i.e. instead of default 404).

= Feedback =
* We are open to your suggestions and feedback. Thank you for using or trying out one of our plugins!

== Installation ==

1. Upload the entire plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Start by visiting plugin settings at "WooCommerce > Settings > Product Visibility".

== Changelog ==

= 1.4.1 - 19/03/2019 =
* Dev - Plugin author data updated.

= 1.4.0 - 10/02/2019 =
* Dev - General Options - "Replace description" options added.
* Dev - Admin Options - Product quick/bulk edit - "Clear" option added.
* Dev - Bulk Settings - Products - Language codes added to the list.
* Dev - Major code refactoring.
* Dev - Admin settings restyled.

= 1.3.0 - 29/01/2019 =
* Dev - Admin Options - "User roles to display in settings" option added.
* Dev - Admin settings descriptions updated and restyled.
* Fix singular view if category option is in use on bulk settings.
* Fix duplicate tax_query parameters.
* Improve nested tax_query parameters by making it simpler.
* "Redirect URL per product" option added.
* Bulk Settings - "array_merge(): Argument #1 is not an array" PHP warning fixed.
* "Hide menu items" now also hides products items from menu (i.e. not only categories and tags).
* Admin settings restyled.
* Add WPML compatibility to bulk section.
* Code refactored (admin settings).
* Fix products terms hiding regarding multiple roles.
* Fix redirect option forcing invisible products to be 404.
* Make the plugin compatible with WPML plugin.

= 1.2.4 - 25/12/2018 =
* Dev - Bulk Settings - Products - Product ID added to listing.
* Dev - Bulk Settings - Products - WPML - Listing all languages products now.
* Dev - Bulk Settings - Products - Code refactoring - Variations listing optimized.
* Dev - Bulk Settings - Categories / Tags - WPML - Listing all languages terms now.
* Dev - Bulk Settings - Categories / Tags - Term ID and term parent info added.
* Dev - Plugin URI updated.
* Improve 'Bulk Settings' code combined with 'Modify Query' option.

= 1.2.3 - 06/12/2018 =
* Add compatibility with WPML plugin.

= 1.2.2 - 13/08/2018 =
* Improve 'alg_wc_pvbur_can_search' filter preventing the main visibility query to work on wp menu.
* Fix category visibility replacing 'key' by 'taxonomy' on tax_query parameters.

= 1.2.1 - 25/07/2018 =
* Allow other plugins to interact with invisible products query using the 'alg_wc_pvbur_can_search' filter or passing a 'alg_wc_pvbur_search' parameter on customs WP_Query queries.
* Add WooCommerce minimum requirement.
* Improve performance of invisible products search by saving the results of alg_wc_pvbur_get_invisible_products() in cache (Only the ids, not WP_Query).
* Add action 'alg_wc_pvbur_save_metabox' on metabox saving.
* Add new function 'alg_wc_pvbur_get_invisible_products_ids()' to get only the invisible products ids, with a cache option.

= 1.2.0 - 29/06/2018 =
* Improve 'alg_wc_pvbur_is_visible()' function. Get the terms isn't working properly. Replace by wp_get_post_terms().
* Add is_search() check to products hiding function to make sure it works on search results too.
* Add new option to filter product terms hiding product categories and tags using the get_terms() function.
* Improve invisible product detection on single product pages.
* Add new option to hide all products, product categories/tags from user roles.

= 1.1.9 - 24/05/2018 =
* Create "alg_wc_pvbur_get_invisible_products" function.
* Change the way Modify query option works.
* Add 'alg_wc_pvbur_post__not_in' filter.
* Add 'alg_wc_pvbur_hide_products_query' action.
* Update WooCommerce tested up to.

= 1.1.8 - 03/05/2018 =
* Remove pro version checking.
* Fix visible products logic.
* Fix 'alg_wc_pvbur_get_user_roles()' function exists.
* Use composer to handle dependencies.

= 1.1.7 - 18/04/2018 =
* Add composer
* Sync bulk options with post meta values.
* Change the way "product_by_user_role_pre_get_posts()" works. Now it really hides invisible product from queries fixing issues with pagination.
* Add action 'pvbur_save_product' after a product is updated.
* Add a new admin option to hide categories from wp nav menu if there is no products to show.
* Add a filter 'pvbur_hide_empty_cats' to hide categories from wp nav menu if there is no products to show.

= 1.1.6 - 12/04/2018 =
* Add 'alg_wc_pvbur_is_visible' filter.
* Add new option on admin to redirect to a page different from 404 in case a product is invisible.
* Add new filter 'pvbur_invisible_product_redirect' to redirect to a page different from 404 in case a product is invisible.

= 1.1.5 - 26/03/2018 =
* Fix - Core - `is_visible()` - Bulk settings - Products - Returning `false` only.
* Dev - General - Admin Options - "Product quick edit" option added.
* Dev - General - Admin Options - "Products bulk edit" option added.

= 1.1.4 - 12/11/2017 =
* Dev - Core - Possible "`wp_get_current_user()` undefined" error fixed.

= 1.1.3 - 31/10/2017 =
* Dev - Admin Settings - Settings tab title updated.
* Dev - Admin Settings - General - Description updated.

= 1.1.2 - 30/10/2017 =
* Dev - Admin Settings - Bulk Settings - User roles subsections added.
* Dev - Admin Settings - Meta box - Title updated.

= 1.1.1 - 26/10/2017 =
* Dev - Admin Settings - Bulk Settings - "Save all changes" button added to each role's section.
* Dev - Admin Settings - Meta box - Title and descriptions updated.

= 1.1.0 - 25/10/2017 =
* Dev - "Bulk Settings" section added.
* Dev - Admin Settings - Meta box select - `chosen_select` class added.
* Dev - Admin Settings - Meta box on product edit moved to `side` with `low` priority.
* Dev - Code refactoring.
* Dev - Saving settings array as main class property.

= 1.0.0 - 30/08/2017 =
* Initial Release.

== Upgrade Notice ==

= 1.0.0 =
This is the first release of the plugin.
