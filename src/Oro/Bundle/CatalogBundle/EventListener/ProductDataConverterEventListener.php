<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

/**
 * Adds category fields to the list of product export fields.
 */
class ProductDataConverterEventListener
{
    public const string PRODUCT_CATEGORY_FIELD_NAME = 'category';
    public const string CATEGORY_ID_FIELD_NAME = 'id';

    protected ?ConfigManager $configManager = null;

    public function setConfigManager(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    /**
     * @see \Oro\Bundle\CatalogBundle\EventListener\ProductNormalizerEventListener::onNormalize()
     */
    public function onBackendHeader(ProductDataConverterEvent $event): void
    {
        $data = $event->getData();

        // Old behavior without new configuration options:

        // 1. Category ID is added automatically if the category field is not excluded from export in field settings.

        // 2. `category.default.title` was always added
        $exportCategoryDefaultTitle = true;

        // 3. Category path was not available for export.
        $exportCategoryPath = false;

        if ($this->configManager) {
            $exportCategoryDefaultTitle = (bool) $this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_DEFAULT_TITLE)
            );
            $exportCategoryPath = (bool) $this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::EXPORT_CATEGORY_PATH)
            );
        }

        if ($exportCategoryDefaultTitle
            && !\in_array(AbstractProductImportEventListener::CATEGORY_KEY, $data, true)
        ) {
            $data[] = AbstractProductImportEventListener::CATEGORY_KEY;
        }

        if ($exportCategoryPath
            && !\in_array(AbstractProductImportEventListener::CATEGORY_PATH_KEY, $data, true)
        ) {
            $data[] = AbstractProductImportEventListener::CATEGORY_PATH_KEY;
        }

        $event->setData($data);
    }
}
