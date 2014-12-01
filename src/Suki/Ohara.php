<?php

/**
 * @package Ohara helper class
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2014, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;

class Ohara
{
	public $name = '';
	protected $_text = array();
	protected static $_registry = array();
	protected $_request = array();

	public function getName()
	{
		return $this->name;
	}

	public function setRegistry()
	{
		global $sourcedir, $scripturl, $smcFunc;
		global $settings, $boarddir, $boardurl;

		$this->sourceDir = $sourcedir;
		$this->scriptUrl = $scripturl;
		$this->smcFunc = $smcFunc;
		$this->settings = $settings;
		$this->boardDir = $boarddir;
		$this->boardUrl = $boardurl;

		self::$_registry[$this->name] = $this;
	}

	public function getRegistry($instance = '')
	{
		return $instance ? self::$_registry[$instance] : self::$_registry;
	}

	public function text($var)
	{
		global $txt;

		// This should be extended by somebody else...
		if (empty($this->name) || empty($var))
			return false;

		if (!isset($this->_text[$var]))
			$this->setText($var);

		return $this->_text[$var];
	}

	protected function setText($var)
	{
		global $txt;

		// No var no set.
		if (empty($var))
			return false;

		// Load the mod's language file.
		loadLanguage($this->name);

		if (!empty($txt[$this->name .'_'. $var]))
			$this->_text[$var] =  $txt[$this->name .'_'. $var];

		else
			$this->_text[$var] = false;
	}

	public function getAllText()
	{
		return $this->_text;
	}

	public function enable($var)
	{
		global $modSettings;

		if (empty($var))
			return false;

		if (isset($modSettings[$this->name .'_'. $var]) && !empty($modSettings[$this->name .'_'. $var]))
			return true;

		else
			return false;
	}

	public function setting($var)
	{
		global $modSettings;

		// This should be extended by somebody else...
		if (empty($this->name) || empty($var))
			return false;

		if (true == $this->enable($var))
			return $modSettings[$this->name .'_'. $var];

		else
			return false;
	}

	public function modSetting($var)
	{
		global $modSettings;

		// This should be extended by somebody else...
		if (empty($this->name))
			return false;

		if (empty($var))
			return false;

		if (isset($modSettings[$var]))
			return $modSettings[$var];

		else
			return false;
	}

	public function data($var)
	{
		return $this->validate($var) ? $this->sanitize($this->_request[$var]) : false;
	}

	public function validate($var, $type = 'request')
	{
		// $var should always be a string, it should be the name of the var you want to validate, not the actual var!
		if (!is_string($var))
			return false;

		$types = array('request' => $_REQUEST, 'get' => $_GET, 'post' => $_POST);

		$this->_request = (empty($type) || !isset($types[$type])) ? $_REQUEST : $types[$type];

		unset($types);
		return (isset($this->_request[$var]));
	}

	public function sanitize($var)
	{
		global $smcFunc;

		if (is_array($var))
		{
			foreach ($var as $k => $v)
				$var[$k] = $this->sanitize($v);

			return $var;
		}

		else
		{
			if (is_numeric($var))
				$var = (int)trim($var);

			else if (is_string($var))
				$var = $smcFunc['htmltrim']($smcFunc['htmlspecialchars']($var), ENT_QUOTES);

			else
				$var = 'error_' . $var;
		}

		return $var;
	}

	public function setMessage($key, $message)
	{
		if (empty($key) || empty($message))
			return false;

		if (!isset($_SESSION[$this->name][$key]))
			$_SESSION[$this->name][$key] = $message;
	}

	public function getMessage($key)
	{
		if (empty($key))
			return false;

		$message =  !empty($_SESSION[$this->name][$key]) ? $_SESSION[$this->name][$key] : false;

		$this->cleanMessage($key);

		return $message;
	}

	public function cleanMessage($key)
	{
		if (empty($key))
			return false;

		unset($_SESSION[$this->name][$key]);
	}
}
