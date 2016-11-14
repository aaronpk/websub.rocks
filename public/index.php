<?php
chdir('..');
include('vendor/autoload.php');

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$container = new League\Container\Container;
$container->share('response', Zend\Diactoros\Response::class);
$container->share('request', function () {
  return Zend\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
  );
});
$container->share('emitter', Zend\Diactoros\Response\SapiEmitter::class);

$route = new League\Route\RouteCollection($container);

$route->map('GET', '/', 'App\\Controller::index');

$route->map('POST', '/auth/start', 'App\\Auth::start');
$route->map('GET', '/auth/code', 'App\\Auth::code');
$route->map('GET', '/auth/signout', 'App\\Auth::signout');

$route->map('GET', '/dashboard', 'App\\Controller::dashboard');

$route->map('GET', '/publisher', 'App\\Publisher::index');
$route->map('POST', '/publisher/discover', 'App\\Publisher::discover');
$route->map('POST', '/publisher/subscribe', 'App\\Publisher::subscribe');

$route->map('GET', '/publisher/status', 'App\\Publisher::subscription_status');
$route->map('GET', '/publisher/callback', 'App\\Publisher::callback_verify');
$route->map('POST', '/publisher/callback', 'App\\Publisher::callback_deliver');

$route->map('GET', '/subscriber', 'App\\Subscriber::index');
$route->map('POST', '/subscriber/{num}/{token}/publish', 'App\\Subscriber::publish');
$route->map('POST', '/blog/{num}/{token}/hub', 'App\\Subscriber::hub');
$route->map('HEAD', '/blog/{num}/{token}', 'App\\Subscriber::head_feed');
$route->map('GET', '/blog/{num}/{token}', 'App\\Subscriber::get_feed');
$route->map('GET', '/subscriber/{num}', 'App\\Subscriber::get_test');

$route->map('GET', '/hub', 'App\\Hub::index');




$route->map('GET', '/image', 'ImageProxy::image');

$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

try {
  $response = $route->dispatch($container->get('request'), $container->get('response'));
  $container->get('emitter')->emit($response);
} catch(League\Route\Http\Exception\NotFoundException $e) {
  $response = $container->get('response');
  $response->getBody()->write("Not Found\n");
  $container->get('emitter')->emit($response->withStatus(404));
} catch(League\Route\Http\Exception\MethodNotAllowedException $e) {
  $response = $container->get('response');
  $response->getBody()->write("Method not allowed\n");
  $container->get('emitter')->emit($response->withStatus(405));
}
