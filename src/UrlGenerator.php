<?php


use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class UrlGenerator
 * Append jwt param from query
 * @author Aleksandr Arofikin <sashaaro@gmail.com>
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /** @var UrlGeneratorInterface */
    protected $urlGenerator;
    /** @var RequestStack */
    protected $requestStack;

    protected $jwtRouteExceptions = ['logout'];

    public function __construct(UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $this->attemptAppendJWTToken($name, $parameters);

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    private function attemptAppendJWTToken($name, &$parameters)
    {
        $masterRequest = $this->requestStack->getMasterRequest();
        if ($masterRequest && $masterRequest->query->has('jwt') && !in_array($name, $this->jwtRouteExceptions, true)) {
            $parameters['jwt'] = $masterRequest->query->get('jwt');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->urlGenerator->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->urlGenerator->getContext();
    }

}