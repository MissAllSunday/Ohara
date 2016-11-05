<?php

/**
 * @package Ohara helper class
 * @version 1.1
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2016, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;

class Config
{
	protected $_config = array();
	protected $_app;

	public function __construct($app)
	{
		$this->_app = $app;
	}

	/**
	 * Gets a mod's config settings, loads it and store it on {@link $_config}
	 * Checks if a $modSettings key exists and tries to load the info from it, defaults to check for a json file.
	 * @access public
	 * @return array
	 */
	public function getConfig()
	{
		global $txt, $modSettings;

		$file = $this->_app->boardDir .'/_config'. $this->_app->name .'.json';

		if (!$this->_app->useConfig)
			return $this->_config = array();

		// Already loaded?
		if (!empty($this->_config))
			return $this->_config;

		// Check for a $modSettings key first.
		if (!empty($modSettings['_config'. $this->_app->name]))
			return $this->_config = smf_json_decode($modSettings['_config'. $this->_app->name], true);

		// Get the json file. Must be located in $boarddir folder.
		if (!file_exists($file))
		{
			loadLanguage('Errors');
			log_error(sprintf($txt['error_bad_file'], $file));

			return $this->_config = array();
		}

		else
			return $this->_config = smf_json_decode(file_get_contents($file), true);
	}

	/**
	 * Gets a specific mod config array.
	 * @access public
	 * @param string $name The name of an specific setting, if empty it will return the entire array.
	 * @return array
	 */
	public function get($name = '')
	{
		// This needs to be extendewd by somebody else!
		if(!$this->_app->name)
			return array();

		// Not defined huh?
		if (!$this->_config)
			$this->getConfig();

		return $name ? (isset($this->_config['_'. $name]) ? $this->_config['_'. $name] : false) : ($this->_config ? $this->_config : false);
	}

	/**
	 * Insert/Updates a mod config value.
	 * @access public
	 * @param array $values an array of values to be inserted, it follows a name => value format
	 * @return array the full modified config array. An empty array if the process couldn't be performed.
	 */
	public function put($values = array())
	{
		// The usual checks.
		if (empty($values) || !$this->_app->name)
			return array();

		// Work with arrays.
		$values = (array) $values;

		// Does it exists?
		if (!$this->_config)
			$this->getConfig();

		// Perform. Overwrite the values.
		$this->_config = array_merge($this->_config, $values);

		// Done!
		return $this->_config;
	}
}
