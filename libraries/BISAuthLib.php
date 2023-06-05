<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library to Connect to BIS Services
 */
class BISAuthLib
{
	private $_ci; // Code igniter instance
	private $authentication;

	const USERNAME = 'username';
	const PASSWORD = 'password';
	const AUTHENTICATION_PATH = 'oauth/token';
	const AUTHENTICATION_GRANT_TYPE_NAME = 'grant_type';
	const AUTHENTICATION_GRANT_TYPE_VALUE = 'password';
	const AUTHORIZATION_HEADER_NAME = 'Authorization';
	const AUTHORIZATION_HEADER_PREFIX = 'Basic';
	const TOKEN_EXPIRATION_OFFSET = 5; // offset to make token expire earlier to avoid errors

	/**
	 * Object initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance

		$this->_ci->config->load('extensions/FHC-Core-BIS/BISClient');

		$this->_setConnection(); // loads the configurations
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * get OAuth Token
	 */
	public function getToken()
	{
		if ($this->_tokenIsExpired())
		{
			$this->_authenticate();
		}

		if (isset($this->authentication->access_token))
			return $this->authentication->access_token;

		return null;
	}

	/**
	 * Checks if the Token is Expired
	 * @return boolean true if expired, false if valid.
	 */
	private function _tokenIsExpired()
	{
		if (!isset($this->authentication))
			return true;

		$dtnow = new DateTime();
		if ($this->authentication->DateTimeExpires < $dtnow)
			return true;
		else
			return false;
	}

	/**
	 * Retrieves active connection from config.
	 * @return object the connection
	 */
	private function _setConnection()
	{
		$activeConnectionName = $this->_ci->config->item(BISClientLib::ACTIVE_CONNECTION);
		$connectionsArray = $this->_ci->config->item(BISClientLib::CONNECTIONS);

		$this->_connectionsArray = $connectionsArray[$activeConnectionName];
	}

	/**
	 * Retrieves token service url needed for authentication.
	 * @return string
	 */
	private function _getTokenServiceURL()
	{
		return sprintf(
			BISClientLib::URI_TEMPLATE,
			$this->_connectionsArray[BISClientLib::PROTOCOL],
			$this->_connectionsArray[BISClientLib::HOST],
			$this->_connectionsArray[BISClientLib::PATH],
			self::AUTHENTICATION_PATH
		);
	}

	/**
	 * Performs a remote web service authentication.
	 */
	private function _authenticate()
	{
		$curl = curl_init();

		$uri = $this->_getTokenServiceURL();

		$authorizationHeaderValue = self::AUTHORIZATION_HEADER_PREFIX.' '.base64_encode(
			$this->_connectionsArray[self::USERNAME].':'.$this->_connectionsArray[self::PASSWORD]
		);

		// HTTP POST call for to retrieve token
		$response = \Httpful\Request::post($uri)
			->expectsJson() // dangerous expectations
			->addHeader(self::AUTHENTICATION_GRANT_TYPE_NAME, self::AUTHENTICATION_GRANT_TYPE_VALUE)
			->addHeader(self::AUTHORIZATION_HEADER_NAME, $authorizationHeaderValue)
			->body(array(self::AUTHENTICATION_GRANT_TYPE_NAME => self::AUTHENTICATION_GRANT_TYPE_VALUE)) // grant type must be sent in body
			->sendsForm() // must be sent as URLencoded form data
			->send();

		if (isset($response->code) && $response->code == '200')
		{
			/* Example Response:
			[body] => stdClass Object
			(
				[access_token] => 1234abcd
				[token_type] => bearer
				[expires_in] => 1799
				[scope] =>
				[.issued] => Sun, 05 Feb 2023 21:09:19 GMT
				[.expires] => Sun, 05 Feb 2023 21:39:19 GMT
			)*/

			if (isset($response->body->access_token))
			{
				$this->authentication = $response->body;

				// Calculate Expire Date
				// make the date expire a bit earlier to avoid "invalid token" error
				$expires_in_seconds = $this->authentication->expires_in > self::TOKEN_EXPIRATION_OFFSET
					? $this->authentication->expires_in - self::TOKEN_EXPIRATION_OFFSET
					: $this->authentication->expires_in;
				$ttl = new DateTime();
				$ttl->add(new DateInterval('PT'.$expires_in_seconds.'S'));
				$this->authentication->DateTimeExpires = $ttl;
			}
			else
			{
				return error('No token data returned');
			}

			return success();
		}
		else
		{
			return error('Authentication failed');
		}
	}
}
