<?php

/**
 * Implements the BIS webservice calls
 */
abstract class BISClientModel extends CI_Model
{
	protected $_apiSetName; // to store the name of the api set name

	/**
	 *
	 */
	public function __construct()
	{
		// Loads the BisClientLib library
		$this->load->library('extensions/FHC-Core-BIS/BISClientLib');
	}

	// --------------------------------------------------------------------------------------------
	// Protected methods

	/**
	 * Generic BIS webservice call
	 */
	protected function _call($wsFunction, $httpMethod, $uriParametersArray = array(), $callParametersArray = array())
	{
		// Checks if the property _apiSetName is valid
		if ($this->_apiSetName == null || trim($this->_apiSetName) == '')
		{
			$this->bisclientlib->resetToDefault();

			return error('API set name not valid');
		}

		// Call the BIS webservice with the given parameters
		$wsResult = $this->bisclientlib->call($this->_apiSetName, $wsFunction, $httpMethod, $uriParametersArray, $callParametersArray);

		// If an error occurred return it
		if ($this->bisclientlib->isError())
		{
			$wsResult = error($this->bisclientlib->getError(), $this->bisclientlib->getErrorCode());
		}
		else // otherwise return a success
		{
			$wsResult = success($wsResult);
		}

		// Reset the bisclientlib parameters
		$this->bisclientlib->resetToDefault();

		// Return a success object that contains the web service result
		return $wsResult;
	}
}

