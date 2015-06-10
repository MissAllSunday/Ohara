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
	 * Almost all methods on this class relies on this property so make sure it is unique and make sure your files are named after this var as well.
	 * @access public
	 * @var string
	 */
	public $name = '';

	/**
	 * A list of hooks and its inner data.
	 * While {@link $_availableHooks} holds the hook's full name, this property holds the data that will be used on each hook call.
	 * If a predefined method needs predefined data for the specific hook called, you need to set the requested data on this property using the hook inner name to identify it.
	 * The "key" is used as a short reference to help identify each hook, the "value" is a mixed var, could be a boolean, a string or an array depending on the hook been called. The "value" acts as an "enable" check, if it contains an non empty var the hook is processed otherwise the value is set to the hook's corresponding type of var on its empty state (false, array(), '').
	 * Hooks that do not pass any data should be set to a boolean.
	 * Needs to be defined/extended/modified BEFORE calling {@link getRegistry()}
	 * @access protected
	 * @var array
	 */
	protected $_modHooks = array(
		'credits' => false,
		'actions' => array(),
		'helpAdmin' => '',
	);

	/**
	 * Special property to allow mod authors to overwrite every aspect of hooks called on runtime.
	 * Contains a list of hooks to be overwritten.
	 * Each hook contains an array of values, the list is as follows:
	 * 'hookName' => The hook name, it gets defined by {@link $_availableHooks},
	 * 'func' => The function that will be called for this hook, by default it uses {@$name} and adds a "add" prefix to the hook identifier, also assumes your function is a method.
	 * 'permanent' => To let SMF know if this hook will be permanently added to the DB, default is false.
	 * 'file' => The file to be loaded when this hook is called, useful if you have your function on another file, by default uses {@$name} for the filename and assumes its located on Sources folder.
	 * 'object' => Boolean param to let SMF know if your function is suppose to be called as an instantiated method or not, only valid if your function is a method.
	 * Needs to be defined/extended/modified BEFORE calling {@link getRegistry()}
	 * @access protected
	 * @var array
	 */
	protected $_overwriteHooks = array();

	/**
	 * An array containing all supported hooks by default.
	 * The "key" is used as a short reference to help identify each hook, the "value" is the full hook name.
	 * Mod authors can extend this list and add support for any other hook not listed by default.
	 * The following hooks are supported by default:
		'credits' => 'integrate_credits',
		'actions' => 'integrate_actions',
		'helpAdmin' => 'integrate_helpadmin',
	 * @access protected
	 * @var array
	 */
	protected $_availableHooks = array();

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
		if ($this->_availableHooks)
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
		foreach ($this->_availableHooks as $hook => $hook_name)
		{
			// The $hook_name value acts as an "enable" check, empty means you do not want to use this hook.
			if (empty($hook_name))
				continue;

			$overwriteThis = !empty($this->_overwriteHooks[$hook]) ? $this->_overwriteHooks[$hook] : false;

			// Set some default values.
			$defaultValues = array(
				'hookName' => $hook_name,
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
	 * Outputs a json encoded string
	 * It assumes the data is a valid array.
	 * @param array $data The data to be converted, needs to be an array
	 * @access public
	 * @return boolean whether or not the data was encoded and outputted
	 */
	public function jsonResponse($data = array())
	{
		global $db_show_debug;

		$json = '';
		$result = false;

		// Defensive programming anyone?
		if (empty($data))
			return false;

		// This is pretty simply, just encode the supplied data and be done with it.
		$json = json_encode($data);

		$result = json_last_error() == JSON_ERROR_NONE;

		if ($result)
		{
			// Don't need extra stuff...
			$db_show_debug = false;

			// Kill anything else
			ob_end_clean();

			if ($this->modSetting('CompressedOutput'))
				@ob_start('ob_gzhandler');

			else
				ob_start();

			// Set the header.
			header('Content-Type: application/json');

			// Echo!
			echo $json;

			// Done
			obExit(false);
		}

		return $result;
	}

	/**
	 * Parses and replace tokens by their given values.
	 * also automatically adds the session var for href tokens.
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
	 * Checks and returns a comma separated string.
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
		// This needs to be set and extended by someone else!
		if (!$this->_availableHooks['actions'])
			return;

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

		// This needs to be set and extended by someone else!
		if (!$this->_availableHooks['credits'])
			return;

		$context['copyrights']['mods'][] = $this->text('modCredits');
	}

	/**
	 * Loads a language file.
	 * Used to load a language to properly display any help txt strings from mods that adds new permissions via hooks
	 * Uses {@link $_modHooks} if the mod author wants to specify a custom file name, if not, it defaults to {@link $name}
	 * @access public
	 * @return void
	 */
	public function addHelpAdmin()
	{
		// This needs to be set and extended by someone else!
		if (!$this->_availableHooks['helpAdmin'])
			return;

		// You may or may not want to load a different language file.
		$loadLang = !empty($this->_modHooks['helpAdmin']) ? $this->_modHooks['helpAdmin'] : $this->name;

		// Load your precious help txt strings!
		loadLanguage($loadLang);
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
