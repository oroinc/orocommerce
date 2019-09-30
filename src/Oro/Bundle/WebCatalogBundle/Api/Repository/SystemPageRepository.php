<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Repository;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Api\Model\SystemPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * The repository to get system pages.
 */
class SystemPageRepository
{
    /** @var RouterInterface */
    private $router;

    /** @var FrontendHelper */
    private $frontendHelper;

    /**
     * @param RouterInterface $router
     * @param FrontendHelper  $frontendHelper
     */
    public function __construct(
        RouterInterface $router,
        FrontendHelper $frontendHelper
    ) {
        $this->router = $router;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * Gets a system page by its route if the given route is available on the storefront.
     *
     * @param string $route
     *
     * @return SystemPage|null
     */
    public function findSystemPage(string $route): ?SystemPage
    {
        try {
            $url = $this->getUrl($route);
        } catch (RoutingException $e) {
            return null;
        }

        return new SystemPage($route, $url);
    }

    /**
     * @param string $route
     *
     * @return string
     *
     * @throws RoutingException if the URL cannot be retrieved
     */
    private function getUrl(string $route): string
    {
        $pathinfo = $this->getPathInfo($this->router->generate($route));
        if (!$this->isFrontendUrl($pathinfo)) {
            throw new RouteNotFoundException(sprintf(
                'The route "%s" is not allowed on the storefront.',
                $route
            ));
        }

        return $pathinfo;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function getPathInfo(string $url): string
    {
        $baseUrl = $this->router->getContext()->getBaseUrl();
        if ($baseUrl && 0 === strpos($url, $baseUrl)) {
            $url = substr($url, strlen($baseUrl));
        }

        return $url;
    }

    /**
     * @param string $pathinfo
     *
     * @return bool
     */
    private function isFrontendUrl(string $pathinfo): bool
    {
        return
            $this->frontendHelper->isFrontendUrl($pathinfo)
            && $this->isGetMethodAllowed($pathinfo);
    }

    /**
     * @param string $pathinfo
     *
     * @return bool
     */
    private function isGetMethodAllowed(string $pathinfo): bool
    {
        try {
            $this->matchUrlWithGetMethod($pathinfo);
        } catch (RoutingException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $pathinfo
     */
    private function matchUrlWithGetMethod(string $pathinfo): void
    {
        $context = $this->router->getContext();
        $originalMethod = $context->getMethod();
        $originalPathinfo = $context->getPathInfo();
        $context->setMethod(Request::METHOD_GET);
        $context->setPathInfo($pathinfo);
        try {
            $this->router->match($pathinfo);
        } finally {
            $context->setMethod($originalMethod);
            $context->setPathInfo($originalPathinfo);
        }
    }
}
