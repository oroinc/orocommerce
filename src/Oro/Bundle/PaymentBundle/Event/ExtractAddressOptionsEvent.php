<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;

class ExtractAddressOptionsEvent extends Event
{
    const NAME = 'oro_payment.event.extract_address_options';

    /** @var AbstractAddress */
    protected $entity;

    /** @var AddressOptionModel */
    protected $model;

    /**
     * @param AbstractAddress $entity
     */
    public function __construct(AbstractAddress $entity)
    {
        $this->entity = $entity;
        $this->model = new AddressOptionModel();
    }

    /**
     * @return AbstractAddress
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return AddressOptionModel
     */
    public function getModel()
    {
        return $this->model;
    }
}
