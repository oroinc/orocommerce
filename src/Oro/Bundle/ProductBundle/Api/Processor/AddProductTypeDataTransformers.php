<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\FrontendBundle\Form\DataTransformer\PageTemplateEntityFieldFallbackValueTransformer;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
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
        /** @var FormContext $context */

        $formBuilder = $context->getFormBuilder();

        if ($formBuilder->has('pageTemplate')) {
            $formBuilder->get('pageTemplate')
                ->addModelTransformer(
                    new PageTemplateEntityFieldFallbackValueTransformer(ProductType::PAGE_TEMPLATE_ROUTE_NAME)
                );
        }
    }
}
