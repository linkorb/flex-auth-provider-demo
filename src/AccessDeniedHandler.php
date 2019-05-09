<?php


class AccessDeniedHandler implements \Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface
{
    private $urlGenerator;

    public function __construct(\Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }


    public function handle(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\Security\Core\Exception\AccessDeniedException $accessDeniedException)
    {
        $loginUrl = $this->urlGenerator->generate('login');
        return new \Symfony\Component\HttpFoundation\Response('403 Access Denied. <a href="'. $loginUrl .'">Login<a>');
    }
}