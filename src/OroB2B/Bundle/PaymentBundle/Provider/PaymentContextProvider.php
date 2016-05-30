<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;

class PaymentContextProvider
{
    /**
     * @var AddressExtractor
     */
    protected $addressExtractor;

    /**
     * @var UserCurrencyProvider
     */
    protected $currencyProvider;

    /**
     * @param AddressExtractor $addressExtractor
     * @param UserCurrencyProvider $currencyProvider
     */
    public function __construct(AddressExtractor $addressExtractor, UserCurrencyProvider $currencyProvider)
    {
        $this->addressExtractor = $addressExtractor;
        $this->currencyProvider = $currencyProvider;
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
            'currency' => $this->currencyProvider->getUserCurrency(),
        ];
    }
}
