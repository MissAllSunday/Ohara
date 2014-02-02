Ohara
=====

A helper class to be used by SMF modifications (Mods).

####To be able to use this helper class you need to follow some requirements:

- Needs PHP 5.3 or higher.
- Include it or require it on your own file.
- Need to load your file wherever you're gonna need it, either via a hook or some other method.
- Extend the parent class Ohara.
- Call the parent on construct:

 ```php
parent::__construct();
 ```

- All your settings ($modSettings) and text strings ($txt) must follow the same pattern:

 ```php
	$txt['Mod_something'];
	$modSetting['Mod_something'];
 ```

Where Mod is the actual name of your class (Ohara uses magic constant __CLASS__ rather than __METHOD__).

To be able to use a setting you simply call $this->setting('something'); or $this->text('something'); for a text string.

Property data ($this->data('value')) sanitizes the called value, if it doesn't exists on the $_REQUEST global it returns false. Works with arrays too.
