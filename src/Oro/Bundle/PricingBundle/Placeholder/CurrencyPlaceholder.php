<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;

/**
 * Placeholder for the current user's currency in website search queries.
 *
 * Provides the CURRENCY placeholder value based on the current user's selected currency,
 * enabling currency-aware search indexing and filtering.
 */
class CurrencyPlaceholder extends AbstractPlaceholder
{
    public const NAME = 'CURRENCY';

    /**
     * @var UserCurrencyManager
     */
    private $currencyManager;

    public function __construct(UserCurrencyManager $currencyManager)
    {
        $this->currencyManager = $currencyManager;
    }

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    #[\Override]
    public function getDefaultValue()
    {
        $currency = $this->currencyManager->getUserCurrency();

        if (!$currency) {
            throw new \RuntimeException('Can\'t get current currency');
        }

        return $currency;
    }
}
