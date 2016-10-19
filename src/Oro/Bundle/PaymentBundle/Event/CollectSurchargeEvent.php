<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\PaymentBundle\Model\Surcharge;

class CollectSurchargeEvent extends Event
{
    const NAME = 'oro_payment.event.collect_surcharge';

    /** @var object */
    private $entity;

    /** @var Surcharge */
    private $surchargeModel;

    /**
     * @param object $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
        $this->surchargeModel = new Surcharge();
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return Surcharge
     */
    public function getSurchargeModel()
    {
        return $this->surchargeModel;
    }
}
