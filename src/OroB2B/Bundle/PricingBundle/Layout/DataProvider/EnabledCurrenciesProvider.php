<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class EnabledCurrenciesProvider implements DataProviderInterface
{
    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @param UserCurrencyManager $userCurrencyManager
     */
    public function __construct(UserCurrencyManager $userCurrencyManager)
    {
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_pricing_enabled_currencies';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->userCurrencyManager->getAvailableCurrencies();
    }
}
