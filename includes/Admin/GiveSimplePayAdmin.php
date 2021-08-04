<?php

namespace SalsaBoy990\GiveSimplePayGateway\Admin;

// Közvetlen hozzáférés esetén kilép
if (!defined('ABSPATH')) exit;

trait GiveSimplePayAdmin
{
  /**
   * Register Admin Settings.
   *
   * @param array $settings List of admin settings.
   *
   * @since 1.0.0
   *
   * @return array
   */
  public function registerPaymentGatewaySettingFields($settings): array
  {
    switch (give_get_current_setting_section()) {
      case 'simplepay-settings':
        $settings = array(
          array(
            'id'   => 'give_title_simplepay',
            'type' => 'title',
          ),
        );

        $settings[] = array(
          'name' => __('HUF_MERCHANT', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'desc' => __('merchant account ID (HUF)', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'id'   => 'huf_merchant_simplepay',
          'type' => 'text',
        );

        $settings[] = array(
          'name' => __('HUF_SECRET_KEY', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'desc' => __('secret key for account ID (HUF)', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'id'   => 'huf_secret_key_simplepay',
          'type' => 'text',
        );

        $settings[] = array(
          'name' => __('EUR_MERCHANT', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'desc' => __('merchant account ID (EUR)', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'id'   => 'eur_merchant_simplepay',
          'type' => 'text',
        );

        $settings[] = array(
          'name' => __('EUR_SECRET_KEY', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'desc' => __('secret key for account ID (EUR)', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'id'   => 'eur_secret_key_simplepay',
          'type' => 'text',
        );

        $settings[] = array(
          'name' => __('USD_MERCHANT', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'desc' => __('merchant account ID (USD)', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'id'   => 'usd_merchant_simplepay',
          'type' => 'text',
        );

        $settings[] = array(
          'name' => __('USD_SECRET_KEY', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'desc' => __('secret key for account ID (USD)', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'id'   => 'usd_secret_key_simplepay',
          'type' => 'text',
        );

        $settings[] = array(
          'name' => __('SANDBOX', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'desc' => __('Sandbox - teszt mód', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
          'id'   => 'sandbox_simplepay',
          'type' => 'radio_inline',
          'default' => 'true',
          'options' => apply_filters(
            'give_forms_content_options_select',
            array(
              'true'   => __('Bekapcsolva', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
              'false'  => __('Kikapcsolva', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN),
            )
          ),
        );

        $settings[] = array(
          'id'   => 'give_title_simplepay',
          'type' => 'sectionend',
        );

        break;
    } // End switch().

    return $settings;
  }


  /**
   * Register Section for Payment Gateway Settings.
   *
   * @param array $sections List of payment gateway sections.
   *
   * @since 1.0.0
   *
   * @return array
   */

  public function registerPaymentGatewaySections($sections): array
  {
    // `simplepay-settings` is the name/slug of the payment gateway section.
    $sections['simplepay-settings'] = __('SimplePay', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN);
    return $sections;
  }


  /**
   * Register Simplepay payment method.
   *
   * @since 1.0.0
   *
   * @param array $gateways List of registered gateways.
   *
   * @return array
   */
  public function registerPaymentMethod($gateways): array
  {
    // Duplicate this section to add support for multiple payment method from a custom payment gateway.
    $gateways['simplepay'] = array(
      'admin_label'    => __('SimplePay - Debit Card', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN), // This label will be displayed under Give settings in admin.
      'checkout_label' => __('SimplePay - Card Payment', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN), // This label will be displayed on donation form in frontend.
    );

    return $gateways;
  }


  /**
   * Egyedi callback a konfiguráció felülírására (az admin felületen megadott beállítások alapján)
   * 
   * @return void
   */
  public function overwriteSimplePayConfig(): void
  {
    if (give_get_option('huf_merchant_simplepay')) {
      $this->config['HUF_MERCHANT'] = give_get_option('huf_merchant_simplepay');
    };

    if (give_get_option('huf_merchant_simplepay')) {
      $this->config['HUF_SECRET_KEY'] = give_get_option('huf_secret_key_simplepay');
    };

    if (give_get_option('eur_merchant_simplepay')) {
      $this->config['EUR_MERCHANT'] = give_get_option('eur_merchant_simplepay');
    };

    if (give_get_option('eur_merchant_simplepay')) {
      $this->config['EUR_SECRET_KEY'] = give_get_option('eur_secret_key_simplepay');
    };

    if (give_get_option('usd_merchant_simplepay')) {
      $this->config['USD_MERCHANT'] = give_get_option('usd_merchant_simplepay');
    };

    if (give_get_option('usd_merchant_simplepay')) {
      $this->config['USD_SECRET_KEY'] = give_get_option('usd_secret_key_simplepay');
    };

    if (give_get_option('sandbox_simplepay')) {
      $this->config['SANDBOX'] = ((give_get_option('sandbox_simplepay') === 'true') ? 1 : 0);
    };
  }
}
