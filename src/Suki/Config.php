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
	protected static $_config = array();
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
			return static::$_config[$this->_app->name] = array();

		// Already loaded?
		if (!empty(static::$_config[$this->_app->name]))
			return static::$_config[$this->_app->name];

		// Check for a $modSettings key first.
		if (!empty($modSettings['_config'. $this->_app->name]))
			return static::$_config[$this->_app->name] = smf_json_decode($modSettings['_config'. $this->_app->name], true);

		// Get the json file. Must be located in $boarddir folder.
		if (!file_exists($file))
		{
			loadLanguage('Errors');
			log_error(sprintf($txt['error_bad_file'], $file));

			return static::$_config[$this->_app->name] = array();
		}

		else
			return static::$_config[$this->_app->name] = smf_json_decode(file_get_contents($file), true);
	}

	/**
	 * Gets a specific mod config array.
	 * @access public
	 * @param string $name The name of an specific setting, if empty it will return the entire array.
	 * @return mixed
	 */
	public function get($name = '')
	{
		// Not defined huh?
		if (empty(static::$_config[$this->_app->name]))
			$this->getConfig();

		return $name ? (!empty(static::$_config[$this->_app->name]['_'. $name]) ? static::$_config[$this->_app->name]['_'. $name] : false) : (!empty(static::$_config[$this->_app->name]) ? static::$_config[$this->_app->name] : false);
	}

	/**
	 * Insert/Updates a mod config value.
	 * @access public
	 * @param array $values an array of values to be inserted, it follows a name => value format
	 * @param string $instanceName The name of the instance, if empty, $this->_app->name will be used.
	 * @return array the full modified config array. An empty array if the process couldn't be performed.
	 */
	public function put($values = array(), $instanceName = '')
	{
		// The usual checks.
		if (empty($values))
			return array();

		// Work with arrays.
		$values = (array) $values;

		// Custom instance?
		$instanceName = !empty($instanceName) ? $instanceName : $this->_app->name;

		// Does it exists?
		if (empty(static::$_config[$instanceName]))
			return array();

		// Perform. Overwrite the values.
		static::$_config[$instanceName] = array_merge(static::$_config[$instanceName], $values);

		// Done!
		return static::$_config[$instanceName];
	}
}
