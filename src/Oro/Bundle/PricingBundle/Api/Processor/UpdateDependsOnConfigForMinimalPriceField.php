<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds minimal prices for all product units to depends_on attribute for "minimalPrices" field
 * for ProductSearch entity.
 */
class UpdateDependsOnConfigForMinimalPriceField implements ProcessorInterface
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $config = $context->getConfig();
        $minimalPricesField = $config->getField(ComputeProductSearchMinimalPrices::MINIMAL_PRICES_FIELD);
        if (null !== $minimalPricesField && !$minimalPricesField->isExcluded()) {
            $dependsOn = $minimalPricesField->getDependsOn();
            $productUnits = $this->getProductUnits();
            foreach ($productUnits as $productUnit) {
                $dependsOn[] = 'decimal.minimal_price_CPL_ID_CURRENCY_' . $productUnit;
            }
            $minimalPricesField->setDependsOn($dependsOn);
        }
    }

    /**
     * @return string[]
     */
    private function getProductUnits(): array
    {
        $rows = $this->doctrineHelper
            ->createQueryBuilder(ProductUnit::class, 'e')
            ->select('e.code')
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[] = $row['code'];
        }

        return $result;
    }
}
