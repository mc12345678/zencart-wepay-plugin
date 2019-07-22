<?php
//// Wepay Production version 2.0
//// wepay_checkout.php ENG Lang file
//// written by Alan Pinnt www.alanpinnt.com
//// 2012 ZENCART ONLY
//// Updated by mc12345678 www.mc12345678.com in 2019 Jul 21

  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_TITLE', 'Wepay');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_PUBLIC_TITLE', 'Credit Card (By Wepay)');
  if (IS_ADMIN_FLAG === true) {
    define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_DESCRIPTION_ADMIN', '<strong>Wepay</strong>%s<br /><img src="images/icon_popup.gif" border="0">&nbsp;<a href="https://www.wepay.com/" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit Wepay Website</a><br />
  <img src="images/icon_popup.gif" border="0">&nbsp;<a href="http://github.com/mc12345678/zencart-wepay-plugin" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit Developer Website</a><br />
  <img src="images/icon_popup.gif" border="0">&nbsp;<a href="http://wepay.com/v2/plugin/create/?plugin_post_uri='.HTTP_SERVER.''.DIR_WS_CATALOG.'index.php&plugin_name=ZenCart Plugin&plugin_homepage='.HTTP_SERVER.''.DIR_WS_CATALOG.'&plugin_redirect_uri='.HTTP_SERVER.''.DIR_WS_CATALOG.'index.php" target="_blank" style="text-decoration: underline; font-weight: bold;">Get API Credentials</a>
  </span>');
    define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_TITLE_ADMIN', 'Wepay');
  }
 define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_DESCRIPTION', '<img src="images/icon_popup.gif" border="0">&nbsp;<a href="https://www.wepay.com/" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit Wepay Website</a><br />
  <img src="images/icon_popup.gif" border="0">&nbsp;<a href="http://www.alanpinnt.com/wepay-oscommerce-plugin/" target="_blank" style="text-decoration: underline; font-weight: bold;">Visit Developer Website</a><br />
  <img src="images/icon_popup.gif" border="0">&nbsp;<a href="http://wepay.com/v2/plugin/create/?plugin_post_uri='.HTTP_SERVER.''.DIR_WS_CATALOG.'ext/modules/payment/wepay/auth.php&plugin_name=ZenCart Plugin&plugin_homepage='.HTTP_SERVER.''.DIR_WS_CATALOG.'&plugin_redirect_uri='.HTTP_SERVER.''.DIR_WS_CATALOG.'admin/modules.php?set=payment" target="_blank" style="text-decoration: underline; font-weight: bold;">Get API Credentials</a>
  </span>');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_BUTTON', 'Checkout');
/*  define('MODULE_PAYMENT_PAYPALWPP_TEXT_INVALID_REFUND_AMOUNT', 'You requested a partial refund but did not specify an amount.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_REFUND_FULL_CONFIRM_ERROR', 'You requested a full refund but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_INVALID_AUTH_AMOUNT', 'You requested an authorization but did not specify an amount.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_INVALID_CAPTURE_AMOUNT', 'You requested a capture but did not specify an amount.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_VOID_CONFIRM_CHECK', 'Confirm');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_VOID_CONFIRM_ERROR', 'You requested to void a transaction but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_AUTH_FULL_CONFIRM_CHECK', 'Confirm');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_AUTH_CONFIRM_ERROR', 'You requested an authorization but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_CAPTURE_FULL_CONFIRM_ERROR', 'You requested funds-Capture but did not check the Confirm box to verify your intent.');*/

  define('MODULE_PAYMENT_PAYPALWPP_TEXT_CAPTURE_FULL_CONFIRM_ERROR', 'You requested funds-Capture but did not check the Confirm box to verify your intent.');

  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_INVALID_REFUND_AMOUNT', 'You requested a partial refund but did not specify an amount.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_REFUND_FULL_CONFIRM_ERROR', 'You requested a full refund but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_INVALID_AUTH_AMOUNT', 'You requested an authorization but did not specify an amount.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_INVALID_CAPTURE_AMOUNT', 'You requested a capture but did not specify an amount.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_VOID_CONFIRM_CHECK', 'Confirm');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_VOID_CONFIRM_ERROR', 'You requested to void a transaction but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_AUTH_FULL_CONFIRM_CHECK', 'Confirm');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_AUTH_CONFIRM_ERROR', 'You requested an authorization but did not check the Confirm box to verify your intent.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_CAPTURE_FULL_CONFIRM_ERROR', 'You requested funds-Capture but did not check the Confirm box to verify your intent.');

/*  define('MODULE_PAYMENT_PAYPALWPP_TEXT_REFUND_INITIATED', 'PayPal refund for %s initiated. Transaction ID: %s. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_AUTH_INITIATED', 'PayPal Authorization for %s initiated. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_CAPT_INITIATED', 'PayPal Capture for %s initiated. Receipt ID: %s. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_VOID_INITIATED', 'PayPal Void request initiated. Transaction ID: %s. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_GEN_API_ERROR', 'There was an error in the attempted transaction. Please see the API Reference guide or transaction logs for detailed information.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_INVALID_ZONE_ERROR', 'We are sorry for the inconvenience; however, at the present time we are unable to use PayPal to process orders from the geographic region you selected as your PayPal address.  Please continue using normal checkout and select from the available payment methods to complete your order.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_ORDER_ALREADY_PLACED_ERROR', 'It appears that your order was submitted twice. Please check the My Account area to see the actual order details.  Please use the Contact Us form if your order does not appear here but is already paid from your PayPal account so that we may check our records and reconcile this with you.');*/

  define('MODULE_PAYMENT_PAYPALWPP_TEXT_CAPT_INITIATED', 'PayPal Capture for %s initiated. Receipt ID: %s. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');


  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_REFUND_INITIATED', 'PayPal refund for %s initiated. Transaction ID: %s. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_AUTH_INITIATED', 'PayPal Authorization for %s initiated. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_CAPT_INITIATED', 'PayPal Capture for %s initiated. Receipt ID: %s. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_VOID_INITIATED', 'PayPal Void request initiated. Transaction ID: %s. Refresh the screen to see confirmation details updated in the Order Status History/Comments section.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_GEN_API_ERROR', 'There was an error in the attempted transaction. Please see the API Reference guide or transaction logs for detailed information.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_INVALID_ZONE_ERROR', 'We are sorry for the inconvenience; however, at the present time we are unable to use PayPal to process orders from the geographic region you selected as your PayPal address.  Please continue using normal checkout and select from the available payment methods to complete your order.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_ORDER_ALREADY_PLACED_ERROR', 'It appears that your order was submitted twice. Please check the My Account area to see the actual order details.  Please use the Contact Us form if your order does not appear here but is already paid from your PayPal account so that we may check our records and reconcile this with you.');

  // These are used for displaying raw transaction details in the Admin area:
  define('MODULE_PAYMENT_PAYPAL_ENTRY_FIRST_NAME', 'First Name:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_LAST_NAME', 'Last Name:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_BUSINESS_NAME', 'Business Name:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_NAME', 'Address Name:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_STREET', 'Address Street:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_CITY', 'Address City:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_STATE', 'Address State:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_ZIP', 'Address Zip:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_COUNTRY', 'Address Country:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_EMAIL_ADDRESS', 'Payer Email:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_EBAY_ID', 'Ebay ID:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_PAYER_ID', 'Payer ID:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_PAYER_STATUS', 'Payer Status:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_ADDRESS_STATUS', 'Address Status:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_TYPE', 'Payment Type:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_STATUS', 'Payment Status:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_PENDING_REASON', 'Pending Reason:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_INVOICE', 'Invoice:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_DATE', 'Payment Date:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_CURRENCY', 'Currency:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_GROSS_AMOUNT', 'Gross Amount:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_PAYMENT_FEE', 'Payment Fee:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_EXCHANGE_RATE', 'Exchange Rate:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_CART_ITEMS', 'Cart items:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_TXN_TYPE', 'Trans. Type:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_TXN_ID', 'Trans. ID:');
  define('MODULE_PAYMENT_PAYPAL_ENTRY_PARENT_TXN_ID', 'Parent Trans. ID:');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_TITLE', '<strong>Order Refunds</strong>');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_FULL', 'If you wish to refund this order in its entirety, click here:');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_BUTTON_TEXT_FULL', 'Do Full Refund');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_BUTTON_TEXT_PARTIAL', 'Do Partial Refund');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_TEXT_FULL_OR', '<br />... or enter the partial ');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_PAYFLOW_TEXT', 'Enter the ');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_PARTIAL_TEXT', 'refund amount here and click on Partial Refund');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_SUFFIX', '*A Full refund may not be issued after a Partial refund has been applied.<br />*Multiple Partial refunds are permitted up to the remaining unrefunded balance.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_TEXT_COMMENTS', '<strong>Note to display to customer (Reason for refund):</strong>');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUND_DEFAULT_MESSAGE', 'Refunded by store administrator.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUNDED_FEE', 'MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUNDED_FEE');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_AMOUNT_COLLECTED', 'Net Collected: ');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_REFUNDED_FEE_REASON', 'Last Refund Reason: ');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_REFUND_FULL_CONFIRM_CHECK','Confirm: ');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_COMMENTS', 'System Comments: ');
  define('MODULE_PAYMENT_PAYPALWPP_ENTRY_PROTECTIONELIG', 'Protection Eligibility:');

  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_TITLE', '<strong>Capturing Authorizations</strong>');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_FULL', 'If you wish to capture all or part of the outstanding authorized amounts for this order, enter the Capture Amount and select whether this is the final capture for this order.  Check the confirm box before submitting your Capture request.<br />');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_BUTTON_TEXT_FULL', 'Do Capture');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_AMOUNT_TEXT', 'Amount to Capture:');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_FINAL_TEXT', 'Is this the final capture?');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_SUFFIX', '');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_TEXT_COMMENTS', '<strong>Note to display to customer:</strong>');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_CAPTURE_DEFAULT_MESSAGE', 'Thank you for your order.');
  define('MODULE_PAYMENT_PAYPALWPP_TEXT_CAPTURE_FULL_CONFIRM_CHECK','Confirm: ');

  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_VOID_TITLE', '<strong>Voiding Order Authorizations</strong>');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_VOID', 'If you wish to void an authorization, enter the authorization ID here, and confirm:');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_VOID_TEXT_COMMENTS', '<strong>Note to display to customer:</strong>');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_VOID_DEFAULT_MESSAGE', 'Thank you for your patronage. Please come again.');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_VOID_BUTTON_TEXT_FULL', 'Do Void');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_ENTRY_VOID_SUFFIX', '(Void Suffix)');

  define('MODULE_PAYMENT_WEPAY_CHECKOUT_TEXT_COMMENTS', 'Comments:');
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_EMAIL_PASSWORD', 'An account has automatically been created for you with the following e-mail address and password:' . "\n\n" . 'Store Account E-Mail Address: %s' . "\n" . 'Store Account Password: %s' . "\n\n");
  define('MODULE_PAYMENT_WEPAY_CHECKOUT_LANGUAGE_LOCALE', 'en_US');
  
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_NEW', 'new');
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_AUTHORIZED','authorized');
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_CAPTURED','captured');
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_RELEASED','released');
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_CANCELLED','cancelled');
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_REFUNDED','refunded');
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_CHARGED_BACK','carged back');
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_FAILED','failed');
  define('MODULE_PAYMENT_WEPAY_STATUS_CHECKOUT_EXPIRED','expired');
  define('MODULE_PAYMENT_WEPAY_STATUS_PREAPPROVAL_NEW','new');
  define('MODULE_PAYMENT_WEPAY_STATUS_PREAPPROVAL_APPROVED','approved');
  define('MODULE_PAYMENT_WEPAY_STATUS_PREAPPROVAL_EXPIRED','expired');
  define('MODULE_PAYMENT_WEPAY_STATUS_PREAPPROVAL_REVOKED','revoked');
  define('MODULE_PAYMENT_WEPAY_STATUS_PREAPPROVAL_CANCELLED','cancelled');
  define('MODULE_PAYMENT_WEPAY_STATUS_PREAPPROVAL_STOPPED','stopped');
  define('MODULE_PAYMENT_WEPAY_STATUS_PREAPPROVAL_COMPLETED','completed');
  define('MODULE_PAYMENT_WEPAY_STATUS_PREAPPROVAL_RETRYING','retrying');
?>
