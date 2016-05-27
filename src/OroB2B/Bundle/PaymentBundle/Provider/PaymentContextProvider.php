<?php

namespace OroB2B\Bundle\PaymentBundle\Provider;

class PaymentContextProvider
{
    /** @var AddressExtractor */
    protected $addressExtractor;

    /**
     * @param AddressExtractor $addressExtractor
     */
    public function __construct(AddressExtractor $addressExtractor)
    {
        $this->addressExtractor = $addressExtractor;
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
        ];
    }
}
