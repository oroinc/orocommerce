<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRenamingEvent;

/**
 * Handles {@see MethodRenamingEvent} to update shipping method configurations.
 *
 * This listener updates all shipping method configurations that reference the old method identifier
 * when a shipping method is renamed, ensuring configuration consistency across the system.
 */
class MethodRenamingListener
{
    /**
     * @var ShippingMethodConfigRepository
     */
    private $shippingMethodConfigRepository;

    public function __construct(ShippingMethodConfigRepository $shippingMethodConfigRepository)
    {
        $this->shippingMethodConfigRepository = $shippingMethodConfigRepository;
    }

    public function onMethodRename(MethodRenamingEvent $event)
    {
        $this->updateRuleConfigs($event->getOldMethodIdentifier(), $event->getNewMethodIdentifier());
    }

    /**
     * @param string $oldId
     * @param string $newId
     */
    private function updateRuleConfigs($oldId, $newId)
    {
        $configs = $this->shippingMethodConfigRepository->findByMethod($oldId);
        foreach ($configs as $config) {
            $config->setMethod($newId);
        }
    }
}
