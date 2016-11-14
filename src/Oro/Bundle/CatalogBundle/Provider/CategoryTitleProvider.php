<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\DependencyInjection\ServiceLink;
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
        if ($contentVariant->getType() !== self::SUPPORTED_TYPE) {
            return null;
        }

        $category  = $this->propertyAccessor->getValue($contentVariant, self::FIELD_NAME);
        if ($category instanceof Category) {
            return $this->getLocalizationHelper()->getFirstNonEmptyLocalizedValue($category->getTitles());
        }

        return null;
    }
}
