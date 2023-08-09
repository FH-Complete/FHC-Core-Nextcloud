<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once('vendor/nategood/httpful/bootstrap.php');

/**
 */
class NextcloudClientLib
{
	const HTTP_GET_METHOD = 'GET'; // http get method name
	const HTTP_POST_METHOD = 'POST'; // http post method name
	const HTTP_DELETE_METHOD = 'DELETE'; // http deletemethod name
	const URI_TEMPLATE = '%s://%s/%s/%s'; // URI format

	private $_configArray;		// contains the connection parameters configuration array

	private $_wsFunction;			//

	private $_httpMethod;			// http method used to call this server
	private $_callParametersArray;	// contains the parameters to give to the remote web service

	private $_callResult; 			// contains the result of the called remote web service

	private $_error;				// true if an error occurred
	private $_errorMessage;			// contains the error message

	private $_hasData;				// indicates if there are data in the response or not

	private $_errors = array(
		'MISSING_REQUIRED_PARAMETERS' => array('code' => 1, 'message' => 'Missing parameters'),
		'WRONG_WS_PARAMETERS' => array('code' => 2, 'message' => 'Wrong Webservice parameters'),
		'CONNECTION_ERROR' => array('code' => 3, 'message' => 'Connection error'),
		'XML_PARSE_ERROR' => array('code' => 4, 'message' => 'XML parse error')
	);

	/**
	 * Object initialization
	 */
	public function __construct()
	{
		$this->ci =& get_instance();
		$this->_setPropertiesDefault();
		$this->_loadConfig();
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Performs a call to a remote web service
	 */
	public function call($wsFunction, $httpMethod = NextcloudClientLib::HTTP_GET_METHOD, $callParametersArray = array())
	{
		$this->_error = false;

		if ($wsFunction != null && trim($wsFunction) != '')
		{
			$this->_wsFunction = $wsFunction;
		}
		else
		{
			$this->_logError('MISSING_REQUIRED_PARAMETERS');
		}

		if ($httpMethod != null
			&& ($httpMethod == NextcloudClientLib::HTTP_GET_METHOD || $httpMethod == NextcloudClientLib::HTTP_POST_METHOD || $httpMethod == NextcloudClientLib::HTTP_DELETE_METHOD))
		{
			$this->_httpMethod = $httpMethod;
		}
		else
		{
			$this->_logError('WRONG_WS_PARAMETERS');
		}

		if (is_array($callParametersArray))
		{
			$this->_callParametersArray = $callParametersArray;
		}
		else
		{
			$this->_logError('WRONG_WS_PARAMETERS');
		}

		if ($this->isError()) return null; //

		return $this->_callRemoteWS($this->_generateURI()); // perform a remote ws call with the given uri
	}

	/**
	 * Returns the error message stored in property _errorMessage
	 */
	public function getError()
	{
		return $this->_errorMessage;
	}

	/**
	 * Returns true if an error occurred, otherwise false
	 */
	public function isError()
	{
		return $this->_error;
	}

	/**
	 * Returns false if an error occurred, otherwise true
	 */
	public function isSuccess()
	{
		return !$this->isError();
	}

	/**
	 * Returns true if the response contains data, otherwise false
	 */
	public function hasData()
	{
		return $this->_hasData;
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Initialization of the properties of this object
	 */
	private function _setPropertiesDefault()
	{
		$this->_configArray = null;

		$this->_wsFunction = null;

		$this->_httpMethod = null;

		$this->_callParametersArray = array();

		$this->_callResult = null;

		$this->_error = false;

		$this->_errorMessage = '';

		$this->_hasData = false;
	}

	/**
	 * Loads the config file present in the config directory and sets the properties
	 */
	private function _loadConfig()
	{
		$this->ci->config->load('extensions/FHC-Core-Nextcloud/config');
		$this->_configArray = $this->ci->config->item('FHC-Core-Nextcloud');
	}

	/**
	 * Returns true if the HTTP method used to call this server is GET
	 */
	private function _isGET()
	{
		return $this->_httpMethod == NextcloudClientLib::HTTP_GET_METHOD;
	}

	/**
	 * Returns true if the HTTP method used to call this server is POST
	 */
	private function _isPOST()
	{
		return $this->_httpMethod == NextcloudClientLib::HTTP_POST_METHOD;
	}

	/**
	 * Returns true if the HTTP method used to call this server is DELETE
	 */
	private function _isDELETE()
	{
		return $this->_httpMethod == NextcloudClientLib::HTTP_DELETE_METHOD;
	}

	/**
	 * Generate the URI to call the remote web service
	 */
	private function _generateURI()
	{
		$uri = sprintf(
			NextcloudClientLib::URI_TEMPLATE,
			$this->_configArray['protocol'],
			$this->_configArray['host'],
			$this->_configArray['path'],
			$this->_wsFunction
		);

		// If the call was performed using a HTTP GET then append the query string to the URI
		if ($this->_isGET())
		{
			$queryString = '';

			// Create the query string
			foreach ($this->_callParametersArray as $name => $value)
			{
				if (is_array($value)) // if is an array
				{
					foreach ($value as $key => $val)
					{
						$queryString .= '&'.$name.'[]='.$val;
					}
				}
				else // otherwise
				{
					$queryString .= '&'.$name.'='.$value;
				}
			}

			$uri .= $queryString;
		}

		return $uri;
	}

	/**
	 * Performs a remote web service call with the given uri and returns the result after having checked it
	 */
	private function _callRemoteWS($uri)
	{
		$response = null;

		try
		{
			if ($this->_isGET()) // if the call was performed using a HTTP GET...
			{
				$response = $this->_callGET($uri); // ...calls the remote web service with the HTTP GET method
			}
			elseif ($this->_isDELETE())
			{
				$response = $this->_callDELETE($uri); // ...calls the remote web service with the HTTP DELETE method
			}
			else // else if the call was performed using a HTTP POST...
			{
				$response = $this->_callPOST($uri); // ...calls the remote web service with the HTTP GET method
			}

			// Checks the response of the remote web service and handles possible errors
			// Eventually here is also called a hook, so the data could have been manipulated
			$response = $this->_checkResponse($response);
		}
		catch (\Httpful\Exception\ConnectionErrorException $cee) // connection error
		{
			//echo $this->_errors['CONNECTION_ERROR']['message'];
			$response = null;
			$this->_logError('CONNECTION_ERROR');
		}
			// otherwise another error has occurred, most likely the result of the
			// remote web service is not xml so a parse error is raised
		catch (Exception $e)
		{
			//echo $this->_errors['WRONG_WS_PARAMETERS']['message'];
			$response = null;
			$this->_logError('WRONG_WS_PARAMETERS');
		}

		return $response;
	}

	/**
	 * Performs a remote call using the GET HTTP method
	 * NOTE: parameters in a HTTP GET call are placed into the URI
	 */
	private function _callGET($uri)
	{
		return \Httpful\Request::get($uri)
			->expectsXml() // parse from xml
			->addHeader('OCS-APIRequest', 'true')
			->authenticateWithBasic($this->_configArray['username'], $this->_configArray['password'])
			->send();
    }

	/**
	 * Performs a remote call using the POST HTTP method
	 */
	private function _callPOST($uri)
	{
		return \Httpful\Request::post($uri)
			->expectsXml() // parse response as xml
			->addHeader('OCS-APIRequest', 'true')
			->authenticateWithBasic($this->_configArray['username'], $this->_configArray['password'])
			->body(http_build_query($this->_callParametersArray)) // post parameters
			->sendsType(\Httpful\Mime::FORM)
			->send();
    }

	/**
	 * Performs a remote call using the DELETE HTTP method
	 */
	private function _callDELETE($uri)
	{
		return \Httpful\Request::delete($uri)
			->expectsXml() // parse response as xml
			->addHeader('OCS-APIRequest', 'true')
			->authenticateWithBasic($this->_configArray['username'], $this->_configArray['password'])
			->body(http_build_query($this->_callParametersArray)) // post parameters
			->sendsType(\Httpful\Mime::FORM)
			->send();
	}

	/**
	 * Checks the response from the remote web service
	 */
	private function _checkResponse($response)
	{
		$checkResponse = null;

		if (is_object($response)) // must be an object returned by the Httpful call
		{
			if (isset($response->body)) // the response must have a body
			{
				$status = $response->body->meta->status;
				$statuscode = (int) $response->body->meta->statuscode;
				$message = $response->body->meta->message;

				// If no 1xx code is returned it's an error
				if ($status === 'failure' || $statuscode < 100 || $statuscode > 199)
				{
					$this->_error($statuscode, $message);
				}
				else // otherwise the remote web service has given a successresponse
				{
					// If no data are present
					if ((is_string($response->body) && trim($response->body) == '')
						|| (is_array($response->body) && numberOfElements($response->body) == 0)
						|| (is_object($response->body) && numberOfElements((array)$response->body) == 0))
					{
						$this->_hasData = false; // set property _hasData to false
					}
					else
					{
						$this->_hasData = true; // set property _hasData to true
					}

					$checkResponse = $response->body; // returns a success
				}
			}
		}

		return $checkResponse;
	}

	/**
	 * Logs an error present in _errors array
	 * @param $name
	 */
	private function _logError($name)
	{
		$error = $this->_errors[$name];
		$this->_error($error['code'], $error['message']);
	}

	/**
	 * Sets property _error to true and stores an error message in property _errorMessage
	 */
	private function _error($code, $message = 'Generic error')
	{
		$this->_error = true;
		$this->_errorMessage = $code.': '.$message;
	}
}
