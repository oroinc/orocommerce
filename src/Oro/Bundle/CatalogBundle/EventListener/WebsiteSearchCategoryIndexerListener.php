<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Placeholder\CategoryPathPlaceholder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

/**
 * Adds following category information to Product documents at search index
 * - category ID (category_id)
 * - full materialized path (category_path)
 * - parts of materialized path for all parent categories (category_paths.CATEGORY_PATH)
 * - category title (category_title_LOCALIZATION_ID)
 * - category ID with parent category titles (category_id_with_parent_categories_LOCALIZATION_ID)
 * - category short description (all_text_LOCALIZATION_ID)
 * - category long description (all_text_LOCALIZATION_ID)
 * - category sort order (category_sort_order) which is in its own attribute group category_sort_order
 */
class WebsiteSearchCategoryIndexerListener
{
    use ContextTrait;

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

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'main')
            && !$this->hasContextFieldGroup($event->getContext(), 'category_sort_order')
        ) {
            return;
        }

        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        if ($this->hasContextFieldGroup($event->getContext(), 'main')) {
            $this->addInformationToIndex($event, $websiteId);
        }

        if ($this->hasContextFieldGroup($event->getContext(), 'category_sort_order')) {
            $this->addCategorySortOrderInformationToIndex($event);
        }
    }

    /**
     * @param IndexEntityEvent $event
     * @param int $websiteId
     * @return void
     */
    protected function addInformationToIndex(IndexEntityEvent $event, int $websiteId): void
    {
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

                $parentCategories = $this->getParentCategories($category);

                // Localized fields
                foreach ($localizations as $localization) {
                    $placeholders = [LocalizationIdPlaceholder::NAME => $localization->getId()];

                    $event->addPlaceholderField(
                        $product->getId(),
                        'category_title_LOCALIZATION_ID',
                        (string)$category->getTitle($localization),
                        $placeholders,
                        true
                    );

                    if ($parentCategories) {
                        $event->addPlaceholderField(
                            $product->getId(),
                            'category_id_with_parent_categories_LOCALIZATION_ID',
                            $this->generateIdWithParentCategories($parentCategories, $localization),
                            $placeholders
                        );
                    }

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

    /**
     * @param IndexEntityEvent $event
     * @return void
     */
    protected function addCategorySortOrderInformationToIndex(IndexEntityEvent $event): void
    {
        /** @var Product[] $products */
        $products = $event->getEntities();

        foreach ($products as $product) {
            $event->addField($product->getId(), 'category_sort_order', $product->getCategorySortOrder());
        }
    }

    /**
     * @param IndexEntityEvent $event
     * @param Product $product
     * @param Category $category
     * @return void
     */
    protected function addCategoryPathInformation(IndexEntityEvent $event, Product $product, Category $category): void
    {
        $event->addField($product->getId(), 'category_path', $category->getMaterializedPath());

        $pathParts = explode(Category::MATERIALIZED_PATH_DELIMITER, $category->getMaterializedPath());
        $lastPart = null;

        foreach ($pathParts as $part) {
            $delimiter = $lastPart ? Category::MATERIALIZED_PATH_DELIMITER : '';

            $lastPart .= $delimiter . $part;

            $event->addPlaceholderField(
                $product->getId(),
                'category_paths.CATEGORY_PATH',
                1,
                [CategoryPathPlaceholder::NAME => $lastPart],
                false
            );
        }
    }

    /**
     * @param Category $category
     * @return Category[]
     */
    protected function getParentCategories(Category $category) : array
    {
        $parentCategoryIds = explode(Category::MATERIALIZED_PATH_DELIMITER, $category->getMaterializedPath());

        // remove root category
        unset($parentCategoryIds[0]);

        /** @var Category[] $parentCategories */
        $parentCategories = $this->getRepository()->findBy(['id' => $parentCategoryIds]);

        $indexedParentCategories = [];
        foreach ($parentCategoryIds as $parentCategoryId) {
            foreach ($parentCategories as $parentCategory) {
                if ($parentCategory->getId() == $parentCategoryId) {
                    $indexedParentCategories[$parentCategoryId] = $parentCategory;
                    break;
                }
            }
        }

        return $indexedParentCategories;
    }

    /**
     * @param Category[] $parentCategories
     * @param Localization $localization
     * @return string
     */
    protected function generateIdWithParentCategories(array $parentCategories, Localization $localization) : string
    {
        $parts = [];

        foreach ($parentCategories as $category) {
            $parts[] = str_replace(Category::INDEX_DATA_DELIMITER, ' ', (string)$category->getTitle($localization));
        }
        array_unshift($parts, $category->getId());

        return implode(Category::INDEX_DATA_DELIMITER, $parts);
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository(): CategoryRepository
    {
        if (!$this->repository) {
            $this->repository = $this->doctrineHelper->getEntityRepository(Category::class);
        }

        return $this->repository;
    }
}
