<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

class PriceAttributeProductPriceWriter extends PersistentBatchWriter
{
    /**
     * {@inheritDoc}
     *
     * @param PriceAttributeProductPrice[] $items
     */
    protected function saveItems(array $items, EntityManager $em)
    {
        foreach ($items as $item) {
            if ($item->getId() !== null && $item->getPrice() === null) {
                $em->remove($item);
            } elseif ($item->getPrice() !== null) {
                $em->persist($item);
            }
        }

        $em->flush();
    }
}
