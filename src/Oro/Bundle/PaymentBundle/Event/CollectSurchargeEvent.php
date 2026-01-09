<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched to collect surcharge amounts (shipping, handling, discount, insurance) for an entity.
 *
 * This event allows listeners to calculate and add surcharge amounts to a surcharge model
 * based on the entity being processed, enabling flexible and extensible surcharge calculation
 * for various payment scenarios.
 */
class CollectSurchargeEvent extends Event
{
    public const NAME = 'oro_payment.event.collect_surcharge';

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
