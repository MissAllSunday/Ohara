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

	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Gets a mod's config settings, loads it and store it on {@link $_config}
	 * Checks if a $modSetting key exists and tries to load the info from it, defaults to check for a json file.
	 * @access public
	 * @return array
	 */
	protected function getConfig()
	{
		global $txt, $modSetting;

		$file = $this->app->boardDir .'/_config'. $this->app->name .'.json';

		// No config info needed.
		if (!$this->app->_useConfig)
			return static::$_config[$this->app->name] = array();

		// Already loaded?
		if (!empty(static::$_config[$this->app->name]))
			return static::$_config[$this->app->name];

		// Check for a $modSetting key first.
		if (!empty($modSetting['_config'. $this->app->name]))
			return static::$_config[$this->app->name] = smf_json_decode($modSetting['_config'. $this->app->name], true);

		// Get the json file. Must be located in $boarddir folder.
		if (!file_exists($file))
		{
			loadLanguage('Errors');
			log_error(sprintf($txt['error_bad_file'], $file));

			return static::$_config[$this->app->name] = array();
		}

		else
			return static::$_config[$this->app->name] = smf_json_decode(file_get_contents($file), true);
	}

	/**
	 * Gets a specific mod config array.
	 * @access public
	 * @param string $name The name of an specific setting, if empty it will return the entire array.
	 * @return mixed
	 */
	protected function get($name = '')
	{
		return $name ? (!empty(static::$_config[$this->app->name]['_'. $name]) ? static::$_config[$this->app->name]['_'. $name] : false) : (!empty(static::$_config[$this->app->name]) ? static::$_config[$this->app->name] : false);
	}
}
