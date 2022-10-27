<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Form\Type\ProductType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Set page template to product.
 * It is necessary in the case when the product is added as a dependence on any entity.
 */
class SetProductPageTemplate implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $pageTemplateForm = $context->findFormField('pageTemplate');
        if (null === $pageTemplateForm) {
            return;
        }

        $pageTemplateData = $pageTemplateForm->getData();
        if ($pageTemplateData instanceof EntityFieldFallbackValue && null !== $pageTemplateData->getScalarValue()) {
            $pageTemplateData->setArrayValue([
                ProductType::PAGE_TEMPLATE_ROUTE_NAME => $pageTemplateData->getScalarValue()
            ]);
            $pageTemplateData->setScalarValue(null);
        }
    }
}
