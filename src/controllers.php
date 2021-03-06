<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function (Request $request) use ($app) {
    if ($request->query->get('type')) {
        file_put_contents($app['flex_type_file'], $request->query->get('type'));
        return new RedirectResponse($app['url_generator']->generate('homepage'));
    }

    /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage */
    $tokenStorage = $app['security.token_storage'];
    $user = null;
    $token = $tokenStorage->getToken();
    if ($token) {
        $user = is_object($token->getUser()) ? $token->getUser() : null;
    }

    /** @var \FlexAuth\FlexAuthTypeProviderInterface $typeProvider */
    $typeProvider = $app['flex_auth.type_provider'];

    return $app['twig']->render('index.html.twig', [
        'user' => $user,
        'type' => $typeProvider->provide()['type']
    ]);
})->bind('homepage');


$app->get('/login', function(Request $request) use ($app) {
    /** @var \FlexAuth\FlexAuthTypeProviderInterface $typeProvider */
    $typeProvider = $app['flex_auth.type_provider'];

    return $app['twig']->render('login.twig', [
        'error' => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
        'isJWT' => $typeProvider->provide()['type'] === \FlexAuth\Type\JWT\JWTUserProviderFactory::TYPE,
        'memory_users' => $app['parameters.memory_users']
    ]);
})->bind('login');

$app->post('/api/login', function(Request $request) use ($app) {
    $content = $request->getContent();
    $content = json_decode($content);
    /** @var \FlexAuth\Type\JWT\JWTTokenAuthenticator $JWTTokenAuthenticator */
    $JWTTokenAuthenticator = $app['flex_auth.type.jwt.security.authenticator'];

    // fake authentication without password
    $user = new \Symfony\Component\Security\Core\User\User($content->username, null, ['ROLE_USER']);

    // Authentication and check password should be in hear

    // generate jwt token always for any useranme
    $token = $JWTTokenAuthenticator->createTokenFromUser($user);

    return new JsonResponse(['token' => $token]);
})->bind('api_login');


$app->get('/cabinet', function() {
    return new Response('Personal cabinet page');
})->bind('cabinet');

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if (!$app['debug']) {
        return new Response('Sorry service is not available.');
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
