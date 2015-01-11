<?php
class PayPalDirectTransaction {
	private $api_config;
	private $transaction_params;
	private $sandbox_mode;
	private $request_string;


	/**
	 * IMPORTANT: It is assumed that all of the required data listed below exists and has already been sanitized and validated.
	 *		  Any missing or invalid data will result in a generic error being thrown and displayed to the user. The specifics
	 *		  of each error may be found in paypal_errors.log.
	 *
	 *
	 * @param Array $config - Contains all of the authentication details to connect with PayPal's API
	 *
	 *	REQUIRED INFORMATION: The following indexes MUST exist withing the $config parameter.
	 *	-----------------------------------------------------------------------------------------------------------------------------------------
	 *
	 *	'USER' 			=> '',			//PayPal Account Username
	 *	'PWD' 			=> '',			//PayPal Account Password
	 *	'SIGNATURE' 		=> '',			//API authorization code for this application
	 *	'VERSION' 		=> '',			//The version of PayPal's API being used to handle the transaction (defaults to 119.0)
	 *
	 *
	 * @param Array $transaction - Contains all of the transaction details related to the customer
	 *
	 *	REQUIRED INFORMATION: The following indexes MUST exist withing the $transaction parameter.
	 *	-----------------------------------------------------------------------------------------------------------------------------------------
	 *	'FIRSTNAME'		=> '',			//Customer's first name
	 *	'LASTNAME'		=> '',			//Customer's last name
	 *	'STREET'		=> '',			//Customer's  street address
  	 *	'CITY'			=> '',			//Customer's city of residence
	 *	'STATE'			=> '',			//Customer's state of residence
	 *	'ZIP'			=> ''			//Customer's zip code
	 *	'COUNTRYCODE'	=> '',			//Code of the customer's country (OPTIONAL: defaults to 'US')
	 *	'IPADDRESS' 		=> '',			//Customer's IP Address (OPTIONAL: defaults to $_SERVER['REMOTE_ADDR'])
	 *	'CREDITCARDTYPE'	 => '',			//Customer's card type (Mastercard, Visa, etc)
	 *	'ACCT'			=> '',			//The 16 digit number on the customer's credit card
	 *	'EXPDATE'		=> '',			//Card's experation date
	 *	'CVV2'			=> ''			//Credit card's  (usually 3 digit) CVV code
	 *	'AMT'			=> '',			//The amount of funds being transfered
	 *	'CURRENCYCODE'	=> '',			//The type of currency being transfered (OPTIONAL: defaults to USD)
	 *	'DESC'			=> '',			//A brief description of the type of transaction being handled
	 *
	 */
	public function __construct(Array $config, Array $transaction, $sandbox_mode = FALSE) {
		$this->api_config = $config;
		$this->transaction_params = $transaction;
		$this->sandbox_mode = $sandbox_mode;
	}


	private function setDefaults() {
		$this->api_config['METHOD'] = 'DoDirectPayment';
		$this->api_config['PAYMENTACTION'] = 'Sale';

		//set default country code if one does not already exist
		if(!isset($this->transaction_params['COUNTRYCODE']) || 
		   strlen(trim($this->transaction_params['COUNTRYCODE'])) == 0) {
			$this->transaction_params['COUNTRYCODE'] = 'US';
		}

		//set default currency code if one does not already exist
		if(!isset($this->transaction_params['CURRENCYCODE']) || strlen(trim($this->transaction_params['CURRENCYCODE'])) == 0) {
			$this->transaction_params['CURRENCYCODE'] = 'USD';
		}

		//set default IP Address if one does not already exist
		if(!isset($this->transaction_params['IPADDRESS']) || strlen(trim($this->transaction_params['IPADDRESS'])) == 0) {
			$this->transaction_param['IPADDRESS'] = $_SERVER['REMOTE_ADDR'];
		}
	}


	private function prepTransaction() {
		$this->setDefaults();
		$this->api_config['ENDPOINT'] = ($this->sandbox_mode) ?
							'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';

		foreach($this->api_config as $option => $value) {
			$this->request_string .= '&' . $option . '=' . urlencode($value);
		}

		foreach($this->transaction_params as $option => $value) {
			$this->request_string .= '&' . $option . '=' . urlencode($value);
		}
	}

	public function makeTransaction() {
		$this->prepTransaction();

		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_VERBOSE, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_TIMEOUT, 30);
			curl_setopt($curl, CURLOPT_URL, ($this->api_config['ENDPOINT']));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $this->request_string);

			$result = curl_exec($curl);  
			curl_close($curl);
		} catch(Exception $e) {
			return false;
		}

		return $result;
	}
}