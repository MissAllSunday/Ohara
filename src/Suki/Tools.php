<?php

/**
 * @package Ohara helper class
 * @version 1.1
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2018, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

namespace Suki;

class Tools
{
	protected $_commaCases = array(
		'numeric' => '\d',
		'alpha' => '[:alpha:]',
		'alphanumeric' => '[:alnum:]',
	);

	public function __construct(Ohara $app)
	{
		$this->_app = $app;
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
		$parsed = [];
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
	public function jsonResponse($data = [])
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

			if ($this->_app->modSetting('CompressedOutput'))
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
	 * @return string
	 */
	public function parser($text, $replacements = []): string
	{
		global $context;

		if (empty($text) || empty($replacements) || !is_array($replacements))
			return '';

		// Split the replacements up into two arrays, for use with str_replace.
		$find = [];
		$replace = [];

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
	 * @param string $delimiter Used for explode/imploding the string.
	 * @return string|bool
	 */
	public function commaSeparated($string, $type = 'alphanumeric', $delimiter = ',')
	{
		if (empty($string))
			return false;

		// This is why we can't have nice things...
		$t = isset($this->_commaCases[$type]) ? $this->_commaCases[$type] : $this->_commaCases['alphanumeric'];

		return empty($string) ? false : implode($delimiter, array_filter(explode($delimiter, preg_replace(
			array(
				'/[^'. $t .',]/',
				'/(?<='. $delimiter .')'. $delimiter .'+/',
				'/^'. $delimiter .'+/',
				'/'. $delimiter .'+$/'
			), '', $string
		))));
	}

	/**
	 * Returns a formatted string.
	 * @access public
	 * @param string|int  $bytes A number of bytes.
	 * @param bool $showUnits To show the unit symbol or not.
	 * @param int  $log the log used, either 1024 or 1000.
	 * @return string
	 */
	public function formatBytes($bytes, $showUnits = false, $log = 1024): string
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB');
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log($log));
		$pow = min($pow, count($units) - 1);
		$bytes /= (1 << (10 * $pow));

		return round($bytes, 2) . ($showUnits ? ' ' . $units[$pow] : '');
	}

	/**
	 * Wrapper function for SMF's redirectexit()
	 * @access public
	 * @param string $url A string, identical to what you would normally pass to redirectexit, IE, no $scripturl.
	 * @param array $options An array of options:
	 * - token string If defined, sets a token using the string given, if no string is provided, uses a generic name created by Suki\Ohara::$name and appending "_re" to it.
	 * - tokenType string createToken needs a type, post, get or request, defaults to get.
	 * - message array Uses Suki\Data::setUpdate() an array with 2 values, the first one array[0] is the message "key", the second array[1] is the "message".
	 * @return void
	 */
	public function redirect($url, $options = [])
	{
		global $context;

		// Why do you even bother?
		if (empty($url))
			return false;

		// Toc toc token?
		if (!empty($options['token']))
		{
			// No name? why?
			$name = !is_string($options['token']) ? ($this->_app->name .'_re') : $options['token'];

			createToken($name, (!empty($options['tokenType']) ? $options['tokenType'] : 'get'));
		}

		// Any messages? uses Suki\Data::setUpdate()
		if (!empty($options['message']) && is_array($options['message']) && isset($options['message'][0]) && isset($options['message'][1]))
			$this->_app['data']->setUpdate($options['message'][0], $options['message'][1]);

		// Finally, set a call to redirectexit, append the session var if available.
		return redirectexit($url .';'. (isset($context['session_var']) ? ($context['session_var'] .'='. $context['session_id']) : ''));
	}
}
