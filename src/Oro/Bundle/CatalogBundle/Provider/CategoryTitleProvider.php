<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\WebCatalog\ContentVariantTitleProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class CategoryTitleProvider implements ContentVariantTitleProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getTitle(ContentVariantInterface $contentVariant)
    {
        if ($contentVariant->getType() != 'catalog_page_category') {
            return null;
        }

        $category  = $contentVariant->getCatalogPageCategory();
        $title = null;
        if ($category instanceof Category) {
            if ($category->getDefaultTitle() && $category->getDefaultTitle()->getText()) {
                $title = $category->getDefaultTitle()->getText();
            }
        }

        return $title;
    }
}
