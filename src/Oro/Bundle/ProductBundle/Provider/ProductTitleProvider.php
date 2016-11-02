<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductTitleProvider implements ContentVariantTitleProviderInterface
{
    const SUPPORTED_TYPE ='product_page_product';
    const FIELD_NAME = 'productPageProduct';

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
        if ($contentVariant->getType() !== self::SUPPORTED_TYPE) {
            return null;
        }

        $product  = $this->propertyAccessor->getValue($contentVariant, self::FIELD_NAME);
        if ($product instanceof Product && $product->getDefaultName() instanceof LocalizedFallbackValue) {
            return $product->getDefaultName()->getText();
        }

        return null;
    }
}
