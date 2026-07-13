<?php

// Router for PHP's built-in dev server (php -S) - lets real files under www/
// (css/js/images) be served as-is, falling through to the Nette front
// controller for everything else. Not used by the Apache/.htaccess deploy
// path (see www/.htaccess), only for local/Docker dev.

$docroot = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && file_exists($docroot . $path)) {
	return false;
}
require $docroot . '/index.php';
