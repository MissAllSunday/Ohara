<?php

/**
 * @package Ohara helper class
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2014, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;

/**
 * Helper class for SMF modifications
 * @package Ohara helper class
 * @subpackage classes
 */
class Ohara
{
	/**
	 * The main identifier for the class extending Ohara, needs to be re-defined by each extending class
	 * Almot all methods on this class relies on this property so make sure it is unique and make sure your files are named after this var as well.
	 * @access public
	 * @var string
	 */
	public $name = '';

	/**
	 * Text array for holding your own text strings
	 * @access protected
	 * @var array
	 */
	protected $_text = array();

	/**
	 * An array holding up all instances extending Ohara
	 * @static
	 * @access protected
	 * @var array
	 */
	protected static $_registry = array();

	/**
	 * Holds any sanitized data from $_REQUEST
	 * @access protected
	 * @var array
	 */
	protected $_request = array();

	/**
	 * Getter for {@link $name} property.

	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Registers your function on {@link $_registry} and sets many properties replacing SMF's global vars
	 * Needs to be called by any class extending this class, preferable on a __construct method but can be called when/where necessary.
	 * Calls {@link createHooks()} if there is any runtime hook.
	 * @access public
	 * @return void
	 */
	public function setRegistry()
	{
		global $sourcedir, $scripturl;
		global $settings, $boarddir, $boardurl;

		$this->sourceDir = $sourcedir;
		$this->scriptUrl = $scripturl;
		$this->settings = $settings;
		$this->boardDir = $boarddir;
		$this->boardUrl = $boardurl;

		static::$_registry[$this->name] = $this;

		// Any runtime hooks?
		if ($this->_modHooks)
			$this->createHooks();
	}

	/**
	 * Getter for {@link $_registry} property.
	 * @access public
	 * @param string $instance The name of the instance you want to retrieve, leave empty to retrieve the entire array
	 * @return string|array|bool
	 */
	public function getRegistry($instance = '')
	{
		return $instance ? (!empty(static::$_registry[$instance]) ? static::$_registry[$instance] : false) : (!empty(static::$_registry) ? static::$_registry : false);
	}

	/**
	 * Dummy method used by Ohara to run {@link createHooks()} via the child's __construct() method and {@link setRegistry()}.
	 * Mod authors can extend this to run their own methods as this is intended to be called pretty early in SMF's process (using SMF's integrate_pre_load hook).
	 * @access public
	 * @return bool
	 */
	public function runTimeHooks()
	{
		return false;
	}

	/**
	 * Takes each defined hook in {@link $_modHooks} and tries to add the relevant data for each hook
	 * Uses {@link $_availableHooks} to know which hook are going to be added
	 * Uses {@link $_overwriteHooks} to let the mod author to overwrite all or any params before calling add_integration_function.
	 * @access public
	 */
	public function createHooks()
	{
		foreach ($this->_modHooks as $hook => $data)
		{
			// The $data value acts as an "enable" check, empty means you do not want to use this hook.
			if (empty($data))
				continue;

			$overwriteThis = !empty($this->_overwriteHooks[$hook]) ? $this->_overwriteHooks[$hook] : false;

			// Set some default values.
			$defaultValues = array(
				'hookName' => $this->_availableHooks[$hook],
				'func' => $this->name .'::add'. ucfirst($hook),
				'permanent' => false,
				'file' => '$sourcedir/'. $this->name .'.php',
				'object' => true,
			);

			// You might or might not want to overwrite this...
			extract(!empty($overwriteThis) ? array_merge($defaultValues, $overwriteThis) : $defaultValues);

			add_integration_function($hookName, $func, $permanent, $file, $object);
		}
	}

	/**
	 * Getter for {@link $_text} property.
	 * @access public
	 * @param string $var The name of the $txt key you want to retrieve
	 * @return bool|string
	 */
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

	/**
	 * Loads the extending class language file and sets a new key in {@link $_text}
	 * Ohara automatically adds the value of {@link $name} plus and underscore to match the exact $txt key when fetching the var
	 * @access protected
	 * @param string $var The name of the $txt key you want to retrieve
	 * @return bool|string
	 */
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

	/**
	 * Getter for {@link $_text}
	 * @access public
	 * @return array
	 */
	public function getAllText()
	{
		return $this->_text;
	}

	/**
	 * Checks for a $modSetting key and its state
	 * returns true if the $modSetting exists and its not empty regardless of what its value is
	 * @param string $var The name of the $modSetting key you want to retrieve
	 * @access public
	 * @return boolean
	 */
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

	/**
	 * Returns the actual value of the selected $modSetting
	 * uses Ohara::enable() to determinate if the var exists
	 * @param string $var The name of the $modSetting key you want to retrieve
	 * @access public
	 * @return mixed|boolean
	 */
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

	/**
	 * Returns the actual value of a generic $modSetting var
	 * useful to check external $modSettings vars
	 * @param string $var The name of the $modSetting key you want to retrieve
	 * @access public
	 * @return mixed|boolean
	 */
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

	/**
	 * Sets {@link $_request} with the value of the requested superglobal var
	 * can be called directly but its also called by Ohara::data()
	 * @param string $type request, post or get. The name of the superblogal you want to fetch, defaults to request
	 * @access public
	 * @return void
	 */
	public function setData($type = 'request')
	{
		$types = array('request' => $_REQUEST, 'get' => $_GET, 'post' => $_POST);

		$this->_request = (empty($type) || !isset($types[$type])) ? $_REQUEST : $types[$type];

		unset($types);
	}

	/**
	 * Sanitizes and returns the requested value.
	 * calls Ohara::sanitize() to properly clean up
	 * @param string $var the superglobal's key name you want to retrieve
	 * @access public
	 * @return mixed
	 */
	public function data($var)
	{
		// Forgot something?
		if (empty($this->_request))
			$this->setData();

		return $this->validate($var) ? $this->sanitize($this->_request[$var]) : false;
	}

	/**
	 * Checks the var against {@link $_request} to know if it exists and its defined or not.
	 * calls Ohara::setData() in case {@link $_request} is empty by the time this method its called
	 * @param string $var the superglobal's key name you want to check
	 * @access public
	 * @return boolean
	 */
	public function validate($var)
	{
		// $var should always be a string, it should be the name of the var you want to validate, not the actual var!
		if (!is_string($var))
			return false;

		// Forgot something?
		if (empty($this->_request))
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
	 * @return void|boolean
	 */
	public function setUpdate($key, $message)
	{
		// Define an update key for this class.
		if (!isset($_SESSION[$this->name]['update']))
			$_SESSION[$this->name]['update'] = array();

		// We need a key and an actual message...
		if (empty($key) || empty($message))
			return false;

		// Store it! or overwrite it!
		if (!isset($_SESSION[$this->name]['update'][$key]))
			$_SESSION[$this->name]['update'][$key] = $message;
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

		$update =  !empty($_SESSION[$this->name][$key]) ? $_SESSION[$this->name][$key] : false;

		foreach ($update as $key => $m)
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
		$update =  !empty($_SESSION[$this->name]['update']) ? $_SESSION[$this->name]['update'] : false;

		if (!empty($update))
			foreach ($update as $k => $v)
				$this->cleanUpdate($k);

		return $update;
	}

	/**
	 * Deletes the key form $_SESSION
	 * automatically called by any getter after retrieving the needed message.
	 * @param string $key The unique identifier for your message
	 * @access public
	 */
	public function cleanUpdate($key)
	{
		if (empty($key))
			return false;

		unset($_SESSION[$this->name]['update'][$key]);
	}

	/**
	 * Parses and replace tokens by their given values.
	 * also automatically adds the session var for url tokens.
	 * @access public
	 * @param string $text The raw text.
	 * @param array $replacements a key => value array containing all tokens to be replaced.
	 * @return string|bool
	 */
	public function parser($text, $replacements = array())
	{
		global $context;

		if (empty($text) || empty($replacements) || !is_array($replacements))
			return '';

		// Inject the session.
		$s = ';'. $context['session_var'] .'='. $context['session_id'];

		// Split the replacements up into two arrays, for use with str_replace.
		$find = array();
		$replace = array();

		foreach ($replacements as $f => $r)
		{
			$find[] = '{' . $f . '}';
			$replace[] = $r . ((strpos($f,'href') !== false) ? $s : '');
		}

		// Do the variable replacements.
		return str_replace($find, $replace, $text);
	}

	/**
	 * Checks and returns a coma separated string.
	 * @access public
	 * @param string $string The string to check and format
	 * @return string|bool
	 */
	public function commaSeparated($string)
	{
		return empty($string) ? false : implode(',', array_filter(explode(',', preg_replace(
			array(
				'/[^\d,]/',
				'/(?<=,),+/',
				'/^,+/',
				'/,+$/'
			), '', $string
		))));
	}

	/**
	 * Creates a new SMF action.
	 * Uses {@link $_modHooks} to determinate the name and file for the action, if no data is given, {@link $name} will be used
	 * @access public
	 * @param array $actions An array containing all current registered SMF actions at the moment of this method execution
	 * @return void
	 */
	public function addActions(&$actions)
	{
		// Set some default values.
		$name = !empty($this->_modHooks['action']['name']) ? $this->_modHooks['action']['name'] : $this->name;
		$file = !empty($this->_modHooks['action']['file']) ? $this->_modHooks['action']['file'] : $this->name .'.php';
		$call = !empty($this->_modHooks['action']['callable']) ? $this->_modHooks['action']['callable'] : $this->name .'::call#';

		$actions[$name] = array($file, $call);
	}

	/**
	 * Creates a copyright link on the credits page.
	 * Uses {@link $_modHooks} to determinate if a link should be added
	 * Uses a predefined $txt string $this->text('modCredits')
	 * @access public
	 * @return void
	 */
	public function addCredits()
	{
		global $context;

		// This needs to be extended by someone else!
		if (!$this->_modHooks['credits'])
			return;

		$context['copyrights']['mods'][] = $this->text('modCredits');
	}

	/**
	 * Magic method to check properties.
	 * uses variable variables.
	 * @access public
	 * @param string $string The var name to check
	 * @return bool
	 */
	public function __isset($name)
	{
		// Directly check the property
		return !empty($this->{$name});
	}
}
