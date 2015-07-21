<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class SkuIncrementor implements SkuIncrementorInterface
{
    const INCREMENTED_SKU_PATTERN = '/^(.*)-\d+$/';
    const SKU_INCREMENT_PATTERN = '/^%s-(\d+)$/';
    const SKU_INCREMENT_DATABASE_PATTERN = '%s-%%';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string[]
     */
    protected $newSku = [];

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $productClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $productClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->productClass = $productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function increment($sku)
    {
        $maxIndex = 0;

        $sku = $this->defineBaseSku($sku);
        $possibleMatches = array_merge($this->getPreMatchedIncrementSku($sku), $this->newSku);

        foreach ($possibleMatches as $incrementedSku) {
            if (preg_match($this->buildSkuIncrementPattern($sku), $incrementedSku, $matches)) {
                $maxIndex = max($maxIndex, $matches[1]);
            }
        }

        $this->newSku[] = $newSku = sprintf('%s-%d', $sku, ++$maxIndex);

        return $newSku;
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository($this->productClass);
    }

    /**
     * @param string $sku
     * @return string[]
     */
    protected function getPreMatchedIncrementSku($sku)
    {
        return $this->getRepository()->findAllSkuByPattern(sprintf(self::SKU_INCREMENT_DATABASE_PATTERN, $sku));
    }

    /**
     * @param string $sku
     * @return string
     */
    protected function defineBaseSku($sku)
    {
        if (preg_match(self::INCREMENTED_SKU_PATTERN, $sku, $matches)) {
            $baseSku = $matches[1];

            if ($this->getRepository()->findOneBySku($baseSku)) {
                return $baseSku;
            }
        }

        return $sku;
    }

    /**
     * @param string $sku
     * @return string
     */
    protected function buildSkuIncrementPattern($sku)
    {
        return sprintf(self::SKU_INCREMENT_PATTERN, preg_quote($sku, '/'));
    }
}
