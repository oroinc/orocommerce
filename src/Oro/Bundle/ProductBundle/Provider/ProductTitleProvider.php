<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
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
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(PropertyAccessor $propertyAccessor, LocalizationHelper $localizationHelper)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->localizationHelper = $localizationHelper;
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
        if ($product instanceof Product) {
            return $this->localizationHelper->getFirstNonEmptyLocalizedValue($product->getNames());
        }

        return null;
    }
}
