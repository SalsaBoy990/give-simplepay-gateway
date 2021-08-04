<?php

namespace SalsaBoy990\GiveSimplePayGateway;

use SalsaBoy990\GiveSimplePayGateway\Admin\GiveSimplePayAdmin as GiveSimplePayAdmin;
use SalsaBoy990\GiveSimplePayGateway\Pay\GiveSimplePayPayment as GiveSimplePayPayment;

// Közvetlen hozzáférés esetén kilép
if (!defined('ABSPATH')) exit;

// SimplePayment osztályok importálása
require_once 'simplepay/src/SimplePayV21.php';
require_once 'simplepay/src/SimplePayV21Auto.php';

// ha a Give plugin nincs aktiválva, ez az osztály nem létezik
if (!class_exists('Give')) {
  // itt van definiálva a deactivate_plugins()
  require_once ABSPATH . 'wp-admin/includes/plugin.php';

  // deaktiváljuk a plugint
  deactivate_plugins('give-simplepay-gateway/give-simplepay-gateway.php');
  return;
}


/**
 * [Description GiveSimplePayGateway]
 */
final class GiveSimplePayGateway
{
  use GiveSimplePayAdmin;
  use GiveSimplePayPayment;

  private const TEXT_DOMAIN = 'give-simplepay';
  private const TRANSACTION_TYPE = 'CIT';
  private const CURRENCY = 'HUF';
  private const LANGUAGE = 'HU';

  // class instance
  private static $instance;

  // Konfiguráció
  protected $config = array();

  protected $errorMessages;


  /**
   * Osztálypéldány visszaadása vagy létrehozása, ha még nem létezik
   * 
   * @return self $instance
   */
  public static function getInstance()
  {
    if (self::$instance == null) {
      self::$instance = new self();
    }
    return self::$instance;
  }


  /**
   * @return void
   */
  private function __construct()
  {
    // Konfiguráció, hibaüzenetek betöltése
    $this->config = require 'simplepay/src/config.php';
    $this->errorMessages = require 'errorMessages.php';

    // Fordításfájlok betöltése
    add_action('plugins_loaded', array($this, 'loadTextdomain'));

    // Fizetési átjáró
    add_filter('give_get_sections_gateways', array($this, 'registerPaymentGatewaySections'));

    // Fizetési átjáró regisztrálása
    add_filter('give_payment_gateways',  array($this, 'registerPaymentMethod'));

    // Az adomány feldolgozása
    add_action('give_gateway_simplepay', array($this, 'processDonation'));

    // Egyedi rejtett input mezők hozzáadása az összes adományozó űrlaphoz
    add_action('give_after_donation_levels', array($this, 'addCustomBrowserFormFields'), 10, 1);

    // Admin settings
    add_filter('give_get_settings_gateways', array($this, 'registerPaymentGatewaySettingFields'));

    // Egyedi hook a konfiguráció felülírására (az admin felületen megadott beállítások alapján)
    add_action('give_gateway_simplepay_overwrite_config', array($this, 'overwriteSimplePayConfig'), 10, 0);

    add_action('give_payment_mode_after_gateways', array($this, 'addSimplePayLogoToDonationForm'), 10, 1);
  }


  /**
   * @return void
   */
  public function __destruct()
  {
  }


  /**
   * Add Custom Donation Form Fields
   *
   * @param $form_id
   * 
   * @return void
   */
  public function addCustomBrowserFormFields($form_id): void
  {
?>
    <div id="give-referral-wrap">
      <input id="browser-java" type="hidden" name="give-java" value="">
      <input id="browser-lang" type="hidden" name="give-lang" value="">
      <input id="browser-color" type="hidden" name="give-color" value="">
      <input id="browser-height" type="hidden" name="give-height" value="">
      <input id="browser-width" type="hidden" name="give-width" value="">
      <input id="browser-tz" type="hidden" name="give-tz" value="">
      <script>
        jQuery(function($) {
          // Szükség van ezekre a tulajdonságokra a 3D Secure bankkártya ellenőrzéshez
          $('#browser-java').val(navigator.javaEnabled());
          $('#browser-lang').val(navigator.language);
          $('#browser-color').val(screen.colorDepth);
          $('#browser-height').val(screen.height);
          $('#browser-width').val(screen.width);
          $('#browser-tz').val(new Date().getTimezoneOffset());

          // elrejtve marad a simplepay logo, ha nem a simplepay fizetési mód az alapértelmezett
          var simplepayPaymentOption = $('#give-gateway-option-simplepay');
          var simplePayLogo = $('#simplepay-logo');
          if (simplepayPaymentOption.parent().hasClass('give-gateway-option-selected')) {
            simplePayLogo.show();
          } else {
            simplePayLogo.hide();
          }
        });
      </script>
    </div>
    <?php
  }


  public function addSimplePayLogoToDonationForm($form_id)
  {
    $gateways  = give_get_enabled_payment_gateways($form_id);
    $selected_gateway  = give_get_chosen_gateway($form_id);

    // Determine the default gateway.
    $checked                   = checked('simplepay', $selected_gateway, false);
    $is_payment_method_visible = isset($gateways['simplepay']['is_visible']) ? $gateways['simplepay']['is_visible'] : true;

    if (true === $is_payment_method_visible &&  $selected_gateway === 'simplepay') {
    ?>
      <a id="simplepay-logo" href="http://simplepartner.hu/PaymentService/Fizetesi_tajekoztato.pdf" target="_blank">
        <span style="display: inline-block; padding-left: 36px;">Fizetési tájékoztató</span>
        <img style="margin-top: 10px; margin-bottom: 30px; width: 100%; max-width: 482px; height: auto;" src="<?php echo plugin_dir_url(__FILE__) . '../assets/simplepay_bankcard_logos_left_482x40.png'; ?>" title="SimplePay - Online bankkártyás fizetés" alt="SimplePay vásárlói tájékoztató">
      </a>
      <script>
        jQuery(function($) {
          // Ha csak egy fizetési mód engedélyezett, a GiveWP plugin nem jeleníti meg azokat
          // Nekünk viszont mindig meg kell jelenítenünk a Simplepay logót és a tájékoztatót
          var paymentMethodsContainer = $('fieldset#give-payment-mode-select');
          paymentMethodsContainer.show();

          var simplepayPaymentOption = $('#give-gateway-option-simplepay');
          var offlinePaymentOption = $('#give-gateway-option-offline');
          var manualpayPaymentOption = $('#give-gateway-option-manual');

          // Ha a simplepay a kiválasztott fizetési opció, akkor
          // megjelenítjük a Simplepay logót a fizetési tájékoztató linkkel egyetemben
          simplepayPaymentOption.on('click', function() {
            var simplePayLogo = $('#simplepay-logo').show();
          });
          offlinePaymentOption.on('click', function() {
            var simplePayLogo = $('#simplepay-logo').hide();
          });
          manualpayPaymentOption.on('click', function() {
            var simplePayLogo = $('#simplepay-logo').hide();
          });
        });
      </script>
<?php
    }
  }


  /**
   * Fordítás fájlok betöltése (amennyiben léteznek)
   * 
   * @return void
   */
  public function loadTextdomain(): void
  {
    // modified slightly from https://gist.github.com/grappler/7060277#file-plugin-name-php
    $domain = self::TEXT_DOMAIN;
    $locale = apply_filters('plugin_locale', get_locale(), $domain);

    load_textdomain($domain, trailingslashit(\WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
    load_plugin_textdomain($domain, false, basename(dirname(__FILE__, 2)) . '/languages/');
    load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/lang/');
  }


  /**
   * Process SimplePay checkout submission.
   *
   * @param array $posted_data List of posted data.
   *
   * @since  1.0.0
   * @access public
   *
   * @return void
   */
  public function processDonation($posted_data): void
  {
    // Bailout, if the current gateway and the posted gateway mismatched.
    if ('simplepay' !== $posted_data['post_data']['give-gateway']) {
      return;
    }

    // Make sure we don't have any left over errors present.
    give_clear_errors();

    // Validate nonce.
    give_validate_nonce($posted_data['gateway_nonce'], 'give-gateway');

    // Any errors?
    $errors = give_get_errors();

    // No errors, proceed.
    if (!$errors) {
      $form_id         = intval($posted_data['post_data']['give-form-id']);
      $price_id        = !empty($posted_data['post_data']['give-price-id']) ? $posted_data['post_data']['give-price-id'] : 0;
      $donation_amount = !empty($posted_data['price']) ? $posted_data['price'] : 0;

      // Setup the payment details.
      $donation_data = array(
        'price'           => $donation_amount,
        'give_form_title' => $posted_data['post_data']['give-form-title'],
        'give_form_id'    => $form_id,
        'give_price_id'   => $price_id,
        'date'            => $posted_data['date'],
        'user_email'      => $posted_data['user_email'],
        'purchase_key'    => $posted_data['purchase_key'],
        'currency'        => give_get_currency($form_id),
        'user_info'       => $posted_data['user_info'],
        'status'          => 'pending',
        'gateway'         => 'simplepay',
      );

      // Record the pending donation.
      $donation_id = give_insert_payment($donation_data);

      if (!$donation_id) {
        // Record Gateway Error as Pending Donation in Give is not created.
        give_record_gateway_error(
          __('SimplePay Payment Method Error. Unable to process donation payment', GiveSimplePayGateway::TEXT_DOMAIN),
          sprintf(
            /* translators: %s Exception error message. */
            __('The SimplePay Gateway returned an error while creating a pending donation.', GiveSimplePayGateway::TEXT_DOMAIN)
          )
        );

        // Send user back to checkout.
        give_send_back_to_checkout();
        return;
      }

      // Do the actual payment processing using the custom payment gateway API. To access the GiveWP settings, use give_get_option() 
      // as a reference, this pulls the API key entered above: give_get_option('insta_for_give_instamojo_api_key')
      $this->payWithSimplePay(
        $donation_id,
        GiveSimplePayGateway::TRANSACTION_TYPE,
        GiveSimplePayGateway::CURRENCY,
        GiveSimplePayGateway::LANGUAGE
      );
      give_die();
    } else {
      // Send user back to checkout.
      give_send_back_to_checkout();
      give_die();
    }
  }
}
