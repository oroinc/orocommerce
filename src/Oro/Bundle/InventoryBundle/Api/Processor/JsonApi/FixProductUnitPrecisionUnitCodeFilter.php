<?php

namespace Oro\Bundle\InventoryBundle\Api\Processor\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Replaces the filter "productUnitPrecision.unit.code" with "productUnitPrecision.unit.id".
 * This is required to avoid BC break and will be removed in one of the future version.
 * @deprecated since 1.1
 */
class FixProductUnitPrecisionUnitCodeFilter implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $filterValues = $context->getFilterValues();
        $filterValue = $filterValues->get('filter[productUnitPrecision.unit.code]');
        if (null !== $filterValue) {
            $filterValues->remove('filter[productUnitPrecision.unit.code]');
            $filterValue->setPath('productUnitPrecision.unit.id');
            $filterValues->set('filter[productUnitPrecision.unit.id]', $filterValue);
        }
    }
}
