<?php

class OharaAutoload
{
	private static $loader;

	public static function loadClassLoader($class)
	{
		global $boarddir;

		if ('Composer\Autoload\ClassLoader' === $class) {
			require $boarddir .'/vendor/composer/ClassLoader.php';
		}
	}

	public static function getLoader()
	{
		global $modSettings, $sourcedir, $boarddir;

		if (null !== self::$loader)
			return self::$loader;

		spl_autoload_register(array('OharaAutoload', 'loadClassLoader'), true, true);
		self::$loader = $loader = new \Composer\Autoload\ClassLoader();
		spl_autoload_unregister(array('OharaAutoload', 'loadClassLoader'));

		// Define some of the most commonly used dirs.
		$vendorDir = $boarddir .'/vendor';
		$baseDir = dirname($vendorDir);
		self::$loader = $loader = new ClassLoader();
		$replacements = array(
			'$vendorDir' => $vendorDir,
			'$baseDir' => $baseDir,
			'$boarddir' => $boarddir,
			'$sourcedir' => $sourcedir,
		);

		// Get all mod's autoload preferences.
		$pref = !empty($modSettings['OharaAutoload']) ? smf_json_decode($modSettings['OharaAutoload'], true) : array(
			'namespaces',
			'psr4',
			'classmap',
		);

		// Gotta register our main class.
		$pref['namespaces']['Suki'] = array($sourcedir . '/ohara/src'),

		if (!empty($pref['namespaces']))
			foreach ($pref['namespaces'] as $namespace => $path)
			{
				$path = (array) $path;
				$path[0] = self::parser($path[0], $replacements);
				$loader->set($namespace, $path);
			}

		if (!empty($pref['psr4']))
			foreach ($pref['psr4'] as $namespace => $path)
			{
				$path = (array) $path;
				$path[0] = self::parser($path[0], $replacements);
				$loader->setPsr4($namespace, self::parser($path, $replacements));
			}

		if (!empty($pref['classmap']))
			foreach ($pref['classmap'] as $name => $classMap)
			{
				$that = $__CLASS__;
				$classMap = (array) $classMap;
				$classMap = array_map(function($map) use ($that, $replacements) { return $that::parser($map, $replacements); }, $classMap);
				$path[0] = $this::parser($path[0], $replacements);
				$loader->addClassMap($this::parser($classMap, $replacements));
				unset($that);
			}

		$loader->register(true);

		return $loader;
	}

	public static function parser($text, $replacements = array())
	{
		if (empty($text) || empty($replacements) || !is_array($replacements))
			return '';

		// Split the replacements up into two arrays, for use with str_replace.
		$find = array();
		$replace = array();
		foreach ($replacements as $f => $r)
		{
			$find[] = '{' . $f . '}';
			$replace[] = $r;
		}

		// Do the variable replacements.
		return str_replace($find, $replace, $text);
	}
}