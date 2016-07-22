<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class DefaultCurrencyProvider
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
    public function getData(ContextInterface $context)
    {
        return $this->userCurrencyManager->getDefaultCurrency();
    }
}
