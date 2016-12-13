Ohara  [![Build Status](https://travis-ci.org/MissAllSunday/Ohara.svg?branch=master)](https://travis-ci.org/MissAllSunday/Ohara)
=====

A helper class to be used by SMF modifications (Mods).

#### To be able to use this helper class you need to follow some requirements:

- Needs PHP 5.3 or higher. SMF 2.1.
- Include it or require it on your own file. You can also use composer:

 ```json
"require": {
		"suki/ohara": "~1.1",
	}
 ```

  - Ohara uses [composer/installers](https://github.com/composer/installers) this means the class will be automatically placed inside SMF's Sources folder unless you overwrite it on your own composer.json file.


- Extend the parent class Ohara using the Suki\Ohara namespace:

 ```php
class YourClass extends Suki\Ohara
{
...
 ```

- You need to define the $name property, ideally from a declaration:

  ```php
public static $name = __CLASS__;
 ```

$name is the unique identifier for your main class. Can be any name but usually __CLASS__ fits quite well.

- You need to call $this->setRegistry(), ideally on your __construct method to register your class, have access to several SMF's global variables, create Pimple's service and execute any "on-the-fly" hook declarations.

  ```php
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
 ```

- The class can largely be used as it is but some methods expects info provided by the mod author.

- All your settings ($modSettings) and text strings ($txt) must follow the same pattern:

 ```php
	$txt['Mod_something'];
	$modSetting['Mod_something'];
 ```

Where Mod is the actual name of your class (Ohara uses magic constant __CLASS__ rather than __METHOD__).

To be able to use a setting you simply call $this->setting('something'); or $this->text('something'); for a text string. Both values return false if there isn't a $modSetting or $txt associated with it.

- Ohara is named after One Piece island: [Ohara](http://onepiece.wikia.com/wiki/Ohara).
