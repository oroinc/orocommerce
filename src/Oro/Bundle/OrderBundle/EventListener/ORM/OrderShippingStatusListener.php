<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;

/**
 * Sets the order shipping status if it is not set yet.
 */
class OrderShippingStatusListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public function prePersist(Order $entity): void
    {
        if ($this->enabled && null === $entity->getShippingStatus()) {
            $defaultValues = $this->doctrine->getRepository(EnumOption::class)
                ->getDefaultValues(Order::SHIPPING_STATUS_CODE);
            if ($defaultValues) {
                $entity->setShippingStatus(reset($defaultValues));
            }
        }
    }
}
