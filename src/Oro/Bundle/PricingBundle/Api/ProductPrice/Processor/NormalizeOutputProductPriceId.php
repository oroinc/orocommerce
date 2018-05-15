<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice\Processor;

use Oro\Bundle\PricingBundle\Api\ProductPrice\ProductPriceIDByContextNormalizerInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Normalizes entity ids that are returned in response
 */
class NormalizeOutputProductPriceId implements ProcessorInterface
{
    /**
     * @var ProductPriceIDByContextNormalizerInterface
     */
    private $productPriceIDByContextNormalizer;

    /**
     * @param ProductPriceIDByContextNormalizerInterface $productPriceIDByContextNormalizer
     */
    public function __construct(ProductPriceIDByContextNormalizerInterface $productPriceIDByContextNormalizer)
    {
        $this->productPriceIDByContextNormalizer = $productPriceIDByContextNormalizer;
    }

    /**
     * @inheritDoc
     */
    public function process(ContextInterface $context)
    {
        $result = $context->getResult();

        if (null === $result) {
            return;
        }

        if (false === is_array($result)) {
            return;
        }

        $newResult = $result;

        if (array_key_exists('id', $newResult)) {
            $newResult['id'] = $this->productPriceIDByContextNormalizer->normalize($newResult['id'], $context);
        } else {
            foreach ($newResult as &$entity) {
                if (false === array_key_exists('id', $entity)) {
                    continue;
                }

                $entity['id'] = $this->productPriceIDByContextNormalizer->normalize($entity['id'], $context);
            }
        }

        $context->setResult($newResult);
    }
}
