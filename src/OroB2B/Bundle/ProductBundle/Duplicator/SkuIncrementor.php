<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class SkuIncrementor implements SkuIncrementorInterface
{
    const INCREMENTED_SKU_PATTERN = '/^(.*)-\d+$/';
    const SKU_INCREMENT_PATTERN = '/^%s-(\d+)$/';
    const SKU_INCREMENT_DATABASE_PATTERN = '%s-%%';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var string[]
     */
    protected $newSku = [];

    /**
     * @var string
     */
    private $productClass;

    /**
     * @param ObjectManager $objectManager
     * @param string $productClass
     */
    public function __construct(ObjectManager $objectManager, $productClass)
    {
        $this->objectManager = $objectManager;
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
            if (preg_match(sprintf(self::SKU_INCREMENT_PATTERN, $sku), $incrementedSku, $matches)) {
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
        return $this->objectManager->getRepository($this->productClass);
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
}
