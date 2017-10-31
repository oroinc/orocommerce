<?php

namespace Oro\Bundle\CheckoutBundle\Model\Updater;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;

class CheckoutShippingMethodUpdater extends AbstractCheckoutUpdater
{
    const SHIPPING_METHOD_ATTRIBUTE = 'shipping_method';
    const SHIPPING_METHOD_TYPE_ATTRIBUTE = 'shipping_method_type';

    /**
     * {@inheritDoc}
     *
     * @param Order $source
     */
    public function update(WorkflowDefinition $workflow, WorkflowData $data, $source)
    {
        if ($source->getShippingMethod() && $source->getShippingMethodType()) {
            $data->set(self::SHIPPING_METHOD_ATTRIBUTE, $source->getShippingMethod());
            $data->set(self::SHIPPING_METHOD_TYPE_ATTRIBUTE, $source->getShippingMethodType());
        }
    }
}
