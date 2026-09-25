<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Provider\CurrentCurrencyProviderInterface;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Serves the storefront currency from the X-Currency header on an embedded fragment request.
 */
class EmbeddedRequestCurrentCurrencyProvider implements CurrentCurrencyProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
        private readonly UserCurrencyManager $userCurrencyManager,
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public function getCurrentCurrency(): ?string
    {
        $currency = $this->embeddedRequestChecker->getFragmentRequestHeader(EmbeddedRequestChecker::CURRENCY_HEADER);
        if (!$currency) {
            return null;
        }

        if (!\in_array($currency, $this->userCurrencyManager->getAvailableCurrencies(), true)) {
            $this->logger->debug('Embedded currency header was rejected.', ['currency' => $currency]);

            return null;
        }

        return $currency;
    }
}
