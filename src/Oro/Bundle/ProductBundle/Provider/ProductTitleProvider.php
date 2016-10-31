<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductTitleProvider implements ContentVariantTitleProviderInterface
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(ContentVariantInterface $contentVariant)
    {
        $product  = $this->propertyAccessor->getValue($contentVariant, 'productPageProduct');
        if ($product instanceof Product && $product->getDefaultName() instanceof LocalizedFallbackValue) {
            $title = $product->getDefaultName()->getText();
            return $title;
        }

        return null;
    }
}
