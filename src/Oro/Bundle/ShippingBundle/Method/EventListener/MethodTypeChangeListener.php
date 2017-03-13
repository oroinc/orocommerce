<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;

class MethodTypeChangeListener
{
    /**
     * @var ShippingMethodTypeConfigRepository
     */
    private $methodTypeRepository;

    /**
     * @param ShippingMethodTypeConfigRepository $methodTypeRepository
     */
    public function __construct(ShippingMethodTypeConfigRepository $methodTypeRepository)
    {
        $this->methodTypeRepository = $methodTypeRepository;
    }

    /**
     * @param MethodTypeChangeEvent $event
     */
    public function addErrorTypes(MethodTypeChangeEvent $event)
    {
        $enabledTypes = $this->methodTypeRepository->findEnabledByMethodIdentifier(
            $event->getMethodIdentifier()
        );

        foreach ($enabledTypes as $type) {
            $typeName = $type->getType();

            if (!in_array($typeName, $event->getAvailableTypes(), false)) {
                $event->addErrorType($typeName);
            }
        }
    }
}
