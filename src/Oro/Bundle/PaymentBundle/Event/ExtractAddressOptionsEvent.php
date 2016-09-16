<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;

class ExtractAddressOptionsEvent extends Event
{
    const NAME = 'oro_payment.event.extract_address_options';

    /** @var object */
    protected $entity;

    /** @var AddressOptionModel */
    protected $model;

    /**
     * @param object $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return object
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
        return ($this->model ?: new AddressOptionModel());
    }

    /**
     * @param AddressOptionModel $model
     */
    public function setModel(AddressOptionModel $model)
    {
        $this->model = $model;
    }
}
