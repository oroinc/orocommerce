<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;

class CurrencyPlaceholder extends AbstractPlaceholder
{
    const NAME = 'CURRENCY';

    /**
     * @var UserCurrencyManager
     */
    private $currencyManager;

    public function __construct(UserCurrencyManager $currencyManager)
    {
        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        $currency = $this->currencyManager->getUserCurrency();

        if (!$currency) {
            throw new \RuntimeException('Can\'t get current currency');
        }

        return $currency;
    }
}
