<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Adds "tierPrices" to the quote entry point data.
 */
class QuoteProductTierPricesQuoteEventListener
{
    public const TIER_PRICES_KEY = 'tierPrices';

    private QuoteProductPricesProvider $quoteProductPricesProvider;
    private ?AuthorizationCheckerInterface $authorizationChecker = null;

    public function __construct(
        QuoteProductPricesProvider $quoteProductPricesProvider
    ) {
        $this->quoteProductPricesProvider = $quoteProductPricesProvider;
    }

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): void
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function onQuoteEvent(QuoteEvent $event): void
    {
        if ($this->authorizationChecker !== null
            && !$this->authorizationChecker->isGranted(
                BasicPermission::VIEW,
                sprintf('entity:%s', ProductPrice::class)
            )
        ) {
            $event->getData()->offsetSet(self::TIER_PRICES_KEY, []);

            return;
        }

        $quote = $event->getQuote();

        $event->getData()->offsetSet(
            self::TIER_PRICES_KEY,
            $this->quoteProductPricesProvider->getProductLineItemsTierPrices($quote)
        );
    }
}
