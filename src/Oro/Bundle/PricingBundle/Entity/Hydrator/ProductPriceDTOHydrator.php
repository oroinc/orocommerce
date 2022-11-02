<?php

namespace Oro\Bundle\PricingBundle\Entity\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Hydrate prices batch to ProductPriceDTO.
 */
class ProductPriceDTOHydrator extends AbstractHydrator
{
    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = [];
        $mappings = array_flip($this->_rsm->scalarMappings);
        while ($row = $this->_stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = new ProductPriceDTO(
                $this->_em->getReference(Product::class, $row[$mappings['id']]),
                Price::create((float)$row[$mappings['value']], $row[$mappings['currency']]),
                (float)$row[$mappings['quantity']],
                $this->_em->getReference(ProductUnit::class, $row[$mappings['code']])
            );
        }

        return $result;
    }
}
