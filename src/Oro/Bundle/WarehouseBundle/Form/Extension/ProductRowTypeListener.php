<?php

namespace Oro\Bundle\WarehouseBundle\Form\Extension;

use Oro\Bundle\ProductBundle\Event\FormTypeConfigureOptionsEvent;
use Oro\Bundle\WarehouseBundle\Validator\Constraints\ProductRowQuantity;

class ProductRowTypeListener
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

        $event->setOption('constraints', array_merge($constraints, [new ProductRowQuantity()]));
    }
}
