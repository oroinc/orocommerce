<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CategoryTitleProvider implements ContentVariantTitleProviderInterface
{
    const SUPPORTED_TYPE ='catalog_page_category';
    const FIELD_NAME = 'catalogPageCategory';

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

        $category  = $this->propertyAccessor->getValue($contentVariant, self::FIELD_NAME);
        if ($category instanceof Category) {
            return $this->localizationHelper->getFirstNonEmptyLocalizedValue($category->getTitles());
        }

        return null;
    }
}
