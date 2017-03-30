<?php

define('ROOT', __DIR__);
define('SMF', true);

// mock globals used by SMF
global $sourcedir, $scripturl, $modSettings;
global $boarddir, $boardurl, $context, $txt;

// Mock functions
function loadLanguage($template_name){}
function log_error(){}

$sourcedir = $scripturl = $boarddir = $boardurl = ROOT;

// Mock some SMF arrays.
$context = array(
	'session_var' => 'foo',
	'session_id' => 'baz',
);
$modSettings = array(
	'CompressedOutput' => false,
);

// Composer-Autoloader
require_once "vendor/autoload.php";

// And another require.
require_once "src/Suki/autoload.php";

