<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Provides checkoutId from request by _target_path parameter
 */
class CheckoutIdByTargetPathRequestProvider
{
    private string $checkoutRoute = 'oro_checkout_frontend_checkout';

    public function __construct(
        private SameSiteUrlHelper $sameSiteUrlHelper,
        private RouterInterface $router
    ) {
    }

    public function setCheckoutRoute(string $checkoutRoute): void
    {
        $this->checkoutRoute = $checkoutRoute;
    }

    public function getCheckoutId(Request $request): ?int
    {
        $targetPath = $request->request->get('_target_path');
        if (!$targetPath || !\is_string($targetPath)) {
            return null;
        }

        if (!$this->sameSiteUrlHelper->isSameSiteUrl($targetPath, $request)) {
            return null;
        }

        $urlPath = parse_url($targetPath, PHP_URL_PATH);

        try {
            $routeInfo = $this->router->match($urlPath);
        } catch (ResourceNotFoundException) {
            // Route not found.
        }

        if (($routeInfo['_route'] ?? null) !== $this->checkoutRoute) {
            return null;
        }

        return $routeInfo['id'] ?? null;
    }
}
