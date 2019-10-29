<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\ProductPrice;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Normalizes entity ids that are returned in response
 */
class NormalizeOutputProductPriceId implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $result = $context->getResult();
        if (!is_array($result)) {
            return;
        }

        $newResult = $result;

        if (array_key_exists('id', $newResult)) {
            $newResult['id'] = PriceListIdContextUtil::normalizeProductPriceId($context, $newResult['id']);
        } else {
            foreach ($newResult as &$entity) {
                if (!array_key_exists('id', $entity)) {
                    continue;
                }

                $entity['id'] = PriceListIdContextUtil::normalizeProductPriceId($context, $entity['id']);
            }
        }

        $context->setResult($newResult);
    }
}
