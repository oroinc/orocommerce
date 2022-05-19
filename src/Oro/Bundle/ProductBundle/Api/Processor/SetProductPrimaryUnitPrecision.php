<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Under normal conditions, entity dependencies are passed as relation between objects and they correspond
 * to the metadata described in the entity schema.
 * In this case, we have additional processing for the dependent entity.
 * Therefore, we cannot guarantee that the reference to the dependent entity will be correct
 * as the entity may not be initiated (form data are not mapped to the entity).
 * We forcibly check whether these form have been sent and re-resolve the dependence on the product entity.
 */
class SetProductPrimaryUnitPrecision implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $form = $context->getForm();

        /** @var ProductUnitPrecision $productUnitPrecision */
        $productUnitPrecision = $form->getData();
        $product = $productUnitPrecision->getProduct();
        if (null !== $product && null === $product->getPrimaryUnitPrecision()) {
            $product->setPrimaryUnitPrecision($productUnitPrecision);
        }
    }
}
