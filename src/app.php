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


$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new FlexAuthProvider());

$app['security.user_provider.main'] = function ($app) {
    return $app['flex_auth.security.user_provider'];
};

$app['flex_auth.type_provider'] = function ($app) {
    return new \FlexAuth\AuthFlexTypeCallbackProvider(function() use($app) {
        /** @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
        $requestStack = $app['request_stack'];
        $request = $requestStack->getMasterRequest();
        $type = $request->getSession()->get('flex_auth_type');

        if ($type === 'jwt') {
            $certFolderPath = './../config/cert';
            $type = 'jwt?private_key=@'. $certFolderPath .'/jwtRS256.key&public_key=@'. $certFolderPath .'/jwtRS256.key.pub&algo=RS256';
        } else {
            $type = 'memory?users=alice:4l1c3:ROLE_ADMIN;ROLE_EXAMPLE,bob:b0b:ROLE_EXAMPLE)';
        }

        return \FlexAuth\AuthFlexTypeProviderFactory::resolveParamsFromLine($type);
    });
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
]);

$app['security.default_encoder'] = function ($app) {
    return new \Symfony\Component\Security\Core\Encoder\PlaintextPasswordEncoder();
};

return $app;
