<?php

class WePay extends base {

	/**
	 * Version number - sent in user agent string
	 */
	const VERSION = '2019-04-03';

	/**
	 * Scope fields
	 * Passed into Wepay::getAuthorizationUri as array
	 */
	const SCOPE_MANAGE_ACCOUNTS     = 'manage_accounts';     // Open and interact with accounts
	const SCOPE_VIEW_BALANCE        = 'view_balance';        // View account balances
	const SCOPE_COLLECT_PAYMENTS    = 'collect_payments';    // Create and interact with checkouts
	const SCOPE_VIEW_USER           = 'view_user';           // Get details about authenticated user
	const SCOPE_PREAPPROVE_PAYMENTS = 'preapprove_payments'; // Create and interact with preapprovals
	const SCOPE_SEND_MONEY          = 'send_money';          // For withdrawals
  const SCOPE_MANAGE_SUBSCRIPTIONS= 'manage_subscriptions';// Handle subscriptions.

	/**
	 * Application's client ID
	 */
	private static $client_id;

	/**
	 * Application's client secret
	 */
	private static $client_secret;


	/**
	 * API Version 
	 * https://www.wepay.com/developer/reference/versioning
	 */
	private static $api_version;

	/**
	 * @deprecated Use WePay::getAllScopes() instead.
	 */
	public static $all_scopes = array(
		self::SCOPE_MANAGE_ACCOUNTS,
		self::SCOPE_VIEW_BALANCE,
		self::SCOPE_COLLECT_PAYMENTS,
		self::SCOPE_PREAPPROVE_PAYMENTS,
		self::SCOPE_VIEW_USER,
		self::SCOPE_SEND_MONEY,
	);

	/**
	 * Determines whether to use WePay's staging or production servers
	 */
	private static $production = null;

	/**
	 * cURL handle
	 */
	private static $ch = NULL;

	/**
	 * Authenticated user's access token
	 */
	private $token;

	/**
	 * Pass WePay::getAllScopes() into getAuthorizationUri if your application desires full access
	 */
	public static function getAllScopes() {
		return array(
			self::SCOPE_MANAGE_ACCOUNTS,
			self::SCOPE_COLLECT_PAYMENTS,
			self::SCOPE_VIEW_USER,
			self::SCOPE_SEND_MONEY,
			self::SCOPE_PREAPPROVE_PAYMENTS,
      self::SCOPE_MANAGE_SUBSCRIPTIONS,
			self::SCOPE_VIEW_BALANCE,
		);
	}

	/**
	 * Generate URI used during oAuth authorization
	 * Redirect your user to this URI where they can grant your application
	 * permission to make API calls
	 * @link https://www.wepay.com/developer/reference/oauth2
	 * @param array  $scope             List of scope fields for which your application wants access
	 * @param string $redirect_uri      Where user goes after logging in at WePay (domain must match application settings)
	 * @param array  $options optional  user_name,user_email which will be pre-filled on login form, state to be returned in querystring of redirect_uri
	 * @return string URI to which you must redirect your user to grant access to your application
	 */
	public static function getAuthorizationUri(array $scope, $redirect_uri, array $options = array()) {
		// This does not use WePay::getDomain() because the user authentication
		// domain is different than the API call domain
		if (self::$production === null) {
			throw new RuntimeException('You must initialize the WePay SDK with WePay::useStaging() or WePay::useProduction()');
		}
		$domain = self::$production ? 'https://www.wepay.com' : 'https://stage.wepay.com';
		$uri = $domain . '/v2/oauth2/authorize?';
		$uri .= http_build_query(array(
			'client_id'    => self::$client_id,
			'redirect_uri' => $redirect_uri,
			'scope'        => implode(',', $scope),
			'state'        => empty($options['state'])      ? '' : $options['state'],
			'user_name'    => empty($options['user_name'])  ? '' : $options['user_name'],
			'user_email'   => empty($options['user_email']) ? '' : $options['user_email'],
		), '', '&');
		return $uri;
	}

	private static function getDomain() {
		if (self::$production === true) {
			return 'https://wepayapi.com/v2/';
		}
		elseif (self::$production === false) {
			return 'https://stage.wepayapi.com/v2/';
		}
		else {
			throw new RuntimeException('You must initialize the WePay SDK with WePay::useStaging() or WePay::useProduction()');
		}
	}

	/**
	 * Exchange a temporary access code for a (semi-)permanent access token
	 * @param string $code          'code' field from query string passed to your redirect_uri page
	 * @param string $redirect_uri  Where user went after logging in at WePay (must match value from getAuthorizationUri)
	 * @return StdClass|false
	 *  user_id
	 *  access_token
	 *  token_type
	 */
	public static function getToken($code, $redirect_uri) {
		$params = (array(
			'client_id'     => self::$client_id,
			'client_secret' => self::$client_secret,
			'redirect_uri'  => $redirect_uri,
			'code'          => $code,
			'state'         => '', // do not hardcode
		));
		$result = self::make_request('oauth2/token', $params);
		return $result;
	}

	/**
	 * Configure SDK to run against WePay's production servers
	 * @param string $client_id      Your application's client id
	 * @param string $client_secret  Your application's client secret
	 * @return void
	 * @throws RuntimeException
	 */
	public static function useProduction($client_id, $client_secret, $api_version = null) {
		if (self::$production !== null) {
			throw new RuntimeException('API mode has already been set.');
		}
		self::$production    = true;
		self::$client_id     = $client_id;
		self::$client_secret = $client_secret;
		self::$api_version   = $api_version;
	}

	/**
	 * Configure SDK to run against WePay's staging servers
	 * @param string $client_id      Your application's client id
	 * @param string $client_secret  Your application's client secret
	 * @return void
	 * @throws RuntimeException
	 */
	public static function useStaging($client_id, $client_secret, $api_version = null)
   {
		if (self::$production !== null) {
			throw new RuntimeException('API mode has already been set.');
		}
		self::$production    = false;
		self::$client_id     = $client_id;
		self::$client_secret = $client_secret;
		self::$api_version   = $api_version;
	}

	/**
	 * Returns the current environment.
	 * @return string "none" (not configured), "production" or "staging".
  	 */
	public static function getEnvironment() 
  {
		if(self::$production === null) {
			return 'none';
		} elseif (self::$production) {
			return 'production';
		} else {
			return 'staging';
		}
	}

	/**
	 * Create a new API session
	 * @param string $token - access_token returned from WePay::getToken
	 */
	public function __construct($token)
  {
		if ($token && !is_string($token)) {
			throw new InvalidArgumentException('$token must be a string, ' . gettype($token) . ' provided');
		}
		$this->token = $token;
	}

	/**
	 * Clean up cURL handle
	 */
	public function __destruct()
  {
		if (self::$ch) {
			curl_close(self::$ch);
			self::$ch = NULL;
		}
	}
	
	/**
	 * create the cURL request and execute it
	 */
	private static function make_request($endpoint, $values, $headers = array())
	{
		self::$ch = curl_init();
		$headers = array_merge(array("Content-Type: application/json"), $headers); // always pass the correct Content-Type header

		// send Api Version header
		if(!empty(self::$api_version)) {
			$headers[] = "Api-Version: " . self::$api_version;
		}

		curl_setopt(self::$ch, CURLOPT_USERAGENT, 'WePay v2 PHP SDK v' . self::VERSION);
		curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(self::$ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt(self::$ch, CURLOPT_TIMEOUT, 30); // 30-second timeout, adjust to taste
		curl_setopt(self::$ch, CURLOPT_POST, !empty($values)); // WePay's API is not strictly RESTful, so all requests are sent as POST unless there are no request values

		// Below should not be necessary if Curl is properly installed and up-to-date
		/*
		if (!defined('CURL_SSLVERSION_TLSv1_2')) {
			define('CURL_SSLVERSION_TLSv1_2', 6);
		}
		*/
		// Force TLS 1.2 connections
		curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt(self::$ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt(self::$ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

		$uri = self::getDomain() . $endpoint;
		curl_setopt(self::$ch, CURLOPT_URL, $uri);
		
		if (!empty($values)) {
			curl_setopt(self::$ch, CURLOPT_POSTFIELDS, json_encode($values));
		}
		
		$raw = curl_exec(self::$ch);
		if ($errno = curl_errno(self::$ch)) {
			// Set up special handling for request timeouts
			if ($errno == CURLE_OPERATION_TIMEOUTED) {
				throw new WePayServerException("Timeout occurred while trying to connect to WePay");
			}
			throw new Exception('cURL error while making API call to WePay: ' . curl_error(self::$ch), $errno);
		}
		$result = json_decode($raw);
		$httpCode = curl_getinfo(self::$ch, CURLINFO_HTTP_CODE);
		if ($httpCode >= 400) {
			if (!isset($result->error_code)) {
				throw new WePayServerException("WePay returned an error response with no error_code, please alert api@wepay.com. Original message: $result->error_description", $httpCode, $result, 0);
			}
			if ($httpCode >= 500) {
				throw new WePayServerException($result->error_description, $httpCode, $result, $result->error_code);
			}
			switch ($result->error) {
				case 'invalid_request':
					throw new WePayRequestException($result->error_description, $httpCode, $result, $result->error_code);
				case 'access_denied':
				default:
					throw new WePayPermissionException($result->error_description, $httpCode, $result, $result->error_code);
			}
		}
		
		return $result;
	}

	/**
	 * Make API calls against authenticated user
	 * @param string $endpoint - API call to make (ex. 'user', 'account/find')
	 * @param array  $values   - Associative array of values to send in API call
	 * @return StdClass
	 * @throws WePayException on failure
	 * @throws Exception on catastrophic failure (non-WePay-specific cURL errors)
	 */
	public function request($endpoint, array $values = array()) 
  {
		$headers = array();
		
		if ($this->token) { // if we have an access_token, add it to the Authorization header
			$headers[] = "Authorization: Bearer $this->token";
		}
		
		$result = self::make_request($endpoint, $values, $headers);
		
		return $result;
	}
  
  /**
   * GetTransactionDetails
   *
   * Used to read data from WePay for a given transaction
   */
  public function GetTransactionDetails($txnID) {
//    $values = array();
    
//    $values['checkout_id'] = $txnID;

//    $wepay = new WePay($this->token);

    try {
      $response = $this->request('/checkout', array('checkout_id'=> $txnID));
    }

    catch (WePayException $e) { // if the API call returns an error, get the error message for display later
      $error_checkout = $e->getMessage();
//      trigger_error ("warning: $error. ", E_USER_WARNING);
//      trigger_error ("All about e: " . print_r($e, true), E_USER_WARNING);
//      $messageStack->add_session($error, 'error');
    }

    if (isset($error_checkout) && $error_checkout) {
      try {
//        $prevalues = array();
//        $prevalues['preapproval_id'] = $values['checkout_id'];
        $response = $this->request('/preapproval', array('preapproval_id' => $txnID));
      }

      catch (WePayException $e) {
        $error_app = $e->getMessage();
//        trigger_error ("warning: $error. ", E_USER_WARNING);
      }

      if (isset($error_app) && $error_app) {
        trigger_error ("WePay checkout or approval data collect problem. Checkout: " . $error_checkout . PHP_EOL . $error_app, E_USER_WARNING);
      }
    }
      trigger_error ("All about e's response: " . print_r($response, true), E_USER_WARNING);

    return $response;
  }
  
  /**
   * RefundTransaction
   *
   * Used to refund all or part of a given transaction
   */
  public function RefundTransaction($oID, $txnID, $amount = 'Full', $note = '', $curCode = 'USD') {
    $values['checkout_id'] = $txnID;
    if ($amount != 'Full' && (float)$amount > 0) {
//      $values['REFUNDTYPE'] = 'Partial';
//      $values['CURRENCYCODE'] = $curCode;
      $values['amount'] = number_format((float)$amount, 2);
    } elseif ($amount == 'Full') {
//      $values['REFUNDTYPE'] = 'Full';
    } else {
      unset($values['checkout_id']);
    }
    if ($note != '') $values['refund_reason'] = $note;

//    $wepay = new WePay($this->token);
    
    return $this->Request('/checkout/refund', $values);
  }

  /**
   * DoAuthorization
   *
   * Used to authorize part of a previously placed order which was initiated as authType of Order
   */
  function DoAuthorization($txnID, $amount = 0, $currency = 'USD', $entity = 'Order') {
    $values['checkout_id'] = $txnID;
    $values['AMT'] = number_format($amount, 2, '.', ',');
    $values['TRANSACTIONENTITY'] = $entity;
    $values['CURRENCYCODE'] = $currency;
    return $this->Request('/checkout/capture', $values);
  }

  /**
   * DoCapture
   *
   * Used to capture part or all of a previously placed order which was only authorized
   */
  function DoCapture($txnID, $amount = 0, $currency = 'USD', $captureType = 'Complete', $invNum = '', $note = '') {
    $orig_values = $this->GetTransactionDetails($txnID);
    
/*    [preapproval_id] => 27509752
    [preapproval_uri] => https://stage.wepay.com/api/preapproval/27509752/d195edef

==    [account_id] => 1411908117
==    [short_description] => Order 1-1482255364
==    [currency] => USD
==    [amount] => 492.49
==    [fee_payer] => payer
    [state] => approved
    [redirect_uri] => https://template7.mc12345678.com/index.php?main_page=checkout_process
    [app_fee] => 0
    [period] => once
    [frequency] => 1
    [start_time] => 1482255364
    [end_time] => 1640039994
    [auto_recur] => 
    [create_time] => 1482255364
    [manage_uri] => https://stage.wepay.com/preapproval/view/27509752/d195edef
    [mode] => regular
    [payer_email] => mc12345678@mc12345678.com
    [payer_name] => Test Test*/

    
    $values = array(
                                  'account_id' => $orig_values->account_id ,       // ID of the account that you want the money to go to
                                  'amount' => /*$orig_values->amount*/ number_format((float)$amount, 2) ,       // dollar amount you want to charge the user
                                  'short_description' => $orig_values->short_description,       // a short description of what the payment is for
                                  'reference_id' => '1-' . $orig_values->account_id . '-' . gmmktime(),       // A reference id for your application to use.
//                                  'unique_id' => $orig_values->_SESSION['WEPAY_SD'],
                                  'type' => 'goods',       // the type of the payment - choose from GOODS SERVICE DONATION or PERSONAL
                                  'currency' => $orig_values->currency,
//                                  'charge_tax' => $orig_values->chargetax ,       //charge tax or not, boolean value. Tax values are set in your wepay account
/*                                  'fee' => MODULE_PAYMENT_WEPAY_CHECKOUT_FEEPAYER        // Here you put who is paying the fees you or your client. Payee = client, Payer = account holder
                                           ,*/
                                  'fee' => array('fee_payer' => $orig_values->fee_payer),
                                  'auto_release' => (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE == 'Auth Only'? false : true),

                                  'payment_method' => array(
                                    'type' => 'preapproval' ,       // put iframe here if you want the checkout to be in an iframe, regular if you want the user to be sent to WePay
                                    'preapproval' => array(
                                          'id'=>$orig_values->preapproval_id
                                          )
                                    ),
                                  'callback_uri' => str_replace('&amp;', '&', zen_href_link('wepayipn.php', '', 'SSL', true, true, true)) //, '', 'NONSSL', true, true, true)) // HTTP_SERVER.DIR_WS_CATALOG."wepayipn.php"))      // Location of the file that handles IPN requests
                                  );
    
//    $values['checkout_id'] = $txnID;
/*    $values['COMPLETETYPE'] = $captureType;
    $values['AMT'] = number_format((float)$amount, 2);
    $values['CURRENCYCODE'] = $currency;*/
//    if ($invNum != '') $values['INVNUM'] = $invNum;
//    if ($note != '') $values['NOTE'] = $note;
    return (MODULE_PAYMENT_WEPAY_CHECKOUT_TRANSACTION_MODE == 'Tipping Point' ? $this->request('/checkout/create', $values) : $this->request('/checkout/capture', $values));
  }



}

/**
 * Different problems will have different exception types so you can
 * catch and handle them differently.
 *
 * WePayServerException indicates some sort of 500-level error code and
 * was unavoidable from your perspective. You may need to re-run the
 * call, or check whether it was received (use a "find" call with your
 * reference_id and make a decision based on the response)
 *
 * WePayRequestException indicates a development error - invalid endpoint,
 * erroneous parameter, etc.
 *
 * WePayPermissionException indicates your authorization token has expired,
 * was revoked, or is lacking in scope for the call you made
 */
class WePayException extends Exception 
{
	public function __construct($description = '', $http_code = FALSE, $response = FALSE, $code = 0, $previous = NULL)
	{
		$this->response = $response;

		if (!defined('PHP_VERSION_ID')) {
			$version = explode('.', PHP_VERSION);
			define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
		}

		if (PHP_VERSION_ID < 50300) {
			parent::__construct($description, $code);
		} else {
			parent::__construct($description, $code, $previous);
		}
	}
}
class WePayRequestException extends WePayException {}
class WePayPermissionException extends WePayException {}
class WePayServerException extends WePayException {}
