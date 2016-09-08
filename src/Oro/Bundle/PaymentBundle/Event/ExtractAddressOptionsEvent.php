<?php

namespace Oro\Bundle\PaymentBundle\Event;

class ExtractAddressOptionsEvent extends AbstractExtractOptionsEvent
{
    const NAME = 'oro_payment.event.extract_address_options';

    /** @var object */
    protected $entity;

    /**
     * @param object $addressEntity
     * @param array $keys
     */
    public function __construct($addressEntity, array $keys)
    {
        $this->entity = $addressEntity;
        $this->keys = $keys;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
