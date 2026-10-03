<?php

declare(strict_types=1);

use Rocks\Router;
use Rocks\Http\HttpException;
use Rocks\Http\Request;
use Rocks\Http\Response;

// When running under `php -S` with this file as the router script, let the
// built-in server handle real files (CSS, JS, images) itself.
if (PHP_SAPI === 'cli-server') {
  $file = __DIR__ . '/' . ltrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
  if ($file !== __DIR__ . '/' && is_file($file)) {
    return false;
  }
}

chdir('..');

if (!is_file('vendor/autoload.php')) {
  http_response_code(500);
  header('Content-Type: text/plain; charset=utf-8');
  echo "Dependencies are not installed. Run:\n\n    composer install\n";
  exit(1);
}

require 'vendor/autoload.php';

$router = new Router;

$router->get('/', [App\Controller::class, 'index']);
$router->get('/implementation-reports', [App\Controller::class, 'implementation_reports']);

$router->post('/auth/start', [App\Auth::class, 'start']);
$router->post('/auth/register/challenge', [App\Auth::class, 'register_challenge']);
$router->post('/auth/register', [App\Auth::class, 'register']);
$router->post('/auth/login/challenge', [App\Auth::class, 'login_challenge']);
$router->post('/auth/login', [App\Auth::class, 'login']);
$router->get('/auth/signout', [App\Auth::class, 'signout']);

$router->get('/account', [App\Account::class, 'index']);
$router->post('/account/passkeys/challenge', [App\Account::class, 'add_challenge']);
$router->post('/account/passkeys', [App\Account::class, 'add']);
$router->post('/account/passkeys/{id}/rename', [App\Account::class, 'rename']);
$router->post('/account/passkeys/{id}/remove', [App\Account::class, 'remove']);

$router->get('/publisher', [App\Publisher::class, 'index']);
$router->post('/publisher/discover', [App\Publisher::class, 'discover']);
$router->post('/publisher/subscribe', [App\Publisher::class, 'subscribe']);

$router->get('/publisher/status', [App\Publisher::class, 'subscription_status']);
$router->get('/publisher/callback', [App\Publisher::class, 'callback_verify']);
$router->post('/publisher/callback', [App\Publisher::class, 'callback_deliver']);

$router->get('/subscriber', [App\Subscriber::class, 'index']);
$router->any('/subscriber/{num}/{token}/publish', [App\Subscriber::class, 'publish']);
$router->post('/blog/{num}/{token}/hub', [App\Subscriber::class, 'hub']);
// HEAD must be registered before GET, since get() also matches HEAD
$router->add(['HEAD'], '/blog/{num}/{token}', [App\Subscriber::class, 'head_feed']);
$router->get('/blog/{num}/{token}', [App\Subscriber::class, 'get_feed']);
$router->get('/subscriber/{num}', [App\Subscriber::class, 'get_test']);

$router->get('/hub', [App\Hub::class, 'index']);
$router->get('/hub/{num}', [App\Hub::class, 'get_test']);

$router->post('/hub/{num}/start', [App\Hub::class, 'post_start']);
$router->post('/hub/{num}/subscribe', [App\Hub::class, 'post_subscribe']);

// The user's hub will communicate with these two
$router->get('/hub/{num}/sub/{token}', [App\Hub::class, 'get_subscriber']);
$router->post('/hub/{num}/sub/{token}', [App\Hub::class, 'post_subscriber']);

// For local topics, the user's hub will fetch the contents here (GET and HEAD)
$router->get('/hub/{num}/pub/{token}', [App\Hub::class, 'get_publisher']);

// The user triggers adding a new post with this route
$router->post('/hub/{num}/pub/{token}', [App\Hub::class, 'post_publisher']);

$request = Request::fromGlobals();

try {
  $match = $router->match($request->method, $request->path);
  [$class, $method] = $match->handler;
  $response = (new $class)->$method($request, $match->params);
} catch (HttpException $e) {
  $response = Response::text($e->getMessage() . "\n", $e->status);
  foreach ($e->headers as $name => $value) {
    $response = $response->withHeader($name, $value);
  }
}

$response->send();
