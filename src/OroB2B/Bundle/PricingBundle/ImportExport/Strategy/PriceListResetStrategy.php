<?php

namespace OroB2B\Bundle\PricingBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;

class PriceListResetStrategy extends ConfigurableAddOrReplaceStrategy
{
    /**
     * @var string
     */
    protected $productPriceClassName;

    /**
     * @param string $productPriceClassName
     */
    public function setProductPriceClassName($productPriceClassName)
    {
        $this->productPriceClassName = $productPriceClassName;
    }

    /**
     * @param PriceList $entity
     *
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        if ($entity instanceof PriceList) {
            $this->getProductPriceRepository()->deleteByPriceList($entity);
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        if ($this->isIgnoredEntity($entity)) {
            return null;
        }

        return parent::findExistingEntity($entity, $searchContext);
    }

    /**
     * No need to load ProductPrice in reset strategy
     *
     * @param object $entity
     *
     * @return bool
     */
    protected function isIgnoredEntity($entity)
    {
        return is_a($entity, $this->productPriceClassName);
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getProductPriceRepository()
    {
        return $this->strategyHelper
            ->getEntityManager($this->productPriceClassName)
            ->getRepository($this->productPriceClassName);
    }
}
