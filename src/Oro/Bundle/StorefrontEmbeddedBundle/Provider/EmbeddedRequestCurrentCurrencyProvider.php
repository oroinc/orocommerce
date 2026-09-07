<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\CurrentCurrencyProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Serves the storefront currency from the X-Currency header on an embedded fragment request.
 */
class EmbeddedRequestCurrentCurrencyProvider implements CurrentCurrencyProviderInterface
{
    private const string CURRENCY_HEADER = 'X-Currency';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
        private readonly UserCurrencyManager $userCurrencyManager,
    ) {
    }

    #[\Override]
    public function getCurrentCurrency(): ?string
    {
        if (!$this->embeddedRequestChecker->rendersFragment()) {
            return null;
        }

        $currency = $this->requestStack->getMainRequest()?->headers->get(self::CURRENCY_HEADER);
        if (!$currency) {
            return null;
        }

        if (!\in_array($currency, $this->userCurrencyManager->getAvailableCurrencies(), true)) {
            return null;
        }

        return $currency;
    }
}
