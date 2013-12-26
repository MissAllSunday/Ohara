Ohara
=====

A helper class to be used by SMF modifications (Mods).

####To be able to use this helper class you need to follow some requirements:

- Needs PHP 5.3 or higher.
- Include it or require it on your own file.
- Need to load your file wherever you're gonna need it, either via a hook or some other method.
- Extend the parent class Ohara.
- Set the hooks you're gonna use as an array hook_name => method_to_call:

 ```php
	$this->hooks = array(
		'integrate_menu_buttons' => 'call',
		'integrate_general_mod_settings' => 'settings',
	);
 ```

	This will tell Ohara class which method to call depending on the hook.

- All your settings ($modSettings) and text strings ($txt) must follow the same pattern:

 ```php
	$txt['Mod_something'];
	$modSetting['Mod_something'];
 ```

	Where Mod is the actual name of your class (Ohara uses magic constant __CLASS__ rather than __METHOD__).

	To be able to use a setting you simply call $this->setting('something'); or $this->text('something'); for a text string.

	property data ($this->data('value')) sanitizes the called value, if it doesn't exists on the $_REQUEST global it returns false. Works with arrays too.

- If you have helper classes, make sure to set the static var $helpers:

 ```php
	protected static $helpers = array('db', 'tools');
 ```

	The name of your helper class as well as its file should be the name of your main class followed by the name of your tool in camelCase style:

> class ModTools
> ModTools.php

> class ModDb
> ModDb.php

	Set the $folder property to specify a different directory for your helper classes, this folder needs to be a sub dir of Sources directory.

	Ohara class will call and instantiate all your helper classes and will stored them on the main object:

 ```php
	$this->tools->someMethod();
	$this->db->someQuery();
 ```

	The text, setting and data closures will be available on each helper class as long as you set the proper parameter via __construct:

 ```php
	class ModTools
	{
		function __construct($text, $setting, $data)
		{
			$this->text = $text;
			$this->setting = $setting;
			$this->data = $data;
		}

		protected function someMethod()
		{
			// Use text
			return $this->text('something');
		}
	}
 ```

