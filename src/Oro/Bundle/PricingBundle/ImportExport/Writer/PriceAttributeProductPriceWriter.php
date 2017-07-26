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

    /**
     * @param PriceAttributeProductPrice $price
     *
     * @return bool
     */
    private function priceShouldBeDeleted(PriceAttributeProductPrice $price): bool
    {
        return $price->getId() !== null && $price->getPrice() === null;
    }

    /**
     * @param PriceAttributeProductPrice $price
     *
     * @return bool
     */
    private function priceShouldBeSaved(PriceAttributeProductPrice $price): bool
    {
        return $price->getPrice() !== null;
    }
}
