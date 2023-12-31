<?php

session_start();

use App\controller\AdminController;
use App\controller\ApiController;
use App\controller\AuthController;
use App\controller\UserController;
use App\middleware\Auth;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use UMA\DIC\Container;

require './vendor/autoload.php';

/** @var Container $container */
$container = require_once __DIR__ . '/bootstrap.php';

AppFactory::setContainer($container);

$app = AppFactory::create();

// Configure Twig view renderer
$twig = Twig::create('./src/views/', ['cache' => false]);

$app->add(TwigMiddleware::create($app, $twig));

// Protected Routes
$app->group('', function ($app) {
    $app->get('/home', [UserController::class, 'home']);
    $app->get('/dues', [UserController::class, 'dues']);
    $app->get('/issues', [UserController::class, 'issues']);
    $app->post('/issue', [UserController::class, 'issue']);
    $app->get('/issue/archive/{id}', [UserController::class, 'archiveIssue']);
    $app->get('/issue/unarchive/{id}', [UserController::class, 'unArchiveIssue']);

    $app->get('/transaction/{id}', [UserController::class, 'transaction']);
    $app->post('/pay', [UserController::class, 'pay']);
    $app->get('/announcements', [UserController::class, 'announcements']);

    $app->get('/account', [UserController::class, 'accountSettings']);

})->add(new Auth());

$app->group('/admin', function ($app) {
    $app->get('/home', [AdminController::class, 'home']);
    $app->get('/transaction/{id}', [AdminController::class, 'transaction']);
    $app->post('/transaction/reject', [AdminController::class, 'rejectPayment']);
    $app->post('/transaction/approve', [AdminController::class, 'approvePayment']);
    $app->post('/payment-settings', [AdminController::class, 'paymentSettings']);
    $app->get('/payment-map', [AdminController::class, 'paymentMap']);

    $app->post('/announcement', [AdminController::class, 'announcement']);
    $app->post('/add-due', [AdminController::class, 'addDue']);
    
    $app->get('/announcement/edit/{id}', [AdminController::class, 'editAnnouncement']);
    $app->get('/announcement/delete/{id}', [AdminController::class, 'deleteAnnouncement']);
    $app->get('/announcement/post/{id}', [AdminController::class, 'postAnnouncement']);
    $app->get('/announcement/archive/{id}', [AdminController::class, 'archiveAnnouncement']);

    $app->get('/announcements', [AdminController::class, 'announcements']);
    $app->get('/issues', [AdminController::class, 'issues']);
    
})->add(new Auth());

$app->post('/upload', [ApiController::class, 'upload']);
$app->post('/payable-amount', [ApiController::class, 'amount']);

// Public Routes
$app->post('/register', [AuthController::class, 'register']);
$app->get('/test', [UserController::class, 'test']);
$app->post('/login', [AuthController::class, 'login']);

// Return Signup View
$app->get('/register', function (Request $request, Response $response) use ($twig) {
    return $twig->render($response, 'pages/register.html');
});

$app->get('/logout', function (Request $request, Response $response) {
    session_destroy();
    return $response;
});

// Return Login View
$app->get('/login', function (Request $request, Response $response) use ($twig) {
    return $twig->render($response, 'pages/login.html');
});

// Return Login View
$app->get('/admin/announcement', function (Request $request, Response $response) use ($twig) {
    return $twig->render($response, 'pages/admin-announcement.html');
});

$app->run();