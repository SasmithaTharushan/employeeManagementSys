<?php
/**
 * Router for PHP's built-in web server, used on Hugging Face Spaces and for local development:
 *   php -S localhost:8000 router.php
 *
 * The built-in server ignores .htaccess, so this blocks the folders and files
 * that must never be served (database file, internal code, config, dotfiles).
 */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

$blocked = '#(^|/)\.'                                        // dotfiles: .git, .htaccess, .env
         . '|^/(includes|database|tests|docs|deploy)(/|$)'   // internal folders + SQLite file
         . '|^/(config|config\.local|router)\.php$#i';

if (preg_match($blocked, $path)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Not found';
    return true;
}

return false; // let the built-in server handle everything else
