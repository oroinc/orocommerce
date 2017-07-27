<?php

namespace Oro\Bundle\ProductBundle\Processor\Create;

use Oro\Bundle\FrontendBundle\Form\DataTransformer\PageTemplateEntityFieldFallbackValueTransformer;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Get's the created formBuilder and adds necessary data transformers
 *
 * @package Oro\Bundle\ProductBundle\Processor\Create
 */
class AddProductTypeDataTransformers implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $formBuilder = $context->getFormBuilder();

        if ($formBuilder->has('pageTemplate')) {
            $formBuilder->get('pageTemplate')
                ->addModelTransformer(
                    new PageTemplateEntityFieldFallbackValueTransformer(ProductType::PAGE_TEMPLATE_ROUTE_NAME)
                );
        }

        $context->setFormBuilder($formBuilder);
    }
}
