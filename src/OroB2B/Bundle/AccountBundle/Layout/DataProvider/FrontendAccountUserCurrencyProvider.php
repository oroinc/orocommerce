<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\AccountBundle\Provider\UserCurrencyProvider as CurrencyProvider;

class FrontendAccountUserCurrencyProvider implements DataProviderInterface
{
    /**
     * @var CurrencyProvider
     */
    protected $userCurrencyProvider;

    /**
     * @param CurrencyProvider $userCurrencyProvider
     */
    public function __construct(CurrencyProvider $userCurrencyProvider)
    {
        $this->userCurrencyProvider = $userCurrencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_account_frontend_account_user_currency';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->userCurrencyProvider->getUserCurrency();
    }
}
