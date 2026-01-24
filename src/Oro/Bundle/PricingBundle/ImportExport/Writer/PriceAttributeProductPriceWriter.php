<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;

/**
 * Writes price attribute product prices to the database during import.
 *
 * Handles persistence of price attribute product prices with support for deletion
 * of prices with null values and conditional saving based on price validity.
 */
class PriceAttributeProductPriceWriter extends PersistentBatchWriter
{
    /**
     *
     * @param PriceAttributeProductPrice[] $items
     */
    #[\Override]
    protected function saveItems(array $items, EntityManager $em)
    {
        foreach ($items as $item) {
            if ($this->priceShouldBeDeleted($item)) {
                $em->remove($item);

                continue;
            }

            if ($this->priceShouldBeSaved($item)) {
                $em->persist($item);
            }
        }

        $em->flush();
    }

    private function priceShouldBeDeleted(PriceAttributeProductPrice $price): bool
    {
        return $price->getId() !== null && $price->getPrice() === null;
    }

    private function priceShouldBeSaved(PriceAttributeProductPrice $price): bool
    {
        return $price->getPrice() !== null;
    }
}
