<?

define('ROOT', __DIR__);

// mock globals used by SMF
global $sourcedir, $scripturl;
global $boarddir, $boardurl;

$sourcedir = $scripturl = $boarddir = $boardurl = ROOT;

// Composer-Autoloader
require_once "vendor/autoload.php";

// And another require.
require_once "src/Suki/autoload.php";