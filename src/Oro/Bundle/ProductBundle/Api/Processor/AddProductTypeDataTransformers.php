<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds a data transformer used to store page templates to "pageTemplate" form field.
 */
class AddProductTypeDataTransformers implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        // The implementation moved to the SetProductPageTemplate class.
    }
}
