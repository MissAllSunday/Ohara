<?php

/**
 * @package Ohara helper class
 * @version 1.1
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2018, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;

class Data
{
	public function __construct(Ohara $app)
	{
		$this->_app = $app;
	}

	/**
	 * Holds any sanitized data from $_REQUEST
	 * @access protected
	 * @var array
	 */
	protected $_request = [];

	/**
	 * Sets {@link $_request} with the value of the requested superglobal var
	 * can be called directly but its also called by Ohara::data()
	 * @param string $type request, post or get. The name of the superblogal you want to fetch, defaults to request
	 * @access public
	 * @return void
	 */
	public function setData($type = 'request')
	{
		// Reset it.
		$this->_request = false;

		$types = array('request' => $_REQUEST, 'get' => $_GET, 'post' => $_POST);

		$typeSet = (empty($type) || !isset($types[$type])) ? $_REQUEST : $types[$type];
		$this->_request = $this->sanitize($typeSet);

		unset($types, $type);
	}

	/**
	 * Inserts a new value to {@link $_request}
	 * @param array $data an array of data to be added. Uses a name => value format.
	 * @access public
	 * @return mixed false on fail array.
	 */
	public function put($data = [])
	{
		if (empty($data))
			return false;

		$this->setData();

		$data = (array) $data;

		foreach ($data as $name => $value)
			$this->_request[$name] = $_REQUEST[$name] = $this->sanitize($value);

		return $this->_request;
	}

	/**
	 * Sanitizes and returns the requested value.
	 * calls Suki\Data::sanitize() to properly clean up
	 * @param string $var the superglobal's key name you want to retrieve.
	 * @param mixed $default The default value used if the setting doesn't exists.
	 * @access public
	 * @return mixed
	 */
	public function get($var = '', $default = null)
	{
		if (empty($var))
			return false;

		$this->setData();

		return $this->validate($var) ? $this->_request[$var] : (!is_null($default) ? $default : false);
	}

	/**
	 * Sanitizes and returns all values.
	 * calls Suki\Data::sanitize() to properly clean up
	 * @param string $type The type of superglobal you want to retrieve.
	 * @access public
	 * @return array
	 */
	public function getAll($type = 'request'): array
	{
		$this->setData($type);

		return $this->_request;
	}

	/**
	 * Checks the var against {@link $_request} to know if it exists and its defined or not.
	 * calls Ohara::setData() in case {@link $_request} is empty by the time this method its called
	 * @param string $var the superglobal's key name you want to check
	 * @access public
	 * @return boolean
	 */
	public function validate($var): bool
	{
		// $var should always be a string, it should be the name of the var you want to validate, not the actual var!
		if (!is_string($var))
			return false;

		// Forgot something?
		if (!$this->_request)
			$this->setData();

		return (isset($this->_request[$var]));
	}

	/**
	 * Sanitizes a var. Recursive.
	 * Treats any var as a string and cast it as an integer if necessary.
	 * @param mixed $var The var you want to sanitize
	 * @access public
	 * @return mixed
	 */
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
			$var = (string) $smcFunc['htmltrim']($smcFunc['htmlspecialchars']($var), ENT_QUOTES);

			if (ctype_digit($var))
				$var = (int) $var;

			if (empty($var))
				$var = false;
		}

		return $var;
	}

	/**
	 * Sets a temp var in $_SESSION.
	 * Ohara automatically creates a new key in $_SESSION using {@link $name}
	 * @param string $key The unique identifier for your message
	 * @param mixed $message ideally to store a message but can be used to store any type of variable
	 * @access public
	 * @return boolean
	 */
	public function setUpdate($key, $message)
	{
		// Define an update key for this class.
		if (!isset($_SESSION[$this->_app->name]['update']))
			$_SESSION[$this->_app->name]['update'] = [];

		// We need a key and an actual message...
		if (empty($key) || empty($message))
			return false;

		// Store it! or overwrite it!
		return $_SESSION[$this->_app->name]['update'][$key] = $this->sanitize($message);
	}

	/**
	 * Gets the var previously added by Ohara::setUpdate()
	 * calls Ohara::cleanUpdate() to delete the entry from $_SESSION
	 * @param string $key The unique identifier for your message
	 * @access public
	 * @return mixed
	 */
	public function getUpdate($key)
	{
		if (empty($key))
			return false;

		$update =  !empty($_SESSION[$this->_app->name]['update'][$key]) ? $_SESSION[$this->_app->name]['update'][$key] : false;

		if (!empty($update))
			$this->cleanUpdate($key);

		return $update;
	}

	/**
	 * Gets all vars associated with the the extending class using {@link $name}
	 * calls Ohara::cleanUpdate() to delete the entry from $_SESSION
	 * @access public
	 * @return mixed|boolean
	 */
	public function getAllUpdates()
	{
		$update =  !empty($_SESSION[$this->_app->name]['update']) ? $_SESSION[$this->_app->name]['update'] : false;

		// Clean em all!
		$this->cleanUpdate();

		return $update;
	}

	/**
	 * Deletes the key from $_SESSION
	 * automatically called by any getter after retrieving the needed message.
	 * @param string $key The unique identifier for your message. No key means all messages will be cleaned.
	 * @access public
	 */
	public function cleanUpdate($key = '')
	{
		// No key means you want to clean em all.
		if (empty($key))
			unset($_SESSION[$this->_app->name]['update']);

		else
			unset($_SESSION[$this->_app->name]['update'][$key]);
	}
}
