<?php

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pimple\ServiceProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;

use FlexAuth\Type\Memory\MemoryUserProviderFactory;
use FlexAuth\Type\Entity\EntityUserProviderFactory;
use FlexAuth\Type\UserbaseClient\UserbaseClientUserProviderFactory;
use FlexAuth\Type\JWT\JWTUserProviderFactory;
use FlexAuth\AuthFlexTypeProviderFactory;
use FlexAuth\Type\JWT\JWTTokenAuthenticator;
use FlexAuth\Type\JWT\FlexTypeJWTEncoder;
use FlexAuth\Type\JWT\DefaultJWTUserFactory;

/**
 * Class FlexAuthProvider
 * @author Aleksandr Arofikin <sashaaro@gmail.com>
 */
class FlexAuthProvider implements ServiceProviderInterface, EventListenerProviderInterface, ControllerProviderInterface, BootableProviderInterface
{
    public function boot(\Silex\Application $app)
    {
    }

    public function register(\Pimple\Container $pimple)
    {
        /* Flex auth type registration */

        $pimple['flex_auth.type.'. MemoryUserProviderFactory::TYPE] = function () {
            return new MemoryUserProviderFactory();
        };

        if (isset($pimple['entity_manager'])) {
            $pimple['flex_auth.type.'. EntityUserProviderFactory::TYPE] = function ($app) {
                return new EntityUserProviderFactory($app['entity_manager']);
            };
        }

        if (class_exists(\UserBase\Client\UserProvider::class)) {
            $pimple['flex_auth.type.'. \FlexAuth\Type\UserbaseClient\UserbaseClientUserProviderFactory::TYPE] = function () {
                return new UserbaseClientUserProviderFactory();
            };
        }

        $pimple['flex_auth.type.'. JWTUserProviderFactory::TYPE] = function () {
            return new JWTUserProviderFactory();
        };

        /* Common services */

        $pimple['flex_auth.type_provider'] = function () {
            return AuthFlexTypeProviderFactory::fromEnv('FLEX_AUTH');
        };

        $pimple['flex_auth.user_provider_factory'] = function ($app) {
            $flexAuthUserProviderFactory = new \FlexAuth\UserProviderFactory($app['flex_auth.type_provider']);

            $flexAuthUserProviderFactory->addType(MemoryUserProviderFactory::TYPE, $app['flex_auth.type.'. MemoryUserProviderFactory::TYPE]);
            if (isset($app['flex_auth.type.'. EntityUserProviderFactory::TYPE])) {
                $flexAuthUserProviderFactory->addType(EntityUserProviderFactory::TYPE, $app['flex_auth.type.'. EntityUserProviderFactory::TYPE]);
            }
            if (isset($app['flex_auth.type.'. \FlexAuth\Type\UserbaseClient\UserbaseClientUserProviderFactory::TYPE])) {
                $flexAuthUserProviderFactory->addType(UserbaseClientUserProviderFactory::TYPE, $app['flex_auth.type.'. UserbaseClientUserProviderFactory::TYPE]);
            }
            $flexAuthUserProviderFactory->addType(JWTUserProviderFactory::TYPE, $app['flex_auth.type.'. JWTUserProviderFactory::TYPE]);

            return $flexAuthUserProviderFactory;
        };

        $pimple['flex_auth.security.user_provider'] = function ($app) {
            return new \FlexAuth\Security\FlexUserProvider($app['flex_auth.user_provider_factory']);
        };

        $pimple['flex_auth.type.jwt.user_factory'] = function ($app) {
            return new DefaultJWTUserFactory();
        };

        $pimple['flex_auth.type.jwt.security.authenticator'] = function ($app) {;
            return new JWTTokenAuthenticator(
                $app['flex_auth.type.jwt.user_factory'],
                new FlexTypeJWTEncoder($app['flex_auth.type_provider']),
                $app['flex_auth.type_provider']
            );
        };
    }

    public function subscribe(\Pimple\Container $app, EventDispatcherInterface $dispatcher)
    {

    }


    public function connect(\Silex\Application $app)
    {
    }
}