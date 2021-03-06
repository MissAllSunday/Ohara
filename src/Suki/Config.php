<?php

/**
 * @package Ohara helper class
 * @version 1.1
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2018, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;

class Config
{
	protected $_config = [];
	protected $_app;

	public function __construct(Ohara $app)
	{
		$this->_app = $app;
	}

	/**
	 * Gets a mod's config settings, loads it and store it on {@link $_config}
	 * Checks if a $modSettings key exists and tries to load the info from it, defaults to check for a json file.
	 * @access public
	 * @return array
	 */
	public function getConfig(): array
	{
		global $txt, $modSettings;

		$file = $this->_app->boardDir .'/_config'. $this->_app->name .'.json';

		// Already loaded or does not use config?
		if ($this->_config || !$this->_app->useConfig)
			return $this->_config;

		// Check for a $modSettings key first.
		if (!empty($modSettings['_config'. $this->_app->name]))
			return $this->_config = smf_json_decode($modSettings['_config'. $this->_app->name], true);

		// Get the json file. Must be located in $boarddir folder.
		if (!$this->_config && !file_exists($file))
		{
			loadLanguage('Errors');
			log_error(sprintf($txt['error_bad_file'], $file));
		}

		else
			$this->_config = smf_json_decode(file_get_contents($file), true);

		return $this->_config;
	}

	/**
	 * Gets a specific mod config array.
	 * @access public
	 * @param string $name The name of an specific setting, if empty it will return the entire array.
	 * @return array
	 */
	public function get($name = '')
	{
		// This needs to be extended by somebody else!
		if(!$this->_app->name)
			return [];

		// Not defined huh?
		if (!$this->_config)
			$this->getConfig();

		return $name ? (isset($this->_config['_'. $name]) ? $this->_config['_'. $name] : []) : $this->_config;
	}

	/**
	 * Insert/Updates a mod config value.
	 * @access public
	 * @param array $values an array of values to be inserted, it follows a name => value format
	 * @return array the full modified config array. An empty array if the process couldn't be performed.
	 */
	public function put($values = [])
	{
		// The usual checks.
		if (empty($values) || !$this->_app->name)
			return [];

		// Work with arrays.
		$values = (array) $values;

		// Does it exists?
		if (!$this->_config)
			$this->getConfig();

		// Perform. Overwrite the values.
		$this->_config = array_merge($this->_config, $values);

		// Save it.
		updateSettings(array('_config'. $this->_app->name => json_encode($this->_config)));

		// Done!
		return $this->_config;
	}
}
