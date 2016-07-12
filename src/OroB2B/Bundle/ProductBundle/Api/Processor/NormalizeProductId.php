<?php

namespace OroB2B\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class NormalizeProductId implements ProcessorInterface
{
    const PRODUCT_IDENTIFIER = 'sku';

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var UpdateContext $context */
        $productRequestData = [self::PRODUCT_IDENTIFIER => $context->getId()];
        $context->setRequestData(array_merge($productRequestData, $context->getRequestData()));
        $context->setId(null);
    }
}
