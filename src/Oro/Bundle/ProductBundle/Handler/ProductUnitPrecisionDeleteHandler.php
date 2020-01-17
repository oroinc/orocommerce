<?php

namespace Oro\Bundle\ProductBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

/**
 * The delete handler for ProductUnitPrecision entity.
 */
class ProductUnitPrecisionDeleteHandler extends DeleteHandler
{
    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        /** @var ProductUnitPrecision $entity */
        $product = $entity->getProduct();
        if (null === $product) {
            return;
        }

        $primaryProductUnitPrecision = $product->getPrimaryUnitPrecision();
        if (null !== $primaryProductUnitPrecision && $primaryProductUnitPrecision->getId() === $entity->getId()) {
            throw new ForbiddenException('The delete operation is forbidden. Reason: primary precision.');
        }

        parent::checkPermissions($entity, $em);
    }
}
