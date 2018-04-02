<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;

class OrderStatusListener
{
    /** @var OrderConfigurationProviderInterface */
    protected $configurationProvider;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param OrderConfigurationProviderInterface $configurationProvider
     * @param ManagerRegistry $registry
     */
    public function __construct(OrderConfigurationProviderInterface $configurationProvider, ManagerRegistry $registry)
    {
        $this->configurationProvider = $configurationProvider;
        $this->registry = $registry;
    }

    /**
     * @param Order $entity
     */
    public function prePersist(Order $entity)
    {
        if (!$entity->getInternalStatus()) {
            $statusId = $this->configurationProvider->getNewOrderInternalStatus($entity);
            $entity->setInternalStatus($this->getInternalStatus($statusId));
        }
    }

    /**
     * @param string $statusId
     *
     * @return object|AbstractEnumValue
     */
    protected function getInternalStatus($statusId)
    {
        $className = ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE);

        return $this->registry->getManagerForClass($className)->getRepository($className)->find($statusId);
    }
}
