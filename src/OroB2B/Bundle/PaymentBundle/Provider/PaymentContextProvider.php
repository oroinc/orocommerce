<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

class PaymentContextProvider
{
    /**
     * @var AddressExtractor
     */
    protected $addressExtractor;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @param AddressExtractor $addressExtractor
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(AddressExtractor $addressExtractor, UserCurrencyManager $currencyManager)
    {
        $this->addressExtractor = $addressExtractor;
        $this->currencyManager = $currencyManager;
    }

    /**
     * @param mixed $context
     * @param mixed $entity
     * @return array
     */
    public function processContext($context, $entity)
    {
        if (!$context) {
            return [];
        }

        if (!$entity) {
            return [];
        }

        return [
            'entity' => $entity,
            'country' => $this->addressExtractor->getCountryIso2($entity),
            'currency' => $this->currencyManager->getUserCurrency(),
        ];
    }
}
