<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Writer;

use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductWriter extends EntityWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $entityManager = $this->doctrineHelper->getEntityManager($this->getClassName($items));
        foreach ($items as $item) {
            if ($item instanceof Product) {
                $primary = $item->getPrimaryUnitPrecision();
                if ($item->getSku() !== $primary->getProduct()->getSku()) {
                    $primary->setProduct(null);
                    $item->setPrimaryUnitPrecision($primary);
                }
                $precisions = $item->getUnitPrecisions()->toArray();
                /** @var ProductUnitPrecision $precision */
                foreach ($precisions as $precision) {
                    if ($item->getSku() !== $precision->getProduct()->getSku()) {
                        $precision->setProduct(null);
                        $item->addUnitPrecision($precision);
                    }
                }
            }
            $entityManager->persist($item);
            $this->detachFixer->fixEntityAssociationFields($item, 1);
            $id = $item->getId();
            $entityManager->flush();
            if (null === $id) {
                $entityManager->clear();
            }
        }

        $configuration = $this->getConfig();

        if (empty($configuration[self::SKIP_CLEAR])) {
            $entityManager->clear();
        }
    }
}
