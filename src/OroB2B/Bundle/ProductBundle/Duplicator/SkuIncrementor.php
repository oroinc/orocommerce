<?php

namespace OroB2B\Bundle\ProductBundle\Duplicator;

use Doctrine\Common\Persistence\ObjectManager;

class SkuIncrementor
{
    const INCREMENTED_SKU_PATTERN = '/^(.*)-\d+$/';
    const SKU_INCREMENT_PATTERN = '/^%s-(\d+)$/';

    /**
     * @var string[]
     */
    protected $existingIncrementedSku;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $sku
     * @return string
     */
    public function increment($sku)
    {
        if (null === $this->existingIncrementedSku) {
            $this->loadIncrementedSku();
        }

        $maxIndex = 0;

        if (preg_match(self::INCREMENTED_SKU_PATTERN, $sku, $matches)) {
            $baseSku = $matches[1];
            if (in_array($baseSku, $this->existingIncrementedSku)) {
                $sku = $baseSku;
            }
        }

        foreach ($this->existingIncrementedSku as $incrementedSku) {
            if (preg_match(sprintf(self::SKU_INCREMENT_PATTERN, $sku), $incrementedSku, $matches)) {
                $maxIndex = max($maxIndex, $matches[1]);
            }
        }

        $this->existingIncrementedSku[] = $newSku = sprintf('%s-%d', $sku, ++$maxIndex);

        return $newSku;
    }

    private function loadIncrementedSku()
    {
        $this->existingIncrementedSku = $this->objectManager
            ->getRepository('OroB2BProductBundle:Product')
            ->getAllSku();
    }
}
