<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\ImportExport\Mapper\CategoryPathMapperInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration as ProductConfiguration;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

/**
 * Handles product normalization events during import/export operations.
 * Adds category to product export data.
 * Listens to product normalization events and adds category information to the normalized
 * product data, ensuring that category relationships are preserved during import/export.
 */
class ProductNormalizerEventListener extends AbstractProductImportEventListener
{
    protected ?ConfigManager $configManager = null;
    protected ?CategoryPathMapperInterface $categoryPathMapper = null;

    public function setConfigManager(ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    public function setCategoryPathMapper(CategoryPathMapperInterface $categoryPathMapper): void
    {
        $this->categoryPathMapper = $categoryPathMapper;
    }

    /**
     * @see \Oro\Bundle\CatalogBundle\EventListener\ProductDataConverterEventListener::onBackendHeader()
     */
    public function onNormalize(ProductNormalizerEvent $event)
    {
        $context = $event->getContext();
        if (array_key_exists('fieldName', $context)) {
            // It's a related Product entity (like variantLinks)
            return;
        }

        $category = $this->getCategoryByProduct($event->getProduct(), true);
        if (!$category) {
            return;
        }

        $data = $event->getPlainData();

        if ($this->configManager && $this->categoryPathMapper) {
            // Defensive for backward compatibility with customizations.
            // For the new configuration options to work, we need the new dependencies.
            $exportCategoryPath = (bool) $this->configManager->get(
                ProductConfiguration::getConfigKeyByName(ProductConfiguration::EXPORT_CATEGORY_PATH)
            );
            if ($exportCategoryPath) {
                $data[self::CATEGORY_PATH_KEY] = $this->categoryPathMapper->titlesToPathString(
                    $this->getCategoryRepository()->getCategoryPath($category)
                );
            }
            $exportCategoryDefaultTitle = (bool) $this->configManager->get(
                ProductConfiguration::getConfigKeyByName(ProductConfiguration::EXPORT_CATEGORY_DEFAULT_TITLE)
            );
            if ($exportCategoryDefaultTitle) {
                $data[self::CATEGORY_KEY] = $category->getDefaultTitle();
            }
        } else {
            // preserving old behavior
            $data[self::CATEGORY_KEY] = $category->getDefaultTitle();
        }

        $event->setPlainData($data);
    }
}
