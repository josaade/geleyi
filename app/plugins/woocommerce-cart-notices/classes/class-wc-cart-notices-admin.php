<?php
/**
 * WooCommerce Cart Notices
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Cart Notices to newer
 * versions in the future. If you wish to customize WooCommerce Cart Notices for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-cart-notices/ for more information.
 *
 * @package     WC-Cart-Notices/Admin
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2013, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * <h2>Cart Notices Admin Class</h2>
 *
 * @since 1.0.7
 */
class WC_Cart_Notices_Admin {


	/**
	 * array of notice types to display name
	 * @var array
	 */
	public $notice_types = array(
		'minimum_amount' => 'Minimum Amount',
		'deadline'       => 'Deadline',
		'referer'        => 'Referer',
		'products'       => 'Products in Cart',
		'categories'     => 'Categories in Cart'
	);

	/**
	 * Construct and initialize the admin class
	 *
	 * @since 1.0.7
	 */
	public function __construct() {

		// load WC styles / scripts
		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'load_wc_styles_scripts' ) );

		// add menu item to WooCommerce menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// handle the create/edit actions
		add_action( 'admin_post_cart_notice_new', array( $this, 'create_cart_notice' ) );
		add_action( 'admin_post_cart_notice_edit', array( $this, 'update_cart_notice' ) );

		if ( defined('DOING_AJAX') ) {
			// ajax search categories handler
			add_action( 'wp_ajax_wc_cart_notices_json_search_product_categories', array( $this, 'woocommerce_json_search_product_categories' ) );
		}
	}


	/**
	 * Add notice screen ID to the list of pages for WC to load its CSS/JS on
	 *
	 * @since 1.0.7
	 * @param array $screen_ids
	 * @return array
	 */
	public function load_wc_styles_scripts( $screen_ids ) {

		$screen_ids[] = 'woocommerce_page_wc-cart-notices';

		return $screen_ids;
	}


	/**
	 * Validate options, called after cart notice create/edit
	 *
	 * @since 1.0.7
	 */
	private function validate_options() {
		global $wpdb, $wc_cart_notices;

		// new cart notice must have a valid notice type selected
		if ( 'cart_notice_new' == $_POST['action'] ) {
			if ( ! $_POST['notice_type'] || ! isset( $this->notice_types[ $_POST['notice_type'] ] ) ) {
				$wc_cart_notices->admin_message_handler->add_error( __( 'You must choose a Notice Type', WC_Cart_Notices::TEXT_DOMAIN ) );
			}
		}

		// notice name is required
		if ( ! $_POST['notice_name'] ) {
			$wc_cart_notices->admin_message_handler->add_error( __( 'You must provide a Notice Name', WC_Cart_Notices::TEXT_DOMAIN ) );
		}

		// notice name already in use?
		if ( 'cart_notice_new' == $_POST['action'] ) {
			$name_exists_query = $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}cart_notices WHERE name = %s", $this->get_request( 'notice_name' ) );
		} elseif ( 'cart_notice_edit' == $_POST['action'] ) {
			$name_exists_query = $wpdb->prepare( "SELECT name FROM {$wpdb->prefix}cart_notices WHERE name = %s and id != %d",
			                                     $this->get_request( 'notice_name' ), $this->get_request( 'id' ) );
		}
		if ( $wpdb->get_var( $name_exists_query ) ) {
			$wc_cart_notices->admin_message_handler->add_error( __( 'That name is already in use', WC_Cart_Notices::TEXT_DOMAIN ) );
		}

		// validate minimum order amount, if set
		if ( $_POST['minimum_order_amount'] && ( ! is_numeric( $_POST['minimum_order_amount'] ) || (float) $_POST['minimum_order_amount'] < 0 ) ) {
			$wc_cart_notices->admin_message_handler->add_error( __( 'Minimum order amount must be positive number, or empty', WC_Cart_Notices::TEXT_DOMAIN ) );
		}

		// validate threshold amount, if set
		if ( $_POST['threshold_order_amount'] && ( ! is_numeric( $_POST['threshold_order_amount'] ) || (float) $_POST['threshold_order_amount'] < 0 ) ) {
			$wc_cart_notices->admin_message_handler->add_error( __( 'Threshold amount must be positive number, or empty', WC_Cart_Notices::TEXT_DOMAIN ) );
		}

		// validate deadline hour, if set
		if ( $_POST['deadline_hour'] && ( ! is_numeric( $_POST['deadline_hour'] ) || (int) $_POST['deadline_hour'] < 1 || (int) $_POST['deadline_hour'] > 24 ) ) {
			$wc_cart_notices->admin_message_handler->add_error( __( 'Deadline hour must be in 24-hour format, between 1 to 24', WC_Cart_Notices::TEXT_DOMAIN ) );
		}
	}


	/**
	 * Action to create a new cart notice
	 *
	 * @since 1.0.7
	 */
	public function create_cart_notice() {
		$this->handle_create_update_cart_notice( 'create' );
	}


	/**
	 * Action to update an existing cart notice
	 *
	 * @since 1.0.7
	 */
	public function update_cart_notice() {
		$this->handle_create_update_cart_notice( 'update' );
	}


	/**
	 * Helper function to perform the create/update cart notice actions
	 *
	 * @since 1.0.7
	 * @param string $action one of 'create' or 'update'
	 */
	private function handle_create_update_cart_notice( $action ) {
		global $wpdb, $wc_cart_notices;

		$this->validate_options();

		if ( $wc_cart_notices->admin_message_handler->error_count() > 0 ) {

			// If there are validation errors, send the data back to the create notice page so the user can fix the issue
			// note that we have to serialize the arrayed values because wordpress redirect messes with the normal way of sending arrays through URL parameters
			$query_params = array(
				'page' => $wc_cart_notices->id,
				'tab' => 'create' == $action ? 'new' : 'edit',
				'notice_name'          => urlencode( $this->get_request( 'notice_name' ) ),
				'notice_enabled'       => urlencode( $this->get_request( 'notice_enabled' ) ),
				'notice_message'       => urlencode( $this->get_request( 'notice_message' ) ),
				'call_to_action'       => urlencode( $this->get_request( 'call_to_action' ) ),
				'call_to_action_url'   => urlencode( $this->get_request( 'call_to_action_url' ) ),
				'minimum_order_amount' => urlencode( $this->get_request( 'minimum_order_amount' ) ),
				'threshold_order_amount' => urlencode( $this->get_request( 'threshold_order_amount' ) ),
				'deadline_days'        => urlencode( serialize( $this->get_request( 'deadline_days' ) ) ),
				'deadline_hour'        => urlencode( $this->get_request( 'deadline_hour' ) ),
				'referer'              => urlencode( $this->get_request( 'referer' ) ),
				'product_ids'          => urlencode( serialize( $this->get_request( 'product_ids' ) ) ),
				'shipping_countries'   => urlencode( serialize( $this->get_request( 'shipping_countries' ) ) ),
				'minimum_quantity'     => urlencode( $this->get_request( 'minimum_quantity' ) ),
				'maximum_quantity'     => urlencode( $this->get_request( 'maximum_quantity' ) ),
				'category_ids'         => urlencode( serialize( $this->get_request( 'category_ids' ) ) ),
			);

			if ( 'create' == $action ) {
				$query_params['notice_type'] = urlencode( $this->get_request( 'notice_type' ) );
			} elseif ( 'update' == $action ) {
				$query_params['id'] = $this->get_request( 'id' );
			}

			return wp_redirect( add_query_arg( $query_params, "admin.php" ) );
		}

		// data common to an insert or update
		$fields = array(
			'name'       => trim( $this->get_request( 'notice_name' ) ),
			'enabled'    => $this->get_request( 'notice_enabled' ) ? 1 : 0,
			'message'    => trim( $this->get_request( 'notice_message' ) ),
			'action'     => trim( $this->get_request( 'call_to_action' ) ),
			'action_url' => trim( $this->get_request( 'call_to_action_url' ) ),
			'date_added' => date("Y-m-d H:i:s")
		);

		// get the notice type, depending on whether we're creating or updating
		if ( 'create' == $action ) {
			$notice_type = $this->get_request( 'notice_type' );
			$fields['type'] = $notice_type;
		} elseif ( 'update' == $action ) {
			// load the immutable notice type from the database
			$notice_type = $wpdb->get_var( $wpdb->prepare( "SELECT type FROM {$wpdb->prefix}cart_notices WHERE id = %d", $this->get_request( 'id' ) ) );
		}

		// set any missing defaults (ie, unchecked check boxes)
		if ( 'deadline' == $notice_type ) {
			$deadline_days = $this->get_request( 'deadline_days' );
			for ( $i = 0; $i < 6; $i++ ) {
				if ( ! isset( $deadline_days[ $i ] ) ) $deadline_days[ $i ] = 0;
			}
		}

		// handle the type-dependent data field
		switch ( $notice_type ) {
			case 'minimum_amount':
				$fields['data']['minimum_order_amount'] = trim( $this->get_request( 'minimum_order_amount' ) );
				$fields['data']['threshold_order_amount'] = trim( $this->get_request( 'threshold_order_amount' ) );
			break;
			case 'deadline':
				$fields['data'] = array(
					'deadline_hour' => trim( $this->get_request( 'deadline_hour' ) ),
					'deadline_days' => $deadline_days,
				);
			break;
			case 'referer': $fields['data']['referer'] = trim( $this->get_request( 'referer' ) ); break;
			case 'products':
				$fields['data']['product_ids']        = $this->get_request( 'product_ids' );
				$fields['data']['shipping_countries'] = $this->get_request( 'shipping_countries' );
				$fields['data']['minimum_quantity']   = $this->get_request( 'minimum_quantity' );
				$fields['data']['maximum_quantity']   = $this->get_request( 'maximum_quantity' );
			break;
			case 'categories': $fields['data']['category_ids'] = $this->get_request( 'category_ids' ); break;
		}

		$fields['data'] = maybe_serialize( $fields['data'] );

		// perform the insert or update
		if ( 'create' == $action ) {
			$wpdb->insert( "{$wpdb->prefix}cart_notices", $fields );
			return wp_redirect( add_query_arg( array( "page" => $wc_cart_notices->id, 'tab' => 'list', "result" => "created" ), 'admin.php' ) );
		} elseif ( 'update' == $action ) {
			$id = $this->get_request( 'id' );
			$wpdb->update( "{$wpdb->prefix}cart_notices", $fields, array( 'id' => $id ) );
			return wp_redirect( add_query_arg( array( "page" => $wc_cart_notices->id, 'id' => $id, 'tab' => 'edit', "result" => "updated" ), 'admin.php' ) );
		}

	}


	/**
	 * Add the plugin menu option under Settings
	 *
	 * @since 1.0.7
	 */
	public function add_menu_item() {

		global $wc_cart_notices;

		add_submenu_page( 'woocommerce',                                       // parent menu
		                  __( 'WooCommerce Cart Notices', WC_Cart_Notices::TEXT_DOMAIN ), // page title
		                  __( 'Cart Notices', WC_Cart_Notices::TEXT_DOMAIN ),             // menu title
		                  'manage_woocommerce',                                // capability
		                  $wc_cart_notices->id,                                // unique menu slug
		                  array( $this, 'wc_cart_notices_options' ) );        // callback
	}


	/**
	 * Render the plugin options page, and handle the enable/disable/delete
	 * tab actions
	 *
	 * @since 1.0.7
	 */
	public function wc_cart_notices_options() {

		global $wpdb, $wc_cart_notices;

		// Check the user capabilities
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', WC_Cart_Notices::TEXT_DOMAIN ) );
		}

		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'list';

		if ( 'list' == $tab ) {

			$notices = $wc_cart_notices->get_notices();

			// load product names, category names, and deadline day names, as needed
			foreach ( $notices as $key => $notice ) {
				if ( 'products' == $notice->type )       $notices[ $key ] = $this->load_product_data( $notice );
				elseif ( 'categories' == $notice->type ) $notices[ $key ] = $this->load_category_data( $notice );
				elseif ( 'deadline' == $notice->type )   $notices[ $key ] = $this->load_deadline_data( $notice );
			}

		} elseif ( 'new' == $tab ) {

			// create a new dummy object, loading any request data if there was a validation error
			$notice = $this->load_notice_from_request();

			if ( 'products' == $notice->type )       $this->load_product_data( $notice );
			elseif ( 'categories' == $notice->type ) $this->load_category_data( $notice );

		} elseif ( 'edit' == $tab ) {

			$id = $_GET['id'];

			$notice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}cart_notices WHERE id = %d", $id ) );
			$notice->data = maybe_unserialize( $notice->data );

			if ( ! $notice ) {
				wp_die( 'The requested data could not be found!', WC_Cart_Notices::TEXT_DOMAIN );
			}

			if ( isset( $_REQUEST['notice_name'] ) ) {
				// edit page error request, get the submitted data from the request so error messages can be displayed and the user can fix as necessary
				$notice_type = $notice->type;
				$notice = $this->load_notice_from_request();
				$notice->id = $id;
				$notice->type = $notice_type;
			}

			if ( 'products' == $notice->type ) $this->load_product_data( $notice );
			elseif ( 'categories' == $notice->type ) $this->load_category_data( $notice );

		} elseif ( 'enable' == $tab ) {

			$id = $_GET['id'];
			// enable
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}cart_notices SET enabled=true WHERE id = %d", $id ) );

			wp_redirect( add_query_arg( array( 'page' => $wc_cart_notices->id, 'result' => 'enabled' ), 'admin.php' ) );
			exit;

		} elseif ( 'disable' == $tab ) {

			$id = $_GET['id'];
			// disable
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}cart_notices SET enabled=false WHERE id = %d", $id ) );

			wp_redirect( add_query_arg( array( 'page' => $wc_cart_notices->id, 'result' => 'disabled' ), 'admin.php' ) );
			exit;

		} elseif ( 'delete' == $tab ) {

			$id = $_GET['id'];
			// delete
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}cart_notices WHERE id = %d", $id ) );

			wp_redirect( add_query_arg( array( 'page' => $wc_cart_notices->id, 'result' => 'deleted' ), 'admin.php' ) );
			exit;
		}

		include_once( $wc_cart_notices->get_plugin_path() .  '/admin/admin-options.php' );
	}


	/** AJAX ************************************************************/


	/**
	 * Ajax function to return product categories matching the search term $x
	 *
	 * @param string $x search string
	 *
	 * @return string json encoded array of matching category names, or nothing
	 */
	public function woocommerce_json_search_product_categories( $x = '' ) {

		check_ajax_referer( 'search-product-categories', 'security' );

		$term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );

		if ( empty( $term ) ) die();

		$args = array(
			'search'     => $term,
			'hide_empty' => 0,
		);

		$categories = get_terms( 'product_cat', $args );

		$found_categories = array();
		if ( $categories ) {
			foreach ( $categories as $category ) {
				$found_categories[ $category->term_id ] = $category->name;
			}
		}

		echo json_encode( $found_categories );

		exit();
	}


	/** Helper methods ******************************************************/


	/**
	 * Helper function to create and load a notice object from the client
	 * request
	 *
	 * @since 1.0.7
	 * @return object notice settings object with data loaded from the
	 *         current request
	 */
	private function load_notice_from_request() {

		$notice = (object) array(
			'name'       => $this->get_request( 'notice_name' ),
			'enabled'    => $this->get_request( 'notice_enabled' ),
			'type'       => $this->get_request( 'notice_type' ),
			'message'    => $this->get_request( 'notice_message' ),
			'action'     => $this->get_request( 'call_to_action' ),
			'action_url' => $this->get_request( 'call_to_action_url' ),
			'data'       => array(
				'minimum_order_amount' => $this->get_request( 'minimum_order_amount' ),
				'threshold_order_amount' => $this->get_request( 'threshold_order_amount' ),
				'deadline_hour'        => $this->get_request( 'deadline_hour' ),
				'deadline_days'        => unserialize( $this->get_request( 'deadline_days' ) ),
				'referer'              => $this->get_request( 'referer' ),
				'product_ids'          => unserialize( $this->get_request( 'product_ids' ) ),
				'shipping_countries'   => unserialize( $this->get_request( 'shipping_countries' ) ),
				'minimum_quantity'     => $this->get_request( 'minimum_quantity' ),
				'maximum_quantity'     => $this->get_request( 'maximum_quantity' ),
				'category_ids'         => unserialize( $this->get_request( 'category_ids' ) ),
			),
		);
		return $notice;
	}


	/**
	 * Safely get value from the REQUEST object
	 *
	 * @since 1.0.7
	 * @return string value if it exists, null otherwise
	 */
	private function get_request( $name ) {
		if ( isset( $_REQUEST[ $name ] ) ) {
			if ( is_string( $_REQUEST[ $name ] ) ) return stripslashes( $_REQUEST[ $name ] );
			else return $_REQUEST[ $name ];
		}
		return null;
	}


	/**
	 * Helper function to load the product data for the given notice
	 *
	 * @since 1.0.7
	 * @param object $notice notice settings object
	 * @return object notice settings object with products loaded
	 */
	private function load_product_data( $notice ) {

		$products = array();
		// get any products for the autocompleting search box
		if ( isset( $notice->data['product_ids'] ) && is_array( $notice->data['product_ids'] ) ) {
			foreach ( $notice->data['product_ids'] as $product_id ) {
				$title = get_the_title( $product_id );
				$sku   = get_post_meta( $product_id, '_sku', true );

				if ( ! $title ) continue;

				if ( isset( $sku ) && $sku ) $sku = ' (SKU: ' . $sku . ')';

				$products[ $product_id ] = $title . $sku;
			}
		}

		$notice->data['products'] = $products;

		return $notice;
	}


	/**
	 * Helper function to load the category data for the given notice
	 *
	 * @since 1.0.7
	 * @param object $notice notice settings object
	 * @return object notice settings object with categories loaded
	 */
	private function load_category_data( $notice ) {

		$categories = array();
		// get any product categories for the autocompleting search box
		if ( isset( $notice->data['category_ids'] ) && is_array( $notice->data['category_ids'] ) ) {
			foreach ( $notice->data['category_ids'] as $category_id ) {
				$category = get_term( $category_id, 'product_cat' );

				if ( ! $category ) continue;

				$categories[ $category_id ] = $category->name;
			}
		}

		$notice->data['categories'] = $categories;

		return $notice;
	}


	/**
	 * Helper function to load displayable deadline days data for this notice
	 *
	 * @since 1.0.7
	 * @param object $notice notice settings object
	 * @return object notice settings object with deadline days formatted
	 */
	private function load_deadline_data( $notice ) {
		$days = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thur', 'Fri', 'Sat' );
		$active_days = array();
		foreach( $days as $key => $name ) {
			if ( isset( $notice->data['deadline_days'][ $key ] ) && $notice->data['deadline_days'][ $key ] ) $active_days[] = $name;
		}

		$notice->data['deadline_days_names'] = $active_days;

		return $notice;
	}


}
