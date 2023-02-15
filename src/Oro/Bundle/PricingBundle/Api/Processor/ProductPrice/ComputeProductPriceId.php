<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets ID in the 'GUID-priceListId' format to the response data.
 */
class ComputeProductPriceId implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $idFieldName = $context->getResultFieldName('id');
        $data[$idFieldName] = PriceListIdContextUtil::normalizeProductPriceId($context, $data[$idFieldName]);
        $context->setData($data);
    }
}
