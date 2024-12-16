<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\SignInTargetPathProviderInterface;
use Oro\Bundle\SecurityBundle\Util\SameSiteUrlHelper;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Provides URL to the checkout page after login on checkout page.
 */
class SignInTargetPathProvider implements SignInTargetPathProviderInterface
{
    private string $checkoutRoute = 'oro_checkout_frontend_checkout';

    public function __construct(
        private SignInTargetPathProviderInterface $innerProvider,
        private ConfigManager $configManager,
        private SameSiteUrlHelper $sameSiteUrlHelper,
        private UrlMatcherInterface $urlMatcher
    ) {
    }

    public function setCheckoutRoute(string $checkoutRoute): void
    {
        $this->checkoutRoute = $checkoutRoute;
    }

    public function getTargetPath(): ?string
    {
        $referer = $this->sameSiteUrlHelper->getSameSiteReferer();

        if (!$this->isDoNotLeaveCheckout() || !$referer) {
            return $this->innerProvider->getTargetPath();
        }

        $urlPath = parse_url($referer, PHP_URL_PATH);
        try {
            $routeInfo = $this->urlMatcher->match($urlPath);
        } catch (ResourceNotFoundException) {
            // Route not found.
        }

        if (($routeInfo['_route'] ?? null) === $this->checkoutRoute) {
            return $referer;
        } else {
            return $this->innerProvider->getTargetPath();
        }
    }

    private function isDoNotLeaveCheckout(): bool
    {
        return (bool)$this->configManager->get(Configuration::getConfigKey(Configuration::DO_NOT_LEAVE_CHECKOUT));
    }
}
