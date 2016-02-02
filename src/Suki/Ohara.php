<?php

/**
 * @package Ohara helper class
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2014, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;
use Suki\ClassLoader;

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

	protected static $_config = array();

	/**
	 * A security check to make sure the mod does want to use a config file.
	 * @static
	 * @access protected
	 * @var boolean
	 */
	protected $_useConfig = false;

	/**
	 * Holds any sanitized data from $_REQUEST
	 * @access protected
	 * @var array
	 */
	protected $_request = array();

	protected static $loader;

	/**
	 * Getter for {@link $name} property.

	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	public function autoLoad($force = false)
	{
		if (null !== self::$loader && !$force)
			return self::$loader;

		// Yep, manually loaded...
		require_once ($this->sourceDir .'/ohara/src/Suki/ClassLoader.php');

		// Define some of the most commonly used dirs.
		$vendorDir = $this->boardDir .'/vendor';
		$baseDir = dirname($vendorDir);
		self::$loader = $loader = new ClassLoader();
		$replacements = array(
			'$vendorDir' => $vendorDir,
			'$baseDir' => $baseDir,
			'$boarddir' => $this->boardDir,
			'$sourcedir' => $this->sourceDir,
		);

		if ($this->config('libNamespace'))
			foreach ($this->config('libNamespace') as $namespace => $path)
			{
				$path = (array) $path;
				$path[0] = $this->parser($path[0], $replacements);
				$loader->set($namespace, $path);
			}

		if ($this->config('libPSR'))
			foreach ($this->config('libPSR') as $namespace => $path)
			{
				$path = (array) $path;
				$path[0] = $this->parser($path[0], $replacements);
				$loader->setPsr4($namespace, $this->parser($path, $replacements));
			}

		if ($this->config('libClassMap'))
			foreach ($this->config('libClassMap') as $name => $classMap)
			{
				$that = $this;
				$classMap = (array) $classMap;
				$classMap = array_map(function($map) use ($that, $replacements) { return $that->parser($map, $replacements); }, $classMap);
				$path[0] = $this->parser($path[0], $replacements);
				$loader->addClassMap($this->parser($classMap, $replacements));
				unset($that);
			}

		$loader->register(true);

		return $loader;
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

		// Get this mod's config file.
		$this->getConfigFile();

		// Any runtime hooks?
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

	protected function getConfigFile()
	{
		global $txt;

		$file = $this->sourceDir .'/_config'. $this->name .'.json';

		// No config file needed.
		if (!$this->useConfig)
			return static::$_config[$this->name] = array();

		// Already loaded?
		if (!empty(static::$_config[$this->name]))
			return static::$_config[$this->name];

		// Get the json file. Must be located in Sources folder and must be named:
		if (!file_exists($file))
		{
			loadLanguage('Errors');
			log_error($this->parser($txt['error_bad_file'], array('%1$s' => $file)));

			return static::$_config[$this->name] = array();
		}

		else
		{
			$jsonArray = json_decode(file_get_contents($file), true);

			$result = json_last_error() == JSON_ERROR_NONE;

			// Everything went better than expected!
			if ($result)
				return static::$_config[$this->name] = $file;

			else
			{
				loadLanguage('Errors');
				log_error($this->parser($txt['error_bad_file'], array('%1$s' => $file)));

				return static::$_config[$this->name] = array();
			}
		}
	}

	protected function config($name = '')
	{
		return $name ? (!empty(static::$_config[$this->name]['_'. $name]) ? static::$_config[$this->name]['_'. $name] : false) : (!empty(static::$_config[$this->name]) ? static::$_config[$this->name] : false);
	}

	/**
	 * Dummy method used by Ohara to run {@link createHooks()} via the child's __construct() method and {@link setRegistry()}.
	 * Mod authors can extend this to run their own methods as this is intended to be called pretty early in SMF's process (using SMF's integrate_load_theme hook).
	 * @access public
	 * @return bool
	 */
	public function runTimeHooks()
	{
		return false;
	}

	/**
	 * Takes each defined hook in {@link $_availableHooks} and tries to add the relevant data for each hook
	 * @access public
	 */
	public function createHooks()
	{
		global $context;

		// Get the hooks.
		$hooks = $this->config('availableHooks');
		$overwriteHooks = $this->config('overwriteHooks');

		// Don't execute on uninstall.
		if (!empty($context['uninstalling']) || $this->data('sa') == 'uninstall2' || !$hooks)
			return;

		foreach ($hooks as $hook => $hook_name)
		{
			// The $hook_name value acts as an "enable" check, empty means you do not want to use this hook.
			if (empty($hook_name))
				continue;

			// Gotta replace our tokens.
			if ($overwriteHooks && !empty($overwriteHooks[$hook]))
				$overwriteHooks[$hook]['file'] = $this->parser($overwriteHooks[$hook]['file'], array(
					'$sourcedir' => $this->sourceDir,
					'$scripturl' => $this->scriptUrl,
					'$boarddir' => $this->boardDir,
					'$boardurl' => $this->boardUrl,
				));

			else
				$overwriteHooks[$hook] = array();

			// Set some default values.
			$defaultValues = array(
				'hookName' => $hook_name,
				'func' => $this->name .'::add'. ucfirst($hook),
				'permanent' => false,
				'file' => '$sourcedir/'. $this->name .'.php',
				'object' => true,
			);

			// You might or might not want to overwrite this...
			extract(array_merge($defaultValues, $overwriteHooks[$hook]));

			// You can also disable any hook used by this mod from the mod's admin settings if the mod has that feature.
			$hookAction = ($this->enable('disable_hook_'. $hook_name) ? 'remove' : 'add') .'_integration_function';

			$hookAction($hookName, $func, $permanent, $file, $object);
		}
	}

	/**
	 * Takes each defined hook in {@link $_availableHooks} and tries to create an admin setting for it.
	 * Uses a few specific text strings: disable_hook_title, disable_hook_desc, disable_hook and disable_hook_sub the only required text string is disable_hook.
	 * @param array $config_vars Passed by reference, a regular SMF's config_vars array.
	 * @access public
	 * @return void
	 */
	public function disableHooks(&$config_vars)
	{
		$hooks = $this->config('availableHooks');

		// No hooks were found. Nothing to do.
		if (!$hooks)
			return false;

		// A title and a description.
		if ($this->text('disable_hook_title'))
			$config_vars[] = array('title', 'Ohara_disableHooks_title', 'label' => $this->parser($this->text('disable_hook_title'), array('modname' => $this->name)));

		if ($this->text('disable_hook_desc'))
			$config_vars[] = array('desc', 'Ohara_disableHooks_desc', 'label' => $this->parser($this->text('disable_hook_desc'), array('modname' => $this->name)));

		foreach ($hooks as $hook => $hook_name)
		{
			// Hook has already been disabled, no point in disabling it again :P
			if (empty($hook_name))
				continue;

			// Gotta "protect" the admin and settings hooks.
			$config_vars[] = array('check', $this->name .'_disable_hook_'. $hook_name, 'disabled' => (strpos($hook_name, 'admin') !== false || strpos($hook_name, 'setting') !== false) ? true : false, 'label' => $this->parser($this->text('disable_hook'), array('hook' => $hook_name)), 'subtext' => ($this->text('disable_hook_sub') ? $this->parser($this->text('disable_hook_'. $hook .'_sub'), array('hook' => $hook)) : ''));
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

		if (!empty($update))
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
	 * Checks if a string contains a scheme. Adds one if necessary
	 * checks for schemaless urls.
	 * @param string $url The data to be converted, needs to be an array.
	 * @param boolean $secure If a scheme has to be added, check if https should be used.
	 * @access public
	 * @return string The passed string.
	 */
	public function checkScheme($url = '', $secure = false)
	{
		$parsed = array();
		$parsed = parse_url($url);
		$pos = strpos($url, '//');

		// Perhaps this is a schema less url? parse_url should detect schema less urls .___.
		if (empty($parsed['scheme']) && strpos($url, '//') !== false)
			return $url;

		elseif (empty($parsed['scheme']))
			return 'http'. ($secure ? 's' : '') .'://'. $url;

		else
			return $url;
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

		// Split the replacements up into two arrays, for use with str_replace.
		$find = array();
		$replace = array();

		foreach ($replacements as $f => $r)
		{
			$find[] = '{' . $f . '}';
			$replace[] = $r . ((strpos($f,'href') !== false) ? (';'. $context['session_var'] .'='. $context['session_id']) : '');
		}

		// Do the variable replacements.
		return str_replace($find, $replace, $text);
	}

	/**
	 * Checks and returns a comma separated string.
	 * @access public
	 * @param string $string The string to check and format
	 * @param string $type The type to check against. Accepts "numeric", "alpha" and "alphanumeric".
	 * @param string $delimiter Used for explode7imploding the string.
	 * @return string|bool
	 */
	public function commaSeparated($string, $type = 'alphanumeric', $delimiter = ',')
	{
		if (empty($string))
			return false;

		switch ($type) {
			case 'numeric':
				$t = '\d';
				break;
			case 'alpha':
				$t = '[:alpha:]';
				break;
			case 'alphanumeric':
			default:
				$t = '[:alnum:]';
				break;
		}
		return empty($string) ? false : implode($delimiter, array_filter(explode($delimiter, preg_replace(
			array(
				'/[^'. $t .',]/',
				'/(?<=,),+/',
				'/^,+/',
				'/,+$/'
			), '', $string
		))));
	}

	/**
	 * Returns a formatted string.
	 * @access public
	 * @param string|int  $bytes A number of bytes.
	 * @param bool $showUnits To show the unit symbol or not.
	 * @return string
	 */
	public function formatBytes($bytes, $showUnits = false)
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= (1 << (10 * $pow));
		return round($bytes, 4) . ($showUnits ? ' ' . $units[$pow] : '');
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
		$hooks = $this->config('availableHooks');
		$oActions = $this->config('actions');
		$counter = 0;

		if (empty($hooks['actions']))
			return;

		// An array?
		if (is_array($oActions))
			foreach ($oActions as $a)
			{
				// This needs a name... provide a generic one.
				$counter++;

				$name = !empty($a['name']) ? $a['name'] : $this->name . $counter;
				$file = !empty($a['file']) ? $a['file'] : $this->name .'.php';
				$call = !empty($a['callable']) ? $a['callable'] : $this->name .'::call#';

				$actions[$name] = array($file, $call);
			}

		else
		{
			$name = !empty($oActions['name']) ? $oActions['name'] : $this->name;
			$file = !empty($oActions['file']) ? $oActions['file'] : $this->name .'.php';
			$call = !empty($oActions['callable']) ? $oActions['callable'] : $this->name .'::call#';

			$actions[$name] = array($file, $call);
		}
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
		$hooks = $this->config('availableHooks');

		if (!$hooks['credits'])
			return;

		$context['copyrights']['mods'][] = is_string($hooks['credits']) ? $this->text($hooks['credits']) : $this->text('modCredits');
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
		$hooks = $this->config('availableHooks');

		if (!$hooks['helpAdmin'])
			return;

		// You may or may not want to load a different language file.
		$loadLang = is_string($hooks['helpAdmin']) ? $hooks['helpAdmin'] : $this->name;

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
