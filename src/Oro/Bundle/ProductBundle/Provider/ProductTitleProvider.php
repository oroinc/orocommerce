<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductTitleProvider implements ContentVariantTitleProviderInterface
{
    const FIELD_NAME = 'productPageProduct';

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var ServiceLink
     */
    protected $localizationHelperLink;

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param ServiceLink $localizationHelperLink
     */
    public function __construct(PropertyAccessor $propertyAccessor, ServiceLink $localizationHelperLink)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->localizationHelperLink = $localizationHelperLink;
    }

    /**
     * @return LocalizationHelper|object
     */
    protected function getLocalizationHelper()
    {
        return $this->localizationHelperLink->getService();
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(ContentVariantInterface $contentVariant)
    {
        if ($contentVariant->getType() !== ProductPageContentVariantType::TYPE) {
            return null;
        }

        $product  = $this->propertyAccessor->getValue($contentVariant, self::FIELD_NAME);
        if ($product instanceof Product) {
            return $this->getLocalizationHelper()->getFirstNonEmptyLocalizedValue($product->getNames());
        }

        return null;
    }
}
