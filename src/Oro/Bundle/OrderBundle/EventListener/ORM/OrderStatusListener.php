<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;

/**
 * Sets the internal order status if it is not set yet.
 */
class OrderStatusListener
{
    private OrderConfigurationProviderInterface $configurationProvider;
    private ManagerRegistry $doctrine;

    public function __construct(OrderConfigurationProviderInterface $configurationProvider, ManagerRegistry $doctrine)
    {
        $this->configurationProvider = $configurationProvider;
        $this->doctrine = $doctrine;
    }

    public function prePersist(Order $entity): void
    {
        if (null === $entity->getInternalStatus()) {
            $defaultInternalStatusId = $this->configurationProvider->getNewOrderInternalStatus($entity);
            if ($defaultInternalStatusId) {
                $entity->setInternalStatus(
                    $this->doctrine->getRepository(EnumOption::class)
                        ->find($defaultInternalStatusId)
                );
            }
        }
    }
}
