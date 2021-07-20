<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Placeholder\CategoryPathPlaceholder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

/**
 * Adds following category information to Product documents at search index
 * - category ID (category_id)
 * - full materialized path (category_path)
 * - parts of materialized path for all parent categories (category_path_CATEGORY_PATH)
 * - category title (category_title_LOCALIZATION_ID)
 * - category short description (all_text_LOCALIZATION_ID)
 * - category long description (all_text_LOCALIZATION_ID)
 */
class WebsiteSearchCategoryIndexerListener
{
    const CATEGORY_TITLE_L10N_FIELD = 'category_title_LOCALIZATION_ID';

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var WebsiteLocalizationProvider
     */
    private $websiteLocalizationProvider;

    /**
     * @var CategoryRepository
     */
    private $repository;

    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManager;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AbstractWebsiteLocalizationProvider $websiteLocalizationProvider,
        WebsiteContextManager $websiteContextManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->websiteLocalizationProvider = $websiteLocalizationProvider;
        $this->websiteContextManager = $websiteContextManager;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        /** @var Product[] $products */
        $products = $event->getEntities();

        $localizations = $this->websiteLocalizationProvider->getLocalizationsByWebsiteId($websiteId);

        $categoryMap = $this->getRepository()->getCategoryMapByProducts($products);

        foreach ($products as $product) {
            /** @var Category $category */
            $category = &$categoryMap[$product->getId()];
            if (!empty($category)) {
                // Non localized fields
                $event->addField($product->getId(), 'category_id', $category->getId());

                $this->addCategoryPathInformation($event, $product, $category);

                // Localized fields
                foreach ($localizations as $localization) {
                    $placeholders = [LocalizationIdPlaceholder::NAME => $localization->getId()];

                    $event->addPlaceholderField(
                        $product->getId(),
                        static::CATEGORY_TITLE_L10N_FIELD,
                        (string)$category->getTitle($localization),
                        $placeholders,
                        true
                    );

                    $event->addPlaceholderField(
                        $product->getId(),
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        (string)$category->getLongDescription($localization),
                        $placeholders,
                        true
                    );

                    $event->addPlaceholderField(
                        $product->getId(),
                        IndexDataProvider::ALL_TEXT_L10N_FIELD,
                        (string)$category->getShortDescription($localization),
                        $placeholders,
                        true
                    );
                }
            }
        }
    }

    protected function addCategoryPathInformation(IndexEntityEvent $event, Product $product, Category $category)
    {
        $event->addField($product->getId(), 'category_path', $category->getMaterializedPath());

        $pathParts = explode(Category::MATERIALIZED_PATH_DELIMITER, $category->getMaterializedPath());
        $lastPart = null;

        foreach ($pathParts as $part) {
            $delimiter = $lastPart ? Category::MATERIALIZED_PATH_DELIMITER : '';

            $lastPart .= $delimiter . $part;

            $event->addPlaceholderField(
                $product->getId(),
                'category_path_CATEGORY_PATH',
                1,
                [CategoryPathPlaceholder::NAME => $lastPart],
                false
            );
        }
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository()
    {
        if (!$this->repository) {
            $this->repository = $this->doctrineHelper->getEntityRepository(Category::class);
        }

        return $this->repository;
    }
}
