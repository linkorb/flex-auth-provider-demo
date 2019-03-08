<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
});


$app['debug'] = true;


$users = [
    'admin' => ['ROLE_USER', '123']
];

$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\SecurityServiceProvider(), [
    'security.firewalls' => [
        'main' => [
            'form' => [
                'login_path' => '/login',
                'default_target_path' => '/',
                'check_path' => '/login_check'
            ],
            'logout' => [
                'logout_path' => '/logout',
                'target_url' => 'homepage',
                'invalidate_session' => true
            ],
            'anonymous' => true,
            'users' => $users,
        ],
    ],
]);

$app['security.default_encoder'] = function ($app) {
    return new \Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder();
};

return $app;
