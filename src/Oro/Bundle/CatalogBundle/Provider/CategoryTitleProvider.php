<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
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

        $category  = $this->propertyAccessor->getValue($contentVariant, self::FIELD_NAME);
        if ($category instanceof Category) {
            if ($category->getDefaultTitle() instanceof LocalizedFallbackValue
                && '' !== $category->getDefaultTitle()->getString()
            ) {
                return $category->getDefaultTitle()->getString();
            } elseif ($category->getTitles() instanceof ArrayCollection) {
                foreach ($category->getTitles() as $localizedTitle) {
                    if ('' !== $localizedTitle->getString()) {
                        return $localizedTitle->getString();
                    }
                }
            }
        }

        return null;
    }
}
