<?php

namespace Oro\Bundle\WarehouseBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\FormTypeConfigureOptionsEvent;
use Oro\Bundle\WarehouseBundle\Validator\Constraints\ProductQuantityToOrderLimit;

class ProductTypeListener
{
    /**
     * {@inheritdoc}
     */
    public function onConstraintsConfigureOptions(FormTypeConfigureOptionsEvent $event)
    {
        $constraints = ($event->hasOption('constraints')) ? $event->getOption('constraints') : [];

        if (!is_array($constraints)) {
            $constraints = [$constraints];
        }

        $event->setOption('constraints', array_merge($constraints, [new ProductQuantityToOrderLimit()]));
    }
}
