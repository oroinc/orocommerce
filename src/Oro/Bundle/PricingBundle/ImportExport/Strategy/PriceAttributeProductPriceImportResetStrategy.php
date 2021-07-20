<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;

/**
 * Removes attribute prices only for attributes that are listed in an import file
 */
class PriceAttributeProductPriceImportResetStrategy extends PriceAttributeProductPriceImportStrategy
{
    /**
     * @var int[]
     */
    protected $processedPriceLists = [];

    /**
     * {@inheritDoc}
     */
    protected function beforeProcessEntity($entity)
    {
        if (!$entity instanceof PriceAttributeProductPrice || null === $entity->getPriceList()) {
            return parent::beforeProcessEntity($entity);
        }

        $priceList = $this->getPersistedPriceList($entity->getPriceList());

        if (null === $priceList) {
            return parent::afterProcessEntity($entity);
        }

        if ($this->isPriceListProcessed($priceList)) {
            return parent::afterProcessEntity($entity);
        }

        $this->deletePricesByPriceList($priceList);

        return parent::beforeProcessEntity($entity);
    }

    protected function isPriceListProcessed(BasePriceList $priceList): bool
    {
        return in_array($priceList->getId(), $this->processedPriceLists, true);
    }

    protected function deletePricesByPriceList(BasePriceList $priceList)
    {
        $deletedCount = $this->getPriceAttributeProductPriceRepository()->deletePricesByPriceList($priceList);

        $this->context->incrementDeleteCount($deletedCount);
        $this->processedPriceLists[] = $priceList->getId();
    }

    protected function getPriceAttributeProductPriceRepository(): PriceAttributeProductPriceRepository
    {
        return $this->doctrineHelper
            ->getEntityManager(PriceAttributeProductPrice::class)
            ->getRepository(PriceAttributeProductPrice::class);
    }

    /**
     * Database helper caches the result by criteria so there's no need to worry about request repetition
     *
     * @param BasePriceList $unPersistedPriceList
     *
     * @return null|PriceAttributePriceList
     */
    protected function getPersistedPriceList(BasePriceList $unPersistedPriceList)
    {
        $name = $unPersistedPriceList->getName();

        if (!$name) {
            return null;
        }

        return $this
            ->databaseHelper
            ->findOneBy(PriceAttributePriceList::class, ['name' => $name]);
    }
}
