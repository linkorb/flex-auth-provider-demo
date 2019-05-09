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


$app->register(new \Silex\Provider\SessionServiceProvider());
$app->register(new \FlexAuthProvider\FlexAuthProvider());

$app['security.user_provider.main'] = function ($app) {
    return $app['flex_auth.security.user_provider'];
};

$cacheFolderPath = realpath('./../var/cache');

$app['flex_type_file'] = $cacheFolderPath . '/flex_type';

$app['parameters.memory_users'] = 'alice:4l1c3:ROLE_ADMIN;ROLE_EXAMPLE,bob:b0b:ROLE_EXAMPLE';

$app['flex_auth.type_provider'] = function ($app) {
    return new \FlexAuth\FlexAuthTypeCallbackProvider(function() use($app) {
        /** @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
        $requestStack = $app['request_stack'];
        $request = $requestStack->getMasterRequest();
        $type = is_file($app['flex_type_file']) && is_readable($app['flex_type_file']) ? file_get_contents($app['flex_type_file']) : null;

        if ($type === 'jwt') {
            $certFolderPath = './../config/cert';
            $type = 'jwt?private_key=@'. $certFolderPath .'/jwtRS256.key&public_key=@'. $certFolderPath .'/jwtRS256.key.pub&algo=RS256';
        } else {
            $type = "memory?users=" . $app['parameters.memory_users'];
        }

        return \FlexAuth\FlexAuthTypeProviderFactory::resolveParamsFromLine($type);
    });
};


$app['app.access_denied'] = function ($app) {
    return new AccessDeniedHandler($app['url_generator']);
};

$app['flex_auth.jwt.redirect_login_page'] = '/login';

$app['security.access_denied_handler.main'] = function ($app) {
    return $app['app.access_denied'];
};

$app->register(new Silex\Provider\SecurityServiceProvider(), [
    'security.firewalls' => [
        'main' => [
            # https://silex.symfony.com/doc/2.0/cookbook/guard_authentication.html
            'guard' => [
                'authenticators' => [
                    'flex_auth.type.jwt.security.authenticator'
                ],
            ],
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
        ],
    ],
    'security.access_rules' => [
        ['^/cabinet', \Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter::IS_AUTHENTICATED_FULLY]
    ]
]);

// uncommented if need keep jwt token in query for every links
/* $app->extend('url_generator', function ($urlGenerator, $app) {
    return new UrlGenerator($urlGenerator, $app['request_stack']);
});*/

$app['security.default_encoder'] = function ($app) {
    return new \Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder();
};

return $app;
