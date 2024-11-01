<?php
/*
Plugin Name: WooCommerce Mycelium Gear
Plugin URI: http://plugins.rmweblab.com/woo-mycelium-gear
Description: WooCommerce Mycelium Gear Payment Gateway Extends WooCommerce Payment Gateway allow customer to pay using mycelium gear.
Author: Anas
Version: 2.0.0
Author URI: http://rmweblab.com
Text Domain: woo-mycelium-gear
Domain Path: /languages
WC tested up to: 3.2.3
WC requires at least: 3.2.3

Copyright: © 2017 RMWebLab.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/**
 * Main WC_MyceliumGear class which sets the gateway up for us
 */
class WC_MyceliumGear {

	/**
	 * Constructor
	 */
	public function __construct() {
		define( 'WC_MYGEAR_VERSION', '2.0.0' );
		define( 'WC_MYGEAR_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_MYGEAR_MAIN_FILE', __FILE__ );

		// Actions
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		//Process when payment complete of done.
		add_action( 'wp', array( $this, 'process_mycelium_order_init' ) );

		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_order' ) );
		add_action( 'woocommerce_order_status_pending_to_cancelled', array( $this, 'cancel_order' ) );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_order' ) );

		//Update payment URL for
		add_filter( 'woocommerce_get_checkout_payment_url', array( &$this, 'get_checkout_gear_payment_url' ), 10, 2 );

	}

	/**
	 * Add relevant links to plugins page
	 * @param  array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=myceliumgear' ) . '">' . __( 'Settings', 'woo-mycelium-gear' ) . '</a>',
			'<a href="http://rmweblab.com/support">' . __( 'Support', 'woo-mycelium-gear' ) . '</a>',
			'<a href="http://plugins.rmweblab.com/woocommerce-mycelium-gear">' . __( 'Docs', 'woo-mycelium-gear' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Init localisations and files
	 */
	public function init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// Includes
		require_once( 'inc/class-wc-mycelium-gear-api.php' );

		// Includes
		require_once( 'inc/class-wc-gateway-mycelium-gear.php' );

		// Localisation
		load_plugin_textdomain( 'woo-mycelium-gear', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add flag to payment url for failed/ pending switch orders.
	 *
	 * @since 2.1
	 */
	public function get_checkout_gear_payment_url( $pay_url, $order ) {
		return $pay_url;
	}

	/**
	 * Process call back result after mycellium payment webhook
	 *
	 * @return void
	 */
	function process_mycelium_order_init(){
		$mycelium_gateway = new WC_Gateway_MyceliumGear();
		$mycelium_gateway->process_mycelium_order();
	}

	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {
			if(class_exists( 'WC_Gateway_MyceliumGear' )){
				$methods[] = 'WC_Gateway_MyceliumGear';
			}
		return $methods;
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param  int $order_id
	 */
	public function capture_order( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( $order->get_payment_method() == 'myceliumgear' ) {
				//Used for custom order data
		}
	}

	/**
	 * Cancel pre-auth on cancellation
	 *
	 * @param  int $order_id
	 * @param  obj $order
	 */
	public function cancel_order( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( $order->get_payment_method() == 'myceliumgear' ) {
				//Use when cancel order
				$mygear_order_id = get_post_meta( $order_id, '_mygear_order_id', TRUE );
				$mygear_address = get_post_meta( $order_id, '_mygear_address', TRUE );
				$mygear_payment_id = get_post_meta( $order_id, '_mygear_payment_id', TRUE );
				if($mygear_order_id){
					$mycelium_gateway = new WC_Gateway_MyceliumGear();
					$mycelium_gateway->mycelium_order_cancel($mygear_order_id);
				}
		}

	}

}

new WC_MyceliumGear();
