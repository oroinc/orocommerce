<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;

class DefaultCurrencyProvider implements DataProviderInterface
{
    /**
     * @var UserCurrencyProvider
     */
    protected $userCurrencyProvider;

    /**
     * @param UserCurrencyProvider $userCurrencyProvider
     */
    public function __construct(UserCurrencyProvider $userCurrencyProvider)
    {
        $this->userCurrencyProvider = $userCurrencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_pricing_default_currency';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->userCurrencyProvider->getDefaultCurrency();
    }
}
