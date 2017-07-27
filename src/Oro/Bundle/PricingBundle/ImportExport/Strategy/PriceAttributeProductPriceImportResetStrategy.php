<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;
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
    protected function afterProcessEntity($entity)
    {
        $priceList = $entity->getPriceList();
        if (!$priceList || $priceList->getId() === null) {
            return parent::afterProcessEntity($entity);
        }

        if ($this->isPriceListProcessed($priceList)) {
            return parent::afterProcessEntity($entity);
        }

        $this->deletePricesByPriceList($priceList);

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param BasePriceList $priceList
     *
     * @return bool
     */
    protected function isPriceListProcessed(BasePriceList $priceList): bool
    {
        return in_array($priceList->getId(), $this->processedPriceLists, true);
    }

    /**
     * @param BasePriceList $priceList
     */
    protected function deletePricesByPriceList(BasePriceList $priceList)
    {
        $deletedCount = $this->getPriceAttributeProductPriceRepository()->deletePricesByPriceList($priceList);

        $this->context->incrementDeleteCount($deletedCount);
        $this->processedPriceLists[] = $priceList->getId();
    }

    /**
     * @return PriceAttributeProductPriceRepository
     */
    protected function getPriceAttributeProductPriceRepository(): PriceAttributeProductPriceRepository
    {
        return $this->doctrineHelper
            ->getEntityManager(PriceAttributeProductPrice::class)
            ->getRepository(PriceAttributeProductPrice::class);
    }
}
