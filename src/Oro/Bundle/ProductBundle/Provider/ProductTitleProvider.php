<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ProductTitleProvider implements ContentVariantTitleProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getTitle(ContentVariantInterface $contentVariant)
    {
        if ($contentVariant->getType() != 'product_page_product') {
            return null;
        }

        $product  = $contentVariant->getProductPageProduct();
        $title = null;
        if ($product instanceof Product) {
            if ($product->getDefaultName() && $product->getDefaultName()->getText()) {
                $title = $product->getDefaultName()->getText();
            }
        }

        return $title;
    }
}
