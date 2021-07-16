<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRenamingEvent;

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
