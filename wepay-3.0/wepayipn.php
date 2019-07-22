<?php
//// Wepay Production version 3.0
//// wepayipn.php file
//// written by Alan Pinnt www.alanpinnt.com
//// revised by mc12345678 of http://mc12345678.com
//// 2019 ZENCART ONLY

//chdir('../../../../');
require 'includes/application_top.php';
require DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/wepay/wepay.php';

if (!defined('MODULE_PAYMENT_WEPAY_CHECKOUT_STATUS') || (MODULE_PAYMENT_WEPAY_CHECKOUT_STATUS  != 'True')) {
    exit;
}

if (!empty($_POST['checkout_id'])) {
    $thecheckoutid = $_POST['checkout_id'];
} else {
    exit; /*$thecheckoutid = $_GET['checkout_id'];*/
}

$client_id = MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_ID;
$client_secret = MODULE_PAYMENT_WEPAY_CHECKOUT_CLIENT_SECRET;
$access_token = MODULE_PAYMENT_WEPAY_CHECKOUT_ACCESS_TOKEN;
$account_id = MODULE_PAYMENT_WEPAY_CHECKOUT_ACCOUNT_ID;

if (MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION == 'Staging') {
    $whattouse = 'useStaging';
} elseif (MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION == 'Production') {
    $whattouse = 'useProduction';
} else {
    trigger_error('MODULE_PAYMENT_WEPAY_CHECKOUT_PRODUCTION setting not recognized', E_USER_ERROR);
    exit;
}

Wepay::$whattouse($client_id, $client_secret);
$wepay = new WePay($access_token);

try {
    $checkout = $wepay->request('checkout', array(
                                  'checkout_id' => $thecheckoutid,
                                                 )
                               );
} catch (WePayException $e) {
    $error = $e->getMessage();
}

if (empty($error) && $checkout->state == "captured") {
//  global $db;

    $sql = "SELECT orders_id FROM ".TABLE_ORDERS_STATUS_HISTORY." WHERE comments = :reference_id:";
    $sql = $db->bindVars($sql, ':reference_id:', $checkout->reference_id, 'string');
    $query_id = $db->Execute($sql);

    if (!$query_id->EOF) {
        $row_id = $query_id->fields;

        $sql_data_array = array('orders_id' => (int)$row_id['orders_id'],
                               'orders_status_id' => (int)MODULE_PAYMENT_WEPAY_CHECKOUT_ORDER_STATUS_ID, 
                               'date_added' => 'now()',
                               'customer_notified' => '0',
                               'comments' => 'Payment captured - Wepay',
                               );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS . " set orders_status = " . $sql_data_array['orders_status_id'] . ",
                                                      last_modified = " . $sql_data_array['date_added'] . " 
                                                      where orders_id = '" . $sql_data_array['orders_id'] . "'");
    }
}

require 'includes/application_bottom.php';
