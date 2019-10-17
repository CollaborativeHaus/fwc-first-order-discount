<?php 
/**
 * Plugin Name: First Order Discount
 * Plugin URI: https://collaborativehausmarketing.com/plugins/first-order-discount/
 * Description: This plugin will add a discount to a customers first order
 * Version: 0.1.0
 * Author: Collaborative Haus
 * Author URI: https://collaborativehausmarketing.com
 * Text Domain: wc-first-order-discount
 * Domain Path: /lang
 * License: GPL2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/* Include translations */
function fod_load_textdomain() {
  load_plugin_textdomain( 'woo-first-discount', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}
add_action('plugins_loaded', 'fod_load_textdomain');

/* Add Admin Settings Page */
function fod_add_admin_menu() {
  add_submenu_page( 'woocommerce', 'First Order Discount', 'First Order Discount', 'manage_options', 'wc_first_order_discount', 'fod_add_options_page' );
}
add_action('admin_menu', 'fod_add_admin_menu');

function fod_settings_init() {
  register_setting('fod', 'fod_settings');
  add_settings_section( 'fod_settings_section', __('Choose your setting', 'wc-first-order-discount'), '', 'fod' );
  add_settings_field( 'fod_select', __( 'Select discount type', 'wc-first-order-discount' ), 'fod_select_render', 'fod', 'fod_settings_section' );
  add_settings_field( 'fod_value',  __( 'Enter discount value', 'wc-first-order-discount' ), 'fod_value_render', 'fod',  'fod_settings_section' );
  add_settings_field( 'fod_sales_discount',  __( 'Sales Discount', 'wc-first-order-discount' ), 'fod_sales_discount_render', 'fod', 'fod_settings_section' );
}
add_action('admin_init', 'fod_settings_init');

function fod_select_render() {
  $options = get_option( 'fod_settings' );
  ?>
  <input id="off" type='radio' name='fod_settings[fod_select]' <?php checked( $options['fod_select'], 'off' ); ?> value='off'>
  <label for="off"><?php echo __( 'Disable first order discount', 'wc-first-order-discount' ); ?></label>
  <br>
  <input id="fixed" type='radio' name='fod_settings[fod_select]' <?php checked( $options['fod_select'], 'fixed' ); ?> value='fixed'>
  <label for="fixed"><?php echo __( 'Fixed discount', 'wc-first-order-discount' ); ?></label>
  <br>
  <input id="percent" type='radio' name='fod_settings[fod_select]' <?php checked( $options['fod_select'], 'percent' ); ?> value='percent'>
  <label for="percent"><?php echo __( 'Percent discount', 'wc-first-order-discount' ); ?></label>
  <?php
}

function fod_value_render() {
  $options = get_option( 'fod_settings' );
  ?>
  <input type='number' min="0" name='fod_settings[fod_value]' value='<?php echo $options['fod_value']; ?>'>
  <?php
}

function fod_sales_discount_render(  ) { 
  $options = get_option( 'fod_settings' );
  ?>
  <input id="off-sales" type='radio' name='fod_settings[fod_sales_discount]' <?php checked( $options['fod_sales_discount'], 'off' ); ?> value='off'>
  <label for="off"><?php echo __( 'Disable first order discount if sale products in cart', 'wc-first-order-discount' ); ?></label>
  <br>
  <input id="on-sales" type='radio' name='fod_settings[fod_sales_discount]' <?php checked( $options['fod_sales_discount'], 'on' ); ?> value='on'>
  <label for="on"><?php echo __( 'Enable first order discount if sale products in cart', 'wc-first-order-discount' ); ?></label>
  <?php
}

function fod_add_options_page() {
  ?>
  <form action='options.php' method='post'>
    <h2><?php echo __( 'First Order Discount', 'wc-first-order-discount' ); ?></h2>
    <?php
    settings_fields( 'fod' );
    do_settings_sections( 'fod' );
    submit_button();
    ?>
  </form>
  <?php
}

function fod_add_fee() {
  global $wpdb, $woocommerce;
  if (is_user_logged_in()) {
    $customer_id = get_current_user_id();
    $order_count = wc_get_customer_order_count($customer_id);
    $options = get_option('fod_settings');
    $discount_type =$options['fod_select'];
    $discount_value =$options['fod_value'];
    $enabled_for_products_on_sale=$options['fod_sales_discount'];
    $cart_has_sales_products=false;

    if ($enabled_for_products_on_sale==off){
      foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        $product = new WC_Product($cart_item['product_id']); 
        $onsale=$product->is_on_sale() ? 'yes' : 'no'; 
        if ($onsale=='yes'){
          $cart_has_sales_products=true;
        }
      }
    }



    if ($order_count == 0. and $discount_type !== 'off' and $cart_has_sales_products == false) {
      $subtotal = WC()->cart->cart_contents_total;
      if ($discount_type == 'fixed') {
        WC()->cart->add_fee('First Order Discount', -$discount_value);
      } else {
        $discount = $discount_value/100;
        WC()->cart->add_fee( 'First Order Discount', -$subtotal*$discount );
      }
    }
  }
}
add_action( 'woocommerce_cart_calculate_fees','fod_add_fee' );


