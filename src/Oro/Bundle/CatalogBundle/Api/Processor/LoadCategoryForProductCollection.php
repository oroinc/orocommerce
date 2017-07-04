<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Component\ChainProcessor\ContextInterface;

class LoadCategoryForProductCollection extends AbstractLoadCategoryForProduct
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $data = $context->getResult();
        if (!is_array($data)
            || !isset($data[AbstractDocumentBuilder::DATA])
            || !is_array($data[AbstractDocumentBuilder::DATA])
        ) {
            return;
        }

        $products = $data[AbstractDocumentBuilder::DATA];
        foreach ($products as &$product) {
            $product = $this->includeCategoryInResult($product);
        }
        unset($product);
        $data[AbstractDocumentBuilder::DATA] = $products;

        $context->setResult($data);
    }
}
