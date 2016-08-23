<?php

namespace Oro\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

class ProductPriceResetStrategy extends ProductPriceImportStrategy
{
    /**
     * @var array
     */
    protected $processedPriceLists = [];

    /**
     * @param PriceList $entity
     *
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        if ($entity instanceof ProductPrice) {
            $priceList = $entity->getPriceList();
            $identifier = $this->databaseHelper->getIdentifier($priceList);
            if ($identifier && empty($this->processedPriceLists[$identifier])) {
                $recordsToDelete = $this->getProductPriceRepository()->countByPriceList($priceList);
                if ($recordsToDelete) {
                    $this->context->incrementDeleteCount($recordsToDelete);
                }

                $this->getProductPriceRepository()->deleteByPriceList($priceList);

                $this->processedPriceLists[$identifier] = true;
            }
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        // no need to search product prices in storage
        if (is_a($entity, $this->entityName)) {
            return null;
        }

        return parent::findExistingEntity($entity, $searchContext);
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->strategyHelper
            ->getEntityManager($this->entityName)
            ->getRepository($this->entityName);
    }

    /**
     * There is no replaced entities during reset
     *
     * {@inheritdoc}
     */
    protected function updateContextCounters($entity)
    {
        $this->context->incrementAddCount();
    }
}
