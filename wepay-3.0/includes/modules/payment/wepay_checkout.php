<?php
//// Wepay Production version 3.0
//// wepay_checkout.php mod file
//// written by Alan Pinnt www.alanpinnt.com
//// rewritten by mc12345678 of http://mc12345678.com
//// 2019 ZENCART ONLY

/**
 * load the communications layer code
 */
require_once(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/wepay/wepay.php');

class wepay_checkout extends base
{
  var $code, $title, $description, $enabled;
  // class constructor
  public function __construct()
  {
    global $order;
//    include_once(zen_get_file_directory(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/', 'wepay_checkout.php', 'false'));
    $this->signature = 'wepay_checkout|wepay_checkout|3.0';
    $this->api_version = '2019-04-03';
    $this->code = 'wepay_checkout';
    $this->public_title = MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_PUBLIC_TITLE;
    $this->enabled = ((MODULE_PAYMENT_WEPAY_CHECKOUT_STATUS == 'True') ? true : false);
    if (IS_ADMIN_FLAG === true) {
      $this->description = sprintf(MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_DESCRIPTION_ADMIN, ' (rev: ' . $this->signature . '<br />api-version: ' . $this->api_version . ')');
      $this->title = MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_TITLE_ADMIN;
      $this->sort_order = defined('MODULE_PAYMENT_WEPAY_CHECKOUT_SORT_ORDER') ? MODULE_PAYMENT_WEPAY_CHECKOUT_SORT_ORDER : null;

      if (null === $this->sort_order) return false;
      if ($this->enabled) {
        if (MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION == 'Staging') $this->title .= '<strong><span class="alert"> (Staging active)</span></strong>';

        if (empty(MODULE_PAYMENT_WEPAY_CHECKOUT_ACCOUNT_ID)
            || empty(MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_ID)
            || empty(MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_SECRET)
            || empty(MODULE_PAYMENT_WEPAY_CHECKOUT_ACCESS_TOKEN)
            ) {
          $this->title .= '<span class="alert"><strong> NOT CONFIGURED YET</strong></span>';
        }
      }
    } else {
      $this->description = MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_DESCRIPTION;
      $this->title = MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_TITLE;
    }
    if ((int)MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID;
    }
    if (!empty($order) && is_object($order)) $this->update_status();
  }

  // class methods
  public function update_status()
  {
    global $order, $db;
    if (($this->enabled == true) && ((int)MODULE_PAYMENT_WEPAY_CHECKOUT_ZONE > 0)) {
      $check_flag = false;
      $check = $db->Execute("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = " . (int)MODULE_PAYMENT_WEPAY_CHECKOUT_ZONE . " AND zone_country_id = " . (int)$order->delivery['country']['id'] . " ORDER BY zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        }
        elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }
      if (!$check_flag) {
        $this->enabled = false;
        $this->zcLog('update_status', 'Module disabled due to zone restriction. Billing address is not within the Payment Zone selected in the module settings.');
      }
    }
    // other status checks?
    if ($this->enabled) {
      // other checks here
    }
  }

  public function javascript_validation()
  {
    return false;
  }

  public function selection()
  {
    return array('id' => $this->code , 'module' => $this->public_title);
  }

  public function pre_confirmation_check()
  {
    global $insert_id, $customer_id, $order, $currency, $order_id, $checkout, $db;

    require_once(DIR_WS_CLASSES . 'order_total.php');
    $order_total_modules = new order_total;
    $order_total_modules->pre_confirmation_check();
    $order_total_modules->process();
    
    $orderamount = zen_round($order->info['total'], 2); // @TODO: rounding accuracy should only deal with associated currency
    //$orderamount = preg_replace('/[\$,]/', '', $orderamount);
    $chargetax = '0';
    if (MODULE_PAYMENT_WEPAY_CHECKOUT_CHARGETAX == 'Yes') {
        $chargetax = '1';
    }
    
    $account_id = MODULE_PAYMENT_WEPAY_CHECKOUT_ACCOUNT_ID;
    $state_abbr = $db->Execute("select zone_code from " . TABLE_ZONES . " where zone_name = '" . $order->billing['state'] . "'");
    $arr = array('name' => $order->billing['firstname'].' '.$order->billing['lastname'], 
                  'phone_number' => $order->customer['telephone'], 
                  'email' => (MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION == 'Production' ? $order->customer['email_address'] : /* @TODO Provide some sort of default/alternate for non-production work */SEND_EXTRA_ORDER_EMAILS_TO ),
                  'address' => array(
                      'address1' => $order->billing['street_address'],
                      'city' => $order->billing['city'],
                      'region' => $state_abbr->fields['zone_code'],
                      'postal_code' => $order->billing['postcode'], 
                      'country'=> $order->billing['country']['iso_code_2']
                    )
                 );

    global $doWePay;
    $doWePay = $this->wepay_init();

    try {
      if (empty($_SESSION['customer_id'])) {
        $error = true;
        throw new WePayException('Not able to accept this payment.');
      }
      $_SESSION['WEPAY_SD'] = (int)$_SESSION['customer_id'] . '-' . time();
      if (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE == 'Final Sale') {
      
      $checkout = $doWePay->request('/checkout/create', array(
                                  'account_id' => $account_id,       // ID of the account that you want the money to go to
                                  'amount' => $orderamount,       // dollar amount you want to charge the user
                                  'short_description' => "Order " . $_SESSION['WEPAY_SD'],       // a short description of what the payment is for
                                  'reference_id' => $_SESSION['WEPAY_SD'],       // A reference id for your application to use.
//                                  'unique_id' => $_SESSION['WEPAY_SD'],
                                  'type' => "goods",       // the type of the payment - choose from GOODS, SERVICE, DONATION, EVENT, or PERSONAL
                                  'currency' => $order->info['currency'],
//                                  'charge_tax' => $chargetax,       //charge tax or not, boolean value. Tax values are set in your wepay account
                                  'fee' => array(
                                          'fee_payer'=> strtolower(MODULE_PAYMENT_WEPAY_CHECKOUT_FEEPAYER),
                                                ),        // Here you put who is paying the fees you or your client. Payee = client, Payer = account holder
                                  'auto_release' => (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE == 'Final Sale'? true : false),

                                  'hosted_checkout' => array(
                                      'mode' => "regular",       // put iframe here if you want the checkout to be in an iframe, regular if you want the user to be sent to WePay
                                      'prefill_info' => $arr,
                                      'redirect_uri' => str_replace('&amp;', '&', zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true)),       // The page where you want clients to go after the transaction is completed.
                                                      ),
                                  'callback_uri' => str_replace('&amp;', '&', zen_href_link('wepayipn.php', '', 'SSL', true, true, true)) //, '', 'NONSSL', true, true, true)) // HTTP_SERVER.DIR_WS_CATALOG."wepayipn.php"))      // Location of the file that handles IPN requests
                                  ));
      } elseif (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE == 'Tipping Point') {
        $checkout = $doWePay->request('preapproval/create', array(
                                    'account_id' => $account_id ,       // ID of the account that you want the money to go to
                                    'period'     => 'once',
                                    'amount' => $orderamount,       // dollar amount you want to charge the user
                                    'mode' => "regular" ,       // put iframe here if you want the checkout to be in an iframe, regular if you want the user to be sent to WePay
                                    'short_description' => "Order " . $_SESSION['WEPAY_SD'],       // a short description of what the payment is for
                                    'fee_payer' => MODULE_PAYMENT_WEPAY_CHECKOUT_FEEPAYER,
                                    'prefill_info' => $arr,
                                    'callback_uri' => str_replace('&amp;', '&', zen_href_link('wepayipn.php', '', 'SSL', true, true, true)), //, '', 'NONSSL', true, true, true)) // HTTP_SERVER.DIR_WS_CATALOG."wepayipn.php"))      // Location of the file that handles IPN requests
                                    'redirect_uri' => str_replace('&amp;', '&', zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true))       // The page where you want clients to go after the transaction is completed.
                    ));
      } else {
        // To provide authorization style action upon authorization to be able to develop.
      }
//      trigger_error("wepay: " . print_r($checkout->hosted_checkout->checkout_uri, true), E_USER_WARNING);
//    trigger_error("checkout: " . print_r($checkout, true), E_USER_WARNING);
      $this->payment_time = (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE != 'Tipping Point' ? $checkout->create_time : gmmktime());
    }
    catch (WePayException $e) { // if the API call returns an error, get the error message for display later
      $error = $e->getMessage();
      trigger_error("checkout error: $error. ", E_USER_WARNING);
    }

    if (!empty($error) && !zen_not_null($error)) {
      // Handle error which is to redirect.
      unset($_SESSION['payment']);
      zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT,'payment_error=' . $this->code, 'SSL'));
      exit;
    }

    $_SESSION['WEPAY_CHECKOUT'] = $checkout;
    if (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE == 'Final Sale') {
      header("Location: " . $checkout->hosted_checkout->checkout_uri);
    } elseif (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE == 'Tipping Point') {
      header("Location: " . $checkout->preapproval_uri);
    } else {
    }
  }

  public function confirmation()
  {
    return false;
  }

  public function process_button()
  {
    return false;
  }

  public function before_process()
  {
    return false;
  }

  public function after_process()
  {
    global $insert_id, $cartID, $order, $db;

    $comment_data = 'Reference ID: ' . $_SESSION['WEPAY_SD'] . PHP_EOL;
    unset($_SESSION['WEPAY_SD']);
    $comment_data .= 'Transaction ID: ' . (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE != 'Tipping Point' ? $_SESSION['WEPAY_CHECKOUT']->checkout_id : $_SESSION['WEPAY_CHECKOUT']->preapproval_id). PHP_EOL;
    $comment_data .= 'Payment Type: ' . MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_TITLE . PHP_EOL;
    $comment_data .= 'Timestamp: ' . $this->payment_time . PHP_EOL;
    
    $order_status_id = (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE != 'Tipping Point' ? MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID : MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_PENDING_STATUS_ID);
    
    $order_status_query = "select orders_status_name
                         from " . TABLE_ORDERS_STATUS . "
                         where orders_status_id = :order_status_id:
                         and language_id = :language_id:";
    $order_status_query = $db->bindVars($order_status_query, ':order_status_id:', $order_status_id, 'integer');
    $order_status_query = $db->bindVars($order_status_query, ':language_id:', $_SESSION['languages_id'], 'integer');
        
    $order_status = $db->Execute($order_status_query);
    $order_status_name = $order_status->fields['orders_status_name'];

    $comment_data .= 'Payment Status: ' . $order_status_name . ' (' . MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE . ')' . PHP_EOL;

    $orderamount = (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE == 'Final Sale' ? (float)$_SESSION['WEPAY_CHECKOUT']->gross : zen_round($order->info['total'], 2)); /* @TODO rounding again */

    $comment_data .= 'Amount: ' . $orderamount . PHP_EOL;

    $sql_data_array = array('orders_id' => $insert_id , 'orders_status_id' => (int)MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID , 'date_added' => 'now()' , 'customer_notified' => '0' , 'comments' => $comment_data);
    zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

    // store the WePal order meta data -- used for later matching and back-end processing activities
    $wepay_order = array('order_id' => $insert_id,
                         'txn_type' => $this->transactiontype,
                         'module_name' => $this->code,
                         'module_mode' => MODULE_PAYMENT_WEPAY_CHECKOUT_MODULE_MODE,
                         'reason_code' => $this->reasoncode,
                         'payment_type' => $this->payment_type,
                         'payment_status' => $this->payment_status,
                         'pending_reason' => $this->pendingreason,
                         'invoice' => urldecode($_SESSION['paypal_ec_token'] . $this->responsedata['PPREF']),
                         'first_name' => $_SESSION['WEPAY_CHECKOUT']->payer->name,
                         'last_name' => $_SESSION['paypal_ec_payer_info']['payer_lastname'],
                         'payer_business_name' => $_SESSION['paypal_ec_payer_info']['payer_business'],
                         'address_name' => $_SESSION['paypal_ec_payer_info']['ship_name'],
                         'address_street' => $_SESSION['paypal_ec_payer_info']['ship_street_1'],
                         'address_city' => $_SESSION['paypal_ec_payer_info']['ship_city'],
                         'address_state' => $_SESSION['paypal_ec_payer_info']['ship_state'],
                         'address_zip' => $_SESSION['paypal_ec_payer_info']['ship_postal_code'],
                         'address_country' => $_SESSION['paypal_ec_payer_info']['ship_country'],
                         'address_status' => $_SESSION['paypal_ec_payer_info']['ship_address_status'],
                         'payer_email' => $_SESSION['WEPAY_CHECKOUT']->payer->email,
                         'payer_id' => $_SESSION['paypal_ec_payer_id'],
                         'payer_status' => $_SESSION['paypal_ec_payer_info']['payer_status'],
                         'payment_date' => trim(preg_replace('/[^0-9-:]/', ' ', $this->payment_time)),
                         'business' => '',
                         'receiver_email' => (substr(MODULE_PAYMENT_PAYPALWPP_MODULE_MODE,0,7) == 'Payflow' ? MODULE_PAYMENT_PAYPALWPP_PFVENDOR : str_replace('_api1', '', MODULE_PAYMENT_PAYPALWPP_APIUSERNAME)),
                         'receiver_id' => '',
                         'txn_id' => (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE != 'Tipping Point' ? $_SESSION['WEPAY_CHECKOUT']->checkout_id : $_SESSION['WEPAY_CHECKOUT']->preapproval_id),
                         'parent_txn_id' => '',
                         'num_cart_items' => (float)$this->numitems,
                         'mc_gross' => (float)$_SESSION['WEPAY_CHECKOUT']->gross,
                         'mc_fee' => (float)urldecode($_SESSION['WEPAY_CHECKOUT']->fee->processing_fee),
                         'mc_currency' => $_SESSION['WEPAY_CHECKOUT']->currency,
                         'settle_amount' => (float)urldecode($this->responsedata['PAYMENTINFO_0_SETTLEAMT']),
                         'settle_currency' => $this->responsedata['PAYMENTINFO_0_CURRENCYCODE'],
                         'exchange_rate' => (urldecode($this->responsedata['PAYMENTINFO_0_EXCHANGERATE']) > 0 ? urldecode($this->responsedata['PAYMENTINFO_0_EXCHANGERATE']) : 1.0),
                         'notify_version' => '0',
                         'verify_sign' =>'',
                         'date_added' => 'now()',
                         'memo' => (!empty($this->fmfErrors)? 'FMF Details ' . print_r($this->fmfErrors, TRUE) : '{Record generated by payment module}'),
                        );
    zen_db_perform(TABLE_PAYPAL, $wepay_order);

    return false;
  }
  /**
    * Build admin-page components
    *
    * @param int $zf_order_id
    * @return string
    */
  public function admin_notification($zf_order_id) {
    if (!defined('MODULE_PAYMENT_WEPAY_CHECKOUT_STATUS')) return '';
    global $db;
    $module = $this->code;
    $output = '';
    $response = $this->_GetTransactionDetails($zf_order_id);
    //trigger_error("response: " . print_r($response, true), E_USER_WARNING);

    $sql = "SELECT * from " . TABLE_PAYPAL . " WHERE order_id = :orderID
            AND parent_txn_id = '' AND order_id > 0
            ORDER BY paypal_ipn_id DESC LIMIT 1";
    $sql = $db->bindVars($sql, ':orderID', $zf_order_id, 'integer');
    $ipn = $db->Execute($sql);
//    trigger_error("ipn: " . print_r($ipn, true), E_USER_WARNING);
    if ($ipn->EOF) {
      $ipn = new stdClass;
      $ipn->fields = array();
    }
    if (file_exists(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/wepay/wepay_checkout_admin_notification.php')) require(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/wepay/wepay_checkout_admin_notification.php');
    return $output;
  }
  /**
   * Used to read details of an existing transaction.  FOR FUTURE USE.
   */
  public function _GetTransactionDetails($oID) {
    if ($oID == '' || $oID < 1) return FALSE;
    global $db, $messageStack, $doWePay;

    $doWePay = $this->wepay_init();


    // look up history on this order from WePay table
    $sql = "select * from " . TABLE_PAYPAL . " where order_id = :orderID order by last_modified DESC, date_added DESC, parent_txn_id DESC, paypal_ipn_id DESC ";
    //$sql = "SELECT 
    $sql = $db->bindVars($sql, ':orderID', $oID, 'integer');
    $zc_wpHist = $db->Execute($sql);
  //  trigger_error("zc_wpHist: " . print_r($zc_wpHist, true), E_USER_WARNING);
    if ($zc_wpHist->RecordCount() == 0) return false;
    $txnID = $zc_wpHist->fields['txn_id'];
    if ($txnID == '' || $txnID === 0) return FALSE;
    /**
     * Read data from WePay
     */
    try {
      $response = $doWePay->GetTransactionDetails($txnID); 
    }
    catch (WePayException $e) { // if the API call returns an error, get the error message for display later
      $error = $e->getMessage();
      $messageStack->add_session($error, 'error');
    }
    return $response;
  }

  /**
   * Used to authorize part of a given previously-initiated transaction.  FOR FUTURE USE.
   */
/*  function _doAuth($oID, $amt, $currency = 'USD') {
    global $db, $doWePay, $messageStack;
    $doWePay = $this->wepay_init();
    $authAmt = $amt;
    $new_order_status = (int)MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_PENDING_STATUS_ID;

    if (isset($_POST['orderauth']) && $_POST['orderauth'] == MODULE_PAYMENT_PAYPAL_ENTRY_AUTH_BUTTON_TEXT_PARTIAL) {
      $authAmt = (float)$_POST['authamt'];
      $new_order_status = MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID;
      if (isset($_POST['authconfirm']) && $_POST['authconfirm'] == 'on') {
        $proceedToAuth = true;
      } else {
        $messageStack->add_session(MODULE_PAYMENT_PAYPALWPP_TEXT_AUTH_CONFIRM_ERROR, 'error');
        $proceedToAuth = false;
      }
      if ($authAmt == 0) {
        $messageStack->add_session(MODULE_PAYMENT_PAYPALWPP_TEXT_INVALID_AUTH_AMOUNT, 'error');
        $proceedToAuth = false;
      }
    }
    // look up history on this order from PayPal table
    $sql = "select * from " . TABLE_PAYPAL . " where order_id = :orderID  AND parent_txn_id = '' ";
    $sql = $db->bindVars($sql, ':orderID', $oID, 'integer');
    $zc_ppHist = $db->Execute($sql);
    if ($zc_ppHist->RecordCount() == 0) return false;
    $txnID = $zc_ppHist->fields['txn_id'];
    *//**
     * Submit auth request to PayPal
     */
/*    if ($proceedToAuth) {
      try {
        $response = $doWePay->DoAuthorization($txnID, $authAmt, $currency);
      }
      catch (WePayException $e) { // if the API call returns an error, get the error message for display later
        $error = $e->getMessage();
        trigger_error("warning: $error. ", E_USER_WARNING);
        $messageStack->add_session($error, 'error');
      }

      //$this->zcLog("_doAuth($oID, $amt, $currency):", print_r($response, true));

//      $error = $this->_errorHandler($response, 'DoAuthorization');
      $new_order_status = ($new_order_status > 0 ? $new_order_status : 1);
      if (empty($error) && !zen_not_null($error)) {
        // Success, so save the results
        $sql_data_array = array('orders_id' => (int)$oID,
                                'orders_status_id' => (int)$new_order_status,
                                'date_added' => 'now()',
                                'comments' => 'AUTHORIZATION ADDED. Trans ID: ' . urldecode($response->checkout_id) . "\n" . ' Amount:' . urldecode($response->amount) . ' ' . $currency,
                                'customer_notified' => -1
                               );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS  . "
                      set orders_status = '" . (int)$new_order_status . "'
                      where orders_id = '" . (int)$oID . "'");
        $messageStack->add_session(sprintf(MODULE_PAYMENT_PAYPALWPP_TEXT_AUTH_INITIATED, urldecode($response->AMT)), 'success');
        return true;
      }
    }
  }*/

  /**
   * Used to void a given previously-authorized transaction.  FOR FUTURE USE.
   */
  public function _doVoid($oID, $note = '') {
    global $db, $doWePay, $messageStack;
    $new_order_status = (int)MODULE_PAYMENT_WEPAY_CHECKOUT_REFUNDED_STATUS_ID;
    $doWePay = $this->wepay_init();
    $voidNote = strip_tags(zen_db_input($_POST['voidnote']));
    $voidAuthID = trim(strip_tags(zen_db_input($_POST['voidauthid'])));
    $proceedToVoid = false;
    if (isset($_POST['ordervoid']) && $_POST['ordervoid'] == MODULE_PAYMENT_PAYPAL_ENTRY_VOID_BUTTON_TEXT_FULL) {
      if (isset($_POST['voidconfirm']) && $_POST['voidconfirm'] == 'on') {
        $proceedToVoid = true;
      } else {
        $messageStack->add_session(MODULE_PAYMENT_PAYPALWPP_TEXT_VOID_CONFIRM_ERROR, 'error');
      }
    }
    // look up history on this order from PayPal table
    $sql = "select * from " . TABLE_PAYPAL . " where order_id = :orderID:  AND parent_txn_id = '' ";
    $sql = $db->bindVars($sql, ':orderID:', $oID, 'integer');
//    $sql = $db->bindVars($sql, ':transID:', $voidAuthID, 'string');
    $zc_ppHist = $db->Execute($sql);
    if ($zc_ppHist->RecordCount() == 0) return false;
    $txnID = $zc_ppHist->fields['txn_id'];
    /**
     * Submit void request to PayPal
     */
    if ($proceedToVoid) {
      try {
        $response = $doWePay->DoVoid($voidAuthID, $voidNote);
      }
      
      catch (WePayException $e) { // if the API call returns an error, get the error message for display later
        $error = $e->getMessage();
        trigger_error("warning: $error. ", E_USER_WARNING);
        $messageStack->add_session($error, 'error');
      }
      
      //$this->zcLog("_doVoid($oID, $note):", print_r($response, true));

//      $error = $this->_errorHandler($response, 'DoVoid');
      $new_order_status = ($new_order_status > 0 ? $new_order_status : 1);
      if (empty($error) && !zen_not_null($error)) {
        // Success, so save the results
        $sql_data_array = array('orders_id' => (int)$oID,
                                'orders_status_id' => (int)$new_order_status,
                                'date_added' => 'now()',
                                'comments' => 'VOIDED. Trans ID: ' . urldecode($response->AUTHORIZATIONID). $response->PNREF . (isset($response->PPREF) ? "\nPPRef: " . $response->PPREF : '') . "\n" . $voidNote,
                                'customer_notified' => 0
                             );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS  . "
                      set orders_status = '" . (int)$new_order_status . "'
                      where orders_id = '" . (int)$oID . "'");
        $messageStack->add_session(sprintf(MODULE_PAYMENT_PAYPALWPP_TEXT_VOID_INITIATED, urldecode($response->AUTHORIZATIONID) . $response->PNREF), 'success');
        return true;
      }
    }
  }
  
  /**
   * Used to capture part or all of a given previously-authorized transaction.  FOR FUTURE USE.
   * (alt value for $captureType = 'NotComplete')
   */
  public function _doCapt($oID, $captureType = 'Complete', $amt = 0, $currency = 'USD', $note = '') {
    global $db, $doWePay, $messageStack;
//    $doWePay = $this->wepay_init();

    //@TODO: Read current order status and determine best status to set this to
    $new_order_status = (int)MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID;

    $orig_order_amount = 0;
    $doWePay = $this->wepay_init();
    $proceedToCapture = false;
    $captureNote = strip_tags(zen_db_input($_POST['captnote']));
    if (isset($_POST['captfullconfirm']) && $_POST['captfullconfirm'] == 'on') {
      $proceedToCapture = true;
    } else {
      $messageStack->add_session(MODULE_PAYMENT_PAYPALWPP_TEXT_CAPTURE_FULL_CONFIRM_ERROR, 'error');
    }
    if (isset($_POST['captfinal']) && $_POST['captfinal'] == 'on') {
      $captureType = 'Complete';
    } else {
      $captureType = 'NotComplete';
    }
    if (isset($_POST['btndocapture']) && $_POST['btndocapture'] == MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_BUTTON_TEXT_FULL) {
      $captureAmt = (float)$_POST['captamt'];
      if ($captureAmt == 0) {
        $messageStack->add_session(MODULE_PAYMENT_PAYPALWPP_TEXT_INVALID_CAPTURE_AMOUNT, 'error');
        $proceedToCapture = false;
      }
    }
    // look up history on this order from PayPal table
    $sql = "select * from " . TABLE_PAYPAL . " where order_id = :orderID:  AND parent_txn_id = '' ";
    $sql = $db->bindVars($sql, ':orderID:', $oID, 'integer');
    $zc_ppHist = $db->Execute($sql);
    if ($zc_ppHist->RecordCount() == 0) return false;
    $txnID = $zc_ppHist->fields['txn_id'];
    /**
     * Submit capture request to WePay
     */
    if ($proceedToCapture) {
      try {
        $response = $doWePay->DoCapture($txnID, $captureAmt, $currency, $captureType, '', $captureNote);
      }
      //$this->zcLog("_doCapt($oID, $captureType, $amt, $currency, $note):", print_r($response, true));

      catch (WePayException $e) { // if the API call returns an error, get the error message for display later
        $error = $e->getMessage();
        trigger_error("warning: $error. ", E_USER_WARNING);
        trigger_error("warning: _doCapt catch response: " . print_r($response, true) , E_USER_WARNING);
        $messageStack->add_session($error, 'error');
      }
//      trigger_error("warning: _doCapt response: " . print_r($response, true) , E_USER_WARNING);
//      $error = $this->_errorHandler($response, 'DoCapture');
      $new_order_status = ($new_order_status > 0 ? $new_order_status : 1);
      if (empty($error) && !zen_not_null($error)) {
        if (isset($response->PNREF)) {
          if (!isset($response->AMT)) $response->AMT = $captureAmt;
          if (!isset($response->ORDERTIME)) $response->ORDERTIME = date("M-d-Y h:i:s");
        }
        // Success, so save the results
        $sql_data_array = array('orders_id' => (int)$oID,
                                'orders_status_id' => (int)$new_order_status,
                                'date_added' => 'now()',
                                'comments' => 'FUNDS COLLECTED. Trans ID: ' . urldecode($response->checkout_id) . $response->PNREF. "\n" . ' Amount: ' . urldecode($response->amount) . ' ' . $currency . "\n" . 'Time: ' . urldecode($response->create_time) . "\n" . (isset($response->reference_id) ? 'Reference ID: ' . urldecode($response->reference_id) : 'Auth Code: ' . (isset($response->AUTHCODE) && $response->AUTHCODE != '' ? $response->AUTHCODE : $response->CORRELATIONID)) . (isset($response->PPREF) ? "\nPPRef: " . $response->PPREF : '') . "\n" . $captureNote,
                                'customer_notified' => 0
                             );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_PAYPAL . " 
                      set parent_txn_id = '" . $txnID . "',
                      txn_id = '" . urldecode($response->checkout_id) . "'
                      where order_id = '" . (int)$oID . "'");

        $db->Execute("update " . TABLE_ORDERS  . "
                      set orders_status = '" . (int)$new_order_status . "'
                      where orders_id = '" . (int)$oID . "'");
        $messageStack->add_session(sprintf(MODULE_PAYMENT_PAYPALWPP_TEXT_CAPT_INITIATED, urldecode($response->amount), urldecode($response->reference_id . (isset($response->AUTHCODE) && $response->AUTHCODE != '' ? $response->AUTHCODE : $response->CORRELATIONID) ). $response->PNREF), 'success');
        return true;
      }
    }
  }

  /**
   * Used to submit a refund for a given transaction.  FOR FUTURE USE.
   * @TODO: Add option to specify shipping/tax amounts for refund instead of just total. Ref: https://developer.paypal.com/docs/classic/release-notes/merchant/PayPal_Merchant_API_Release_Notes_119/
   */
  public function _doRefund($oID, $amount = 'Full', $note = '') {
    global $db, $doWePay, $messageStack;
    $new_order_status = (int)MODULE_PAYMENT_WEPAY_CHECKOUT_REFUNDED_STATUS_ID;
    $orig_order_amount = 0;
    $doWePay = $this->wepay_init();
    $proceedToRefund = false;
    $refundNote = strip_tags(zen_db_input($_POST['refnote']));
    if (isset($_POST['fullrefund']) && $_POST['fullrefund'] == MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_BUTTON_TEXT_FULL) {
      $refundAmt = 'Full';
      if (isset($_POST['reffullconfirm']) && $_POST['reffullconfirm'] == 'on') {
        $proceedToRefund = true;
      } else {
        $messageStack->add_session(MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_REFUND_FULL_CONFIRM_ERROR, 'error');
      }
    }
    if (isset($_POST['partialrefund']) && $_POST['partialrefund'] == MODULE_PAYMENT_PAYPAL_ENTRY_REFUND_BUTTON_TEXT_PARTIAL) {
      $refundAmt = (float)$_POST['refamt'];
      $proceedToRefund = true;
      if ($refundAmt <= 0) {
        $messageStack->add_session(MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_INVALID_REFUND_AMOUNT, 'error');
        $proceedToRefund = false;
      }
    }

    // look up history on this order from PayPal table
    $sql = "select * from " . TABLE_PAYPAL . " where order_id = :orderID  AND parent_txn_id = '' ";
    $sql = $db->bindVars($sql, ':orderID', $oID, 'integer');
    $zc_ppHist = $db->Execute($sql);
    if ($zc_ppHist->RecordCount() == 0) return false;
    $txnID = $zc_ppHist->fields['txn_id'];
    $curCode = $zc_ppHist->fields['mc_currency'];
    $PFamt = $zc_ppHist->fields['mc_gross'];
    if ($refundAmt == 'Full') $refundAmt = $PFamt;

    /**
     * Submit refund request to WePay
     */
    if ($proceedToRefund) {
      try {
        $response = $doWePay->RefundTransaction($oID, $txnID, $refundAmt, $refundNote, $curCode);
      }
      catch (WePayException $e) { // if the API call returns an error, get the error message for display later
        $error = $e->getMessage();
        trigger_error("warning: $error. ", E_USER_WARNING);
        $messageStack->add_session($error, 'error');
      }

      //$this->zcLog("_doRefund($oID, $amount, $note):", print_r($response, true));

//      $error = $this->_errorHandler($response, 'DoRefund');
      $new_order_status = ($new_order_status > 0 ? $new_order_status : 1);
      if (empty($error) && !zen_not_null($error)) {
      //  if (!isset($response->refund->amount_refunded)) $response->refund->amount_refunded = $refundAmt;
        // Success, so save the results
        $sql_data_array = array('orders_id' => $oID,
                                'orders_status_id' => (int)$new_order_status,
                                'date_added' => 'now()',
                                'comments' => 'REFUND INITIATED. Trans ID:' . $response->checkout_id . /*$response->reference_id.*/ "\n" . /*' Net Refund Amt:' . urldecode($response['NETREFUNDAMT']) . "\n" . ' Fee Refund Amt: ' . urldecode($response['FEEREFUNDAMT']) . "\n" . */' Gross Refund Amt: ' . urldecode((isset($response->refund->amount_refunded) ? $response->refund->amount_refunded : $refundAmt)) . (isset($response->PPREF) ? "\nPPRef: " . $response->PPREF : '') . "\n" . $refundNote,
                                'customer_notified' => 0
                             );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("UPDATE " . TABLE_ORDERS  . "
                      SET orders_status = " . (int)$new_order_status . "
                      WHERE orders_id = " . (int)$oID);
        $messageStack->add_session(sprintf(MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_REFUND_INITIATED, urldecode($response->refund->amount_refunded), urldecode($response->checkout_id). $response->PNREF), 'success');
        return true;
      }
    }
  }

  public function get_error()
  {
    return array('error' => 'Problem with payment using ' . $this->code . '.  Please either try again or choose an alternate payment method.'); //false;
  }

  public function wepay_init()
  {
    $access_token = MODULE_PAYMENT_WEPAY_CHECKOUT_ACCESS_TOKEN;
//    $account_id = MODULE_PAYMENT_WEPAY_CHECKOUT_ACCOUNT_ID;

    if (!defined('MODULE_PAYMENT_WEPAY_CHECKOUT_STATUS') || !defined('MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_ID') || !defined('MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_SECRET') || !defined('MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION')) {
      $doWePay = new WePay(NULL);
      return $doWePay;
    }
//    $ec_uses_gateway = (defined('MODULE_PAYMENT_PAYPALWPP_PRO20_EC_METHOD') && MODULE_PAYMENT_PAYPALWPP_PRO20_EC_METHOD == 'Payflow') ? true : false;

    if (Wepay::getEnvironment() == 'none') {
      $client_id = MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_ID;
      $client_secret = MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_SECRET;

      if (MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION == 'Staging') {
        $whattouse = 'useStaging';
      }
      elseif (MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION == 'Production') {
        $whattouse = 'useProduction';
      }
      else {
        $doWePay = new WePay(NULL);
        return $doWePay;
      }
    
      WePay::$whattouse($client_id, $client_secret);
    }
    $doWePay = new WePay($access_token);

    /*$doWePay = new paypal_curl(array('mode' => 'nvp',
                                     'user' => trim(MODULE_PAYMENT_PAYPALWPP_APIUSERNAME),
                                     'pwd' =>  trim(MODULE_PAYMENT_PAYPALWPP_APIPASSWORD),
                                     'signature' => trim(MODULE_PAYMENT_PAYPALWPP_APISIGNATURE),
                                     'version' => '124.0',
                                     'server' => MODULE_PAYMENT_PAYPALWPP_SERVER));
    $doWePay->_endpoints = array('live'    => 'https://api-3t.paypal.com/nvp',
                                  'sandbox' => 'https://api-3t.sandbox.paypal.com/nvp');*/

    // set logging options
//    $doWePay->_logDir = $this->_logDir;
//    $doWePay->_logLevel = $this->_logLevel;

    // set proxy options if configured
/*    if (CURL_PROXY_REQUIRED == 'True' && CURL_PROXY_SERVER_DETAILS != '') {
      $proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
      $doPayPal->setCurlOption(CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
      $doPayPal->setCurlOption(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
      $doPayPal->setCurlOption(CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
    }*/

    // transaction processing mode
//    $doPayPal->_trxtype = (in_array(MODULE_PAYMENT_PAYPALWPP_TRANSACTION_MODE, array('Auth Only', 'Order'))) ? 'A' : 'S';

    return $doWePay;

  }
  /**
   * Evaluate installation status of this module. Returns true if the status key is found.
   */
  public function check()
  {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_WEPAY_CHECKOUT_STATUS'");
      $this->_check = !$check_query->EOF;
    }
    return $this->_check;
  }

  public function install()
  {
    global $db;
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Wepay Module', 'MODULE_PAYMENT_WEPAY_CHECKOUT_STATUS', 'True', 'Enable the Wepay Plugin', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Production or Staging', 'MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION', 'Staging', 'Set to Staging if testing, Production for running live transactions', '6', '1', 'zen_cfg_select_option(array(\'Production\', \'Staging\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Account ID', 'MODULE_PAYMENT_WEPAY_CHECKOUT_ACCOUNT_ID', '0', 'Your Account ID from Wepay', '6', '2', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Client ID', 'MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_ID', '0', 'Your Client ID from Wepay API', '6', '3', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, set_function, use_function) values ('Client Secret', 'MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_SECRET', '0', 'Your Client Secret from Wepay API', '6', '4', now(), 'zen_cfg_password_input(', 'zen_cfg_password_display')");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, set_function, use_function) values ('Access Token', 'MODULE_PAYMENT_WEPAY_CHECKOUT_ACCESS_TOKEN', '0', 'Your Access Token from Wepay API', '6', '5', now(), '', 'zen_cfg_password_display')");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Fee Payer', 'MODULE_PAYMENT_WEPAY_CHECKOUT_FEEPAYER', 'Payee', '', '6', '6', 'zen_cfg_select_option(array(\'Payee\', \'Payer\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Charge Tax', 'MODULE_PAYMENT_WEPAY_CHECKOUT_CHARGETAX', 'No', 'Charge tax or not.', '6', '5', 'zen_cfg_select_option(array(\'Yes\', \'No\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_WEPAY_CHECKOUT_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '8', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_WEPAY_CHECKOUT_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '9', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '10', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Unpaid Order Status', 'MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_PENDING_STATUS_ID', '1', 'Set the status of unpaid orders made with this payment module to this value. <br /><strong>Recommended: Pending[1]</strong>', '6', '25', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Refund Order Status', 'MODULE_PAYMENT_WEPAY_CHECKOUT_REFUNDED_STATUS_ID', '1', 'Set the status of refunded orders to this value. <br /><strong>Recommended: Pending[1]</strong>', '6', '25', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Payment Action', 'MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE', 'Final Sale', 'How do you want to obtain payment?<br /><strong>Default: Final Sale</strong>', '6', '25', 'zen_cfg_select_option(array(\'Auth Only\', \'Tipping Point\', \'Final Sale\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Transaction Currency', 'MODULE_PAYMENT_WEPAY_CHECKOUT_CURRENCY', 'Selected Currency', 'Which currency should the order be sent to WePay as? <br />NOTE: if an unsupported currency is sent to WePay, it will be auto-converted to USD<br /><strong>Default: Selected Currency</strong>', '6', '25', 'zen_cfg_select_option(array(\'Selected Currency\', \'Only USD\', \'Only CAD\', \'Only GBP\'), ', now())");
  }

  public function keys()
  {
    return array('MODULE_PAYMENT_WEPAY_CHECKOUT_STATUS' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_ACCOUNT_ID' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_ID' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_SECRET' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_ACCESS_TOKEN' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_FEEPAYER' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_ZONE' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_SORT_ORDER' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID' , 'MODULE_PAYMENT_WEPAY_CHECKOUT_CHARGETAX', 'MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_PENDING_STATUS_ID', 'MODULE_PAYMENT_WEPAY_CHECKOUT_REFUNDED_STATUS_ID', 'MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE', 'MODULE_PAYMENT_WEPAY_CHECKOUT_CURRENCY');
  }

  public function remove()
  {
    global $db;
    $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    $this->notify('NOTIFY_PAYMENT_WEPAY_CHECKOUT_UNINSTALLED');
  }

  // format prices without currency formatting
  function format_raw($number, $currency_code = '', $currency_value = '')
  {
    global $currencies, $currency;
    if (empty($currency_code) || !$currencies->is_set($currency_code)) {
      $currency_code = $currency;
    }
    if (empty($currency_value) || !is_numeric($currency_value)) {
      $currency_value = $currencies->currencies[$currency_code]['value'];
    }
    return number_format(zen_round($number * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
  }
}

