<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CategoryTitleProvider implements ContentVariantTitleProviderInterface
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
        if ((string)$contentVariant->getType() !== 'catalog_page_category'
            || null === $this->propertyAccessor->getValue($contentVariant, 'catalogPageCategory')
        ) {
            return null;
        }

        $category  = $this->propertyAccessor->getValue($contentVariant, 'catalogPageCategory');
        $title = null;
        if ($category instanceof Category && $category->getDefaultTitle() instanceof LocalizedFallbackValue) {
            $title = $category->getDefaultTitle()->getText();
        }

        return $title;
    }
}
