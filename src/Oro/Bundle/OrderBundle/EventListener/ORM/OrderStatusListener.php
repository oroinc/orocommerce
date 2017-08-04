<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderStatusListener
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ConfigManager $configManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ConfigManager $configManager, ManagerRegistry $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    /**
     * @param Order $entity
     * @param LifecycleEventArgs $args
     */
    public function prePersist(Order $entity, LifecycleEventArgs $args)
    {
        if (!$entity->getInternalStatus()) {
            $changeSet = [
                'internal_status' => [null, $this->getDefaultStatus()],
            ];

            $args->getEntityManager()->getUnitOfWork()->scheduleExtraUpdate($entity, $changeSet);
        }
    }

    /**
     * @return object|AbstractEnumValue
     */
    protected function getDefaultStatus()
    {
        $statusId = $this->configManager->get('oro_order.order_creation_new_internal_order_status');
        $className = ExtendHelper::buildEnumValueClassName(Order::INTERNAL_STATUS_CODE);

        return $this->registry->getManagerForClass($className)->getRepository($className)->find($statusId);
    }
}
