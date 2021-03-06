Ohara  [![Build Status](https://travis-ci.org/MissAllSunday/Ohara.svg?branch=master)](https://travis-ci.org/MissAllSunday/Ohara)
=====

A helper class to be used by SMF modifications (Mods).

#### To be able to use this helper class you need to follow some requirements:

- Needs PHP 7.0 or higher. SMF 2.1.
- Include it or require it on your own file. You can also use composer:

 ```command
$ composer require suki/ohara
 ```

  - Ohara uses [composer/installers](https://github.com/composer/installers) this means the class will be automatically placed inside SMF's Sources folder unless you overwrite it on your own composer.json file.


To use this helper simply extend the parent class Ohara using the Suki\Ohara namespace:

 ```php
class YourClass extends Suki\Ohara
{
...
 ```

- You need to define the $name property, ideally from a declaration:

  ```php
public static $name = CLASS;
 ```

$name is the unique identifier for your main class. Can be any name but usually "__CLASS__" fits quite well.

- You need to call $this->setRegistry(), ideally on your construct method to register your class, have access to several SMF's global variables, create Pimple's service and execute any "on-the-fly" hook declarations.

 Thats it, you now have all the power of Ohara. All services are stored as keys in your $this var:

 $parsedText = $this['tools']->parser($text, $replacements);

- The class can largely be used as it is but some methods expects info provided by the mod author.

- All your settings ($modSettings) and text strings ($txt) must follow the same pattern:

 ```php
	$txt['Mod_something'];
	$modSetting['Mod_something'];
 ```

Where Mod is whatever you used in your $name property.

To be able to use a setting you simply call $this->setting('something'); or $this->text('something'); for a text string. Both values return false if there isn't a $modSetting or $txt associated with it.

$this->setting('something') also accepts a second argument to provide a fallback incase the $modSetting doesn't exists:

$something = $this->setting('something', 'not found!');

- Ohara is named after One Piece island: [Ohara](http://onepiece.wikia.com/wiki/Ohara).
