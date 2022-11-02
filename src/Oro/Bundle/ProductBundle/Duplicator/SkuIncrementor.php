<?php

namespace Oro\Bundle\ProductBundle\Duplicator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Increments provided product SKU for a duplicated product.
 */
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
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @var string
     */
    protected $productClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param AclHelper $aclHelper
     * @param string $productClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, AclHelper $aclHelper, $productClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
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
        $qb = $this->getRepository()
            ->getAllSkuByPatternQueryBuilder(sprintf(self::SKU_INCREMENT_DATABASE_PATTERN, $sku));

        /** @var array $result */
        $result = $this->aclHelper->apply($qb)->getResult();

        $matchedSku = [];
        foreach ($result as $item) {
            $matchedSku[] = $item['sku'];
        }

        return $matchedSku;
    }

    /**
     * @param string $sku
     * @return string
     */
    protected function defineBaseSku($sku)
    {
        if (preg_match(self::INCREMENTED_SKU_PATTERN, $sku, $matches)) {
            $baseSku = $matches[1];

            $qb = $this->getRepository()->getBySkuQueryBuilder($baseSku);
            if ($this->aclHelper->apply($qb)->getOneOrNullResult()) {
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
