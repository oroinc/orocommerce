<?php

namespace Oro\Bundle\WebCatalogBundle\Api\Repository;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\WebCatalogBundle\Api\Model\SystemPage;
use Oro\Component\Routing\UrlMatcherUtil;
use Oro\Component\Routing\UrlUtil;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * The repository to get system pages.
 */
class SystemPageRepository
{
    private RouterInterface $router;
    private FrontendHelper $frontendHelper;

    public function __construct(
        RouterInterface $router,
        FrontendHelper $frontendHelper
    ) {
        $this->router = $router;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * Gets a system page by its route if the given route is available on the storefront.
     */
    public function findSystemPage(string $routeName): ?SystemPage
    {
        try {
            $url = $this->getUrl($routeName);
        } catch (RoutingException) {
            return null;
        }

        return new SystemPage($routeName, $url);
    }

    /**
     * @throws RoutingException if the URL cannot be retrieved
     */
    private function getUrl(string $routeName): string
    {
        $url = $this->router->generate($routeName);
        if (!$this->isFrontendUrl(UrlUtil::getPathInfo($url, $this->router->getContext()->getBaseUrl()))) {
            throw new RouteNotFoundException(sprintf(
                'The route "%s" is not allowed on the storefront.',
                $routeName
            ));
        }

        return $url;
    }

    private function isFrontendUrl(string $pathInfo): bool
    {
        return
            $this->frontendHelper->isFrontendUrl($pathInfo)
            && $this->isGetMethodAllowed($pathInfo);
    }

    private function isGetMethodAllowed(string $pathInfo): bool
    {
        try {
            UrlMatcherUtil::matchForGetMethod($pathInfo, $this->router);
        } catch (RoutingException) {
            return false;
        }

        return true;
    }
}
