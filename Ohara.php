<?php

/**
 * @package Ohara helper class mod
 * @version 1.0
 * @author Jessica Gonz�lez <suki@missallsunday.com>
 * @copyright Copyright (c) 2013, Jessica Gonz�lez
 * @license http://www.mozilla.org/MPL/MPL-1.1.html
 */

/*
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is http://code.mattzuba.com code.
 *
 * The Initial Developer of the Original Code is
 * Matt Zuba.
 * Portions created by the Initial Developer are Copyright (C) 2010-2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Jessica Gonz�lez <suki@missallsunday.com>
 */

class Ohara
{
	/**
	 * @var SMF_Singleton Instance of the bridge class
	 */
	protected static $instance;

	/**
	 * @var array Comma separated list of hooks this class implements
	 */
	protected $hooks = array();

	/**
	 * @var boolean Should the hooks only be installed once?
	 */
	protected $persistHooks = FALSE;

	/**
	 * This should be overwritten
	 */
	protected function __construct()
	{
		if (!$this->persistHooks)
			$this->installHooks();
	}

	/**
	 * Installs the hooks to be used by this module.
	 */
	public function installHooks()
	{
		foreach ($this->hooks as $hook => $method)
			add_integration_function($hook, static::$className . '::handleHook', $this->persistHooks);
	}

	public function getHooks()
	{
		return isset(self::$instance->hooks) ? self::$instance->hooks : false;
	}

	/**
	 * Takes all call_integration_hook calls from SMF and figures out what
	 * method to call within the class
	 */
	public static function handleHook()
	{
		$hooks = self::$instance->getHooks();
		$backtrace = debug_backtrace();
		$method = NULL;
		$args = NULL;
		foreach ($backtrace as $item)
			if ($item['function'] === 'call_integration_hook')
			{
				$method = $hooks[$item['args'][0]];
				$args = !empty($item['args'][1]) ? $item['args'][1] : array();
				break;
			}

		if (!isset($method) || !is_callable(array(self::$instance, $method)))
			trigger_error('Invalid call to handleHook', E_USER_ERROR);

		return call_user_func_array(array(self::$instance, $method), $args);
	}

	/**
	 * Let's try the singleton method
	 *
	 * @return object
	 */
	public static function run()
	{
		if (!isset(static::$className))
			trigger_error('<strong>protected static $className = __CLASS__;</strong> must be contained in child class', E_USER_ERROR);

		if (!isset(self::$instance) || !(self::$instance instanceof static::$className))
			self::$instance = new static::$className();

		return self::$instance;
	}

	public function text($string)
	{
		global $txt;

		if (empty($string))
			return false;

		if (!isset($txt[static::$className .'_'. $string]))
			loadLanguage(static::$className);

		if (!empty($txt[static::$className .'_'. $string]))
			return $txt[static::$className .'_'. $string];

		else
		return false;
	}

	public function setting($var)
	{
		global $modSettings;

		if (!empty($modSettings[static::$className .'_'. $var]))
			return $modSettings[static::$className .'_'. $var];

		else
			return false;
	}

	public function sanitize($var)
	{

		if (empty($var))
			return false;

		$return = false;

		// Is this an array?
		if (is_array($var))
			foreach ($var as $item)
			{
				if (!in_array($item, $_REQUEST))
					continue;

				if (empty($_REQUEST[$item]))
					$return[$item] = '';

				if (is_numeric($_REQUEST[$item]))
					$return[$item] = (int) trim($_REQUEST[$item]);

				elseif (is_string($_REQUEST[$item]))
					$return[$item] = trim(htmlspecialchars($_REQUEST[$item], ENT_QUOTES));
			}

		// No? a single item then, check it boy, check it!
		elseif (empty($_REQUEST[$var]))
			return false;

		else
		{
			if (is_numeric($_REQUEST[$var]))
				$return = (int) trim($_REQUEST[$var]);

			elseif (is_string($_REQUEST[$var]))
				$return = trim(htmlspecialchars($_REQUEST[$var], ENT_QUOTES));
		}

		return $return;
	}

	public function data($var = false)
	{
		if (empty($var))
			return false;

		return $this->sanitize($var);
	}
}
