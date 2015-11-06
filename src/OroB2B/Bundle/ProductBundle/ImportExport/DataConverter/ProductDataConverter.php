<?php

namespace OroB2B\Bundle\ProductBundle\ImportExport\DataConverter;

use OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\AbstractLocalizedFallbackValueAwareDataConverter;

class ProductDataConverter extends AbstractLocalizedFallbackValueAwareDataConverter
{
    /** @var string */
    protected $productClass;

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * @inheritDoc
     */
    protected function getEntityClass()
    {
        return $this->productClass;
    }
}
