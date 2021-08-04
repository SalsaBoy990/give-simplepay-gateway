<?php

/**
 * Plugin Name: SimplePay Payment Gateway for Give
 * Plugin URI: https://example.com
 * Description: SimplePay Payment Gateway for Give
 * Version: 1.0.0
 * Author: SalsaBoy990
 * Author URI: https://example.com
 * License: LGPL 3.0
 * Text Domain: give-simplepay
 * Domain Path: /lang
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// require all requires once
require_once 'requires.php';

use \SalsaBoy990\GiveSimplePayGateway\GiveSimplePayGateway as GiveSimplePayGateway;

add_action('plugins_loaded', 'simplepay_for_give_init', 0);
if (!function_exists('simplepay_for_give_init')) {
  function simplepay_for_give_init()
  {
    // instantiate main plugin singleton class
    GiveSimplePayGateway::getInstance();
  }
}
