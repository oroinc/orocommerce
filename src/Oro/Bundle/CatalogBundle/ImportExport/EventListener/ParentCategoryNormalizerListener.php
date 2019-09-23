<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\ImportExportBundle\Event\NormalizeEntityEvent;

/**
 * Adds parentCategory.title column during export.
 */
class ParentCategoryNormalizerListener
{
    /** @var CategoryImportExportHelper */
    private $categoryImportExportHelper;

    /**
     * @param CategoryImportExportHelper $categoryImportExportHelper
     */
    public function __construct(CategoryImportExportHelper $categoryImportExportHelper)
    {
        $this->categoryImportExportHelper = $categoryImportExportHelper;
    }

    /**
     * @param NormalizeEntityEvent $event
     */
    public function afterNormalize(NormalizeEntityEvent $event): void
    {
        if (!$event->getObject() instanceof Category || !$event->isFullData()) {
            return;
        }

        $parentCategory = $event->getObject()->getParentCategory();
        if ($parentCategory) {
            $normalizedCategory = $event->getResult();
            $normalizedParentCategory = $normalizedCategory['parentCategory'] ?? [];
            $normalizedParentCategory['titles']['default']['string']
                = $this->categoryImportExportHelper->getPersistedCategoryPath($parentCategory);

            $event->setResultField('parentCategory', $normalizedParentCategory);
        }
    }
}
