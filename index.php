<?php

use App\controller\UserController;
use App\middleware\Auth;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use UMA\DIC\Container;

require './vendor/autoload.php';

/** @var Container $container */
$container = require_once __DIR__ . '/bootstrap.php';

AppFactory::setContainer($container);

$app = AppFactory::create();

// Configure Twig view renderer
$twig = Twig::create('./src/views/', ['cache' => false]);

$app->add(TwigMiddleware::create($app, $twig));

// Register User
$app->post('/register', [UserController::class, 'register']);

$app->get('/home', [UserController::class, 'home']);
$app->get('/dues', [UserController::class, 'dues']);
$app->get('/transaction/{id}', [UserController::class, 'transaction']);

$app->get('/test', [UserController::class, 'test']);

$app->post('/pay', [UserController::class, 'pay']);

// Return Signup View
$app->get('/register', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'pages/register.html');
});


$app->run();
