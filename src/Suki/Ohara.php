<?php

/**
 * @package Ohara helper class
 * @version 1.1
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2016, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;
use Pimple\Container;

/**
 * Helper class for SMF modifications
 * @package Ohara helper class
 * @subpackage classes
 */
class Ohara extends \Pimple\Container
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
	public $text = array();

	public $useConfig = false;

	protected static $loader;

	protected $_services = array(
		'tools',
		'form',
		'config',
		'loader',
		'data',
	);

	protected function set()
	{
		foreach($this->_services as $s)
			$this[$s] = function ($c) use ($s)
			{
				// Build the right namespace.
				$call = __NAMESPACE__ .'\\'. ucfirst($s);
				return new $call($c);
			};
	}

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
		global $boarddir, $boardurl;

		$this->sourceDir = $sourcedir;
		$this->scriptUrl = $scripturl;
		$this->boardDir = $boarddir;
		$this->boardUrl = $boardurl;

		// Create the services.
		$this->set();

		// Get this mod's config file.
		$this['config']->getConfig();

		// Any runtime hooks?
		$this->createHooks();
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
		$hooks = $this['config']->get('availableHooks');
		$overwriteHooks = $this['config']->get('overwriteHooks');

		// Don't execute on uninstall.
		if (!empty($context['uninstalling']) || $this['data']->get('sa') == 'uninstall2' || !$hooks)
			return;

		foreach ($hooks as $hook => $hook_name)
		{
			// The $hook_name value acts as an "enable" check, empty means you do not want to use this hook.
			if (empty($hook_name))
				continue;

			// Gotta replace our tokens.
			if ($overwriteHooks && !empty($overwriteHooks[$hook]))
				$overwriteHooks[$hook]['file'] = $this['tools']->parser($overwriteHooks[$hook]['file'], array(
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
		$hooks = $this['config']->get('availableHooks');

		// No hooks were found. Nothing to do.
		if (!$hooks)
			return false;

		// A title and a description.
		if ($this->text('disable_hook_title'))
			$config_vars[] = array('title', 'Ohara_disableHooks_title', 'label' => $this['tools']->parser($this->text('disable_hook_title'), array('modname' => $this->name)));

		if ($this->text('disable_hook_desc'))
			$config_vars[] = array('desc', 'Ohara_disableHooks_desc', 'label' => $this['tools']->parser($this->text('disable_hook_desc'), array('modname' => $this->name)));

		foreach ($hooks as $hook => $hook_name)
		{
			// Hook has already been disabled, no point in disabling it again :P
			if (empty($hook_name))
				continue;

			// Gotta "protect" the admin and settings hooks.
			$config_vars[] = array(
				'check',
				$this->name .'_disable_hook_'. $hook_name, 'disabled' => (strpos($hook_name, 'admin') !== false || strpos($hook_name, 'setting') !== false) ? true : false,
				'label' => $this['tools']->parser($this->text('disable_hook'), array('hook' => $hook_name)),
				'subtext' => ($this->text('disable_hook_sub') ? $this['tools']->parser($this->text('disable_hook_'. $hook .'_sub'), array('hook' => $hook)) : '')
			);
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
		if (!$this->name || empty($var))
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

		// This should be extended by somebody else...
		if (empty($this->name) || empty($var))
			return false;

		if (!empty($modSettings[$this->name .'_'. $var]))
			return true;

		else
			return false;
	}

	/**
	 * Returns the actual value of the selected $modSetting
	 * If no setting exists and a default value has been defined, the default value is returned.
	 * This is a shortcut for cases like: $var = !empty($modSettings['foo']) ? $modSettings['foo'] : 'baz';
	 * @param string $var The name of the $modSetting key you want to retrieve
	 * @param mixed $default The default value used if the setting doesn't exists.
	 * @access public
	 * @return mixed|boolean
	 */
	public function setting($var, $default = false)
	{
		global $modSettings;

		if (true == $this->enable($var))
			return $modSettings[$this->name .'_'. $var];

		else
			return $default;
	}

	/**
	 * Returns the actual value of a generic $modSetting var
	 * Useful to check external $modSettings vars
	 * If no setting exists and a default value has been defined, the default value is returned.
	 * This is a shortcut for cases like: $var = !empty($modSettings['foo']) ? $modSettings['foo'] : 'baz';
	 * @param string $var The name of the $modSetting key you want to retrieve
	 * @param mixed $default The default value used if the setting doesn't exists.
	 * @access public
	 * @return mixed|boolean
	 */
	public function modSetting($var, $default = false)
	{
		global $modSettings;

		if (isset($modSettings[$var]))
			return $modSettings[$var];

		else
			return $default;
	}

	/**
	 * Creates a new SMF action.
	 * Uses config('actions') to determinate the name and file for the action, if no data is given, {@link $name} will be used
	 * @access public
	 * @param array $actions An array containing all current registered SMF actions at the moment of this method execution
	 * @return void
	 */
	public function addActions(&$actions)
	{
		// This needs to be set and extended by someone else!
		$hooks = $this['config']->get('availableHooks');
		$oActions = $this['config']->get('actions');
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
		$hooks = $this['config']->get('availableHooks');

		if (!$hooks['credits'])
			return;

		$context['copyrights']['mods'][] = is_string($hooks['credits']) ? $this->text($hooks['credits']) : $this->text('modCredits');
	}

	/**
	 * Add a set of config vars.
	 * This is a much smaller approach than addAdminArea, its designed to use the integrate_general_mod_settings hook and adds a very simple set of config vars. Useful for small mods that doesn't have a lot of settings.
	 * Uses $config['simpleSettings'] to determinate the number of settings to add.
	 * Mods can still overwrite this method to add more complex settings.
	 * @access public
	 * @return void
	 */
	public function addSimpleSettings(&$config_vars)
	{
		// This needs to be set and extended by someone else!
		$hooks = $this['config']->get('availableHooks');

		if (!$hooks['simpleSettings'])
			return;

		$sSettings = $this['config']->get('simpleSettings');

		if (!empty($sSettings) && is_array($sSettings))
			foreach ($sSettings as $s)
			{
				// Empty value means adding an "HR" tag.
				if (empty($s))
					$config_vars[] = '';

				// A string value means a "title".
				elseif (is_string($s))
					$config_vars[] = $this->text($s);

				// The rest.
				else
					$config_vars[] = array($s['type'], $this->name .'_'. $s['name'], 'subtext' => $this->text($s['name'] .'_sub'));
			}
	}

	public function addPermissions(&$permissionGroups, &$permissionList)
	{
		// This needs to be set and extended by someone else!
		$hooks = $this['config']->get('availableHooks');

		if (!$hooks['permissions'])
			return;

		$customPerm = $this['config']->get('permissions');
		$identifier = $customPerm['identifier'] ? $customPerm['identifier'] : $this->name;
		$langFile = $customPerm['langFile'] ? $customPerm['langFile'] : $this->name;

		// We gotta load our language file.
		loadLanguage($langFile);

		$permissionGroups['membergroup']['simple'] = array($identifier .'_per_simple');
		$permissionGroups['membergroup']['classic'] = array($identifier .'_per_classic');

		if (!empty($customPerm['perms']) && is_array($customPerm['perms']))
			foreach ($customPerm['perms'] as $p)
				$permissionList['membergroup'][$identifier .'_'. $p] = array(
				false,
				$identifier .'_per_classic',
				$identifier .'_per_simple');
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
		$hooks = $this['config']->get('availableHooks');

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
