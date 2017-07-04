<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder;
use Oro\Component\ChainProcessor\ContextInterface;

class LoadCategoryForProduct extends AbstractLoadCategoryForProduct
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $data = $context->getResult();
        if (!is_array($data) || !isset($data[AbstractDocumentBuilder::DATA][JsonApiDocumentBuilder::ATTRIBUTES]['sku'])
        ) {
            return;
        }

        $productInfo = $this->includeCategoryInResult($data[AbstractDocumentBuilder::DATA]);
        $data[AbstractDocumentBuilder::DATA] = $productInfo;
        $context->setResult($data);
    }
}
