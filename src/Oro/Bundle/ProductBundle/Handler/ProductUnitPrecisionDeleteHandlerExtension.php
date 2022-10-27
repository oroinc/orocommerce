<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Oro\Bundle\EntityBundle\Handler\AbstractEntityDeleteHandlerExtension;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * The delete handler extension for ProductUnitPrecision entity.
 */
class ProductUnitPrecisionDeleteHandlerExtension extends AbstractEntityDeleteHandlerExtension
{
    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
        /** @var ProductUnitPrecision $entity */
        $product = $entity->getProduct();
        if (null === $product) {
            return;
        }

        $primaryProductUnitPrecision = $product->getPrimaryUnitPrecision();
        if (null !== $primaryProductUnitPrecision && $primaryProductUnitPrecision->getId() === $entity->getId()) {
            throw $this->createAccessDeniedException('primary precision');
        }
    }
}
