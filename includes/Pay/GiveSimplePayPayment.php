<?php

namespace SalsaBoy990\GiveSimplePayGateway\Pay;

// Közvetlen hozzáférés esetén kilép
if (!defined('ABSPATH')) exit;

trait GiveSimplePayPayment
{
  /**
   *  Copyright (C) 2020 OTP Mobil Kft.
   * 
   *  --- Modified By SalsaBoy990 ---
   *
   *  PHP version 7
   *
   *  This program is free software: you can redistribute it and/or modify
   *   it under the terms of the GNU General Public License as published by
   *   the Free Software Foundation, either version 3 of the License, or
   *   (at your option) any later version.
   *
   *   This program is distributed in the hope that it will be useful,
   *   but WITHOUT ANY WARRANTY; without even the implied warranty of
   *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   *   GNU General Public License for more details.
   *
   *  You should have received a copy of the GNU General Public License
   *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
   *
   * @category  SDK
   * @package   SimplePayV2_SDK
   * @author    SimplePay IT Support <itsupport@otpmobil.com>
   * @copyright 2020 OTP Mobil Kft.
   * @license   http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
   * @link      http://simplepartner.hu/online_fizetesi_szolgaltatas.html
   * 
   * 
   * @param int $donation_id
   * @param string $transactionType
   * @param string $currency
   * @param string $language
   * 
   * @return void
   */
  public function payWithSimplePay(
    int $donation_id,
    string $transactionType = 'CIT',
    string $currency = 'HUF',
    string $language = 'HU'
  ): void {

    // Overwrite config options from admin settings
    do_action('give_gateway_simplepay_overwrite_config');

    // Fizetési folyamat megindítása
    $trx = new \SimplePayAuto;

    // Konfigurációs adatok hozzáadása
    $trx->addConfig($this->config);

    // Kártyaadatok
    //-----------------------------------------------------------------------------------------
    // Kártyaszám
    $cartNumber = sanitize_text_field($_REQUEST['card_number']);
    $cartNumber = str_replace(' ', '', $cartNumber);

    // Lejárás dátuma
    $cardExpiry = sanitize_text_field($_REQUEST['card_expiry']);
    $cardExpiry = str_replace(' ', '', $cardExpiry);
    $cardExpiry = str_replace('/', '', $cardExpiry);

    // CVC kód
    $cvc = intval($_REQUEST['card_cvc']);
    $cardName = sanitize_text_field($_REQUEST['card_name']);

    // Kártyaszám (validálva kliens oldalon a Luhn algoritmussal)
    $trx->addGroupData('cardData', 'number', $cartNumber);
    $trx->addGroupData('cardData', 'expiry', $cardExpiry);
    $trx->addGroupData('cardData', 'cvc', $cvc);
    $trx->addGroupData('cardData', 'holder', $cardName);


    /**
     * In case of 3DS of SimplePay
     */
    $type = $transactionType;
    if (isset($_REQUEST['type'])) {
      $type = $_REQUEST['type'];
    }
    $trx->addData('type', $type);


    // Transactions with user presence
    //-----------------------------------------------------------------------------------------
    if ($type == 'CIT') {
      $serverData = [
        'accept' => $_SERVER['HTTP_ACCEPT'],
        'agent' => $_SERVER['HTTP_USER_AGENT'],
        'ip' => $_SERVER['REMOTE_ADDR']
      ];

      if (isset($serverData)) {
        $trx->addGroupData('browser', 'accept', $serverData['accept']);
        $trx->addGroupData('browser', 'agent', $serverData['agent']);
        $trx->addGroupData('browser', 'ip', $serverData['ip']);
      }

      $java = sanitize_text_field($_REQUEST['give-java']);
      $lang = sanitize_text_field($_REQUEST['give-lang']);
      $color = sanitize_text_field($_REQUEST['give-color']);
      $height = sanitize_text_field($_REQUEST['give-height']);
      $width = sanitize_text_field($_REQUEST['give-width']);
      $tz = sanitize_text_field($_REQUEST['give-tz']);

      $trx->addGroupData('browser', 'java', $java);
      $trx->addGroupData('browser', 'lang', $lang);
      $trx->addGroupData('browser', 'color', $color);
      $trx->addGroupData('browser', 'height', $height);
      $trx->addGroupData('browser', 'width', $width);
      $trx->addGroupData('browser', 'tz', $tz);

      // Challenge REDIRECT URL for CIT transactions
      //-----------------------------------------------------------------------------------------
      // common URL for all result
      $trx->addData('url', $this->config['URL']);
    }


    // Az adomány összege
    //-----------------------------------------------------------------------------------------
    $totalSumOfDonation = sanitize_text_field($_REQUEST['give-amount']);
    // Fontos, hogy ne legyenek benne ezres elválasztók, akár pont, akár vessző esetén
    $totalSumOfDonation = str_replace('.', '', $totalSumOfDonation);
    $totalSumOfDonation = str_replace(',', '', $totalSumOfDonation);
    $totalSumOfDonation = intval($totalSumOfDonation);

    $productId = sanitize_text_field($_REQUEST['give-form-id']);
    $productName = sanitize_text_field($_REQUEST['give-form-title']);
    $productId = sanitize_text_field($_REQUEST['give-form-id']);


    $trx->addData('total', $totalSumOfDonation);

    $trx->addItems(
      array(
        'ref' => $productId,
        'title' => $productName,
        'description' => sprintf(__('Adomány az alábbi projekt számára: %s', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN), $productName),
        'amount' => '1',
        'price' => $totalSumOfDonation,
        'tax' => '0',
      )
    );


    // A pénznem
    //-----------------------------------------------------------------------------------------
    $trx->addData('currency', $currency);


    // Az adomány azonosítószáma a kereskedő rendszerben
    //-----------------------------------------------------------------------------------------
    $trx->addData('orderRef', str_replace(array('.', ':', '/'), "", @$_SERVER['SERVER_ADDR']) . @date("U", time()) . rand(1000, 9999));


    // Az adományozó neve
    //-----------------------------------------------------------------------------------------
    $customerName =  sanitize_text_field($_REQUEST['give_first']) . ' ' . sanitize_text_field($_REQUEST['give_last']);
    $trx->addData('customer', $customerName);


    // Az adományozó e-mail címe
    //-----------------------------------------------------------------------------------------
    $customerEmail = sanitize_email($_REQUEST['give_email']);
    $trx->addData('customerEmail', $customerEmail);


    // Nyelv
    // HU, EN, DE, etc.
    //-----------------------------------------------------------------------------------------
    $trx->addData('language', $language);


    // customer's registration method
    // 01: guest
    // 02: registered
    // 05: third party
    //-----------------------------------------------------------------------------------------
    $trx->addData('threeDSReqAuthMethod', '01');


    // INVOICE DATA
    //-----------------------------------------------------------------------------------------
    $companyName = isset($_REQUEST['give_company_name']) ?  sanitize_text_field($_REQUEST['give_company_name']) : '';
    $country = strtolower(sanitize_text_field($_REQUEST['billing_country']));
    $city = sanitize_text_field($_REQUEST['card_city']);
    $address = sanitize_text_field($_REQUEST['card_address']);
    $zip = sanitize_text_field($_REQUEST['card_zip']);
    $county = sanitize_text_field($_REQUEST['card_state']);

    $trx->addGroupData('invoice', 'name', $customerName);
    if ($companyName) {
      $trx->addGroupData('invoice', 'company', $companyName);
    }
    $trx->addGroupData('invoice', 'country', $country);
    $trx->addGroupData('invoice', 'state', $county);
    $trx->addGroupData('invoice', 'city', $city);
    $trx->addGroupData('invoice', 'zip', $zip);
    $trx->addGroupData('invoice', 'address', $address);


    //start transaction with card data
    //-----------------------------------------------------------------------------------------
    $trx->runAuto();


    //Challenge
    //-----------------------------------------------------------------------------------------
    $transactionBase = $trx->getTransactionBase();
    if ($trx->config['autoChallenge'] && isset($v2AutoResult['redirectUrl']) && $transactionBase['type'] === 'CIT') {
      $trx->formDetails['element'] = 'auto';
      $trx->challenge($v2AutoResult);
    }

    // get respose data
    $response = $trx->getReturnData();


    // A válasz alapján értesítések a felhasználónak
    if (isset($response['errorCodes'])) {
      $errorMessage = $response['errorCodes'][0] . ' - ' . $this->errorMessages[$response['errorCodes'][0]];

      // Tulajdonságok hozzáadása a donation objektumhoz
      give_set_payment_transaction_id($donation_id, $response['transactionId']);
      give_insert_payment_note($donation_id, $errorMessage);

      // Update donation status to `failed`.
      give_update_payment_status($donation_id, 'failed');

      $errorTitle = __('Sikertelen tranzakció.', \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN);
      $errorMessage = sprintf(__(
        'SimplePay tranzakció azonosító: %s<br>Kérjük, ellenőrizze a tranzakció során megadott adatok helyességét.
          Amennyiben minden adatot helyesen adott meg, a visszautasítás
          okának kivizsgálása érdekében kérjük, szíveskedjen kapcsolatba lépni
          kártyakibocsátó bankjával.',
        \SALSABOY990_GIVE_SIMPLEPAY_TEXT_DOMAIN
      ), $response['transactionId']);

      give_record_gateway_error(
        $errorTitle,
        $errorMessage
      );

      give_set_error('simplepay_payment_error', $errorTitle . '<br>' . $errorMessage);

      give_send_back_to_checkout();
    } else {
      // Update donation status to `complete`.
      give_update_payment_status($donation_id, 'complete');
      // Tranzakció id hozzáadása az adományhoz
      give_set_payment_transaction_id($donation_id, $response['transactionId']);
      give_insert_payment_note($donation_id, 'Sikeres SimplePay tranzakció.');

      give_send_to_success_page();
    }
  }
}
