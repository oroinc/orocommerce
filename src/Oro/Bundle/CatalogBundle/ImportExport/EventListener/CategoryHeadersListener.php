<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ImportExportBundle\Converter\ConfigurableTableDataConverter;
use Oro\Bundle\ImportExportBundle\Event\LoadEntityRulesAndBackendHeadersEvent;

/**
 * Adds "parentCategory.title" header to category export file.
 */
class CategoryHeadersListener
{
    /** @var FieldHelper */
    private $fieldHelper;

    /**
     * @param FieldHelper $fieldHelper
     */
    public function __construct(FieldHelper $fieldHelper)
    {
        $this->fieldHelper = $fieldHelper;
    }

    /**
     * @param LoadEntityRulesAndBackendHeadersEvent $event
     */
    public function afterLoadEntityRulesAndBackendHeaders(LoadEntityRulesAndBackendHeadersEvent $event): void
    {
        if ($event->getEntityName() !== Category::class || !$event->isFullData()) {
            return;
        }

        $parentCategoryOrder = $this->fieldHelper->getConfigValue(Category::class, 'parentCategory', 'order')
            ?? ConfigurableTableDataConverter::DEFAULT_ORDER;

        $this->addHeader(
            $event,
            sprintf('parentCategory%1$stitles%1$sdefault%1$sstring', $event->getConvertDelimiter()),
            'parentCategory.title',
            $parentCategoryOrder + 1
        );
    }

    /**
     * @param LoadEntityRulesAndBackendHeadersEvent $event
     * @param string $value
     * @param string $label
     * @param int $order
     */
    private function addHeader(
        LoadEntityRulesAndBackendHeadersEvent $event,
        string $value,
        string $label,
        int $order
    ): void {
        if (!in_array($value, array_column($event->getHeaders(), 'value'), false)) {
            $event->addHeader(['value' => $value, 'order' => $order]);
            $event->setRule($label, ['value' => $value, 'order' => $order]);
        }
    }
}
