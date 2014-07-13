Ohara
=====

A helper class to be used by SMF modifications (Mods).

#### To be able to use this helper class you need to follow some requirements:

- Needs PHP 5.3 or higher. SMF 2.1.
- Include it or require it on your own file. You can also use composer:

 ```json
"require": {
		"suki/ohara": "dev-master",
	}
 ```

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

- Optionally, you can call $this->setRegistry() on your __construct method to register your class and make it available for other classes extending Ohara.


- All your settings ($modSettings) and text strings ($txt) must follow the same pattern:

 ```php
	$txt['Mod_something'];
	$modSetting['Mod_something'];
 ```

Where Mod is the actual name of your class (Ohara uses magic constant __CLASS__ rather than __METHOD__).

To be able to use a setting you simply call $this->setting('something'); or $this->text('something'); for a text string.

Property data ($this->data('value')) sanitizes the called value, if it doesn't exists on the $_REQUEST global it returns false. Works with arrays too.
