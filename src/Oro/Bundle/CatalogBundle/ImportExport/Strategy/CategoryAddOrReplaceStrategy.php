<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Strategy;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;

/**
 * Import strategy for Category.
 * Additionally is responsible for:
 * - handles resolving of parentCategory relation
 */
class CategoryAddOrReplaceStrategy extends LocalizedFallbackValueAwareStrategy
{
    /** @var CategoryImportExportHelper|null */
    protected $categoryImportExportHelper;

    /** @var Category|null */
    protected $rootCategory;

    /** @var int|null */
    protected $maxLeft;

    /**
     * @param CategoryImportExportHelper $categoryImportExportHelper
     */
    public function setCategoryImportExportHelper(CategoryImportExportHelper $categoryImportExportHelper): void
    {
        $this->categoryImportExportHelper = $categoryImportExportHelper;
    }

    /**
     * {@inheritdoc}
     *
     * @param Category $category
     */
    public function process($category)
    {
        /** @var Category|null $category */
        $category = parent::process($category);

        if ($category && $categoryPath = $this->categoryImportExportHelper->getCategoryPath($category)) {
            // Adds category to cache by category path.
            $this->newEntitiesHelper->setEntity($categoryPath, $category);
        }

        return $category;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterProcessEntity($category)
    {
        /** @var Category|null $category */
        $category = parent::afterProcessEntity($category);

        if ($category) {
            // Sets dummy values to Gedmo tree columns. These columns will be properly filled in CategoryWriter.
            $category->setLevel(0);
            if (!$category->getId()) {
                $category
                    // We have to set numbers correlating with rows to keep proper ordering. At the same time we should
                    // not interfere with existing ordering - the value should be greater than any of already existing.
                    ->setLeft($this->getLeftOffset())
                    ->setRight(0)
                    ->setRoot($this->getRootCategory()->getId());
            }
        }

        return $category;
    }

    /**
     * {@inheritdoc}
     *
     * Adds extra functionality to the base method:
     * - handles import of parentCategory relation
     */
    protected function importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData)
    {
        if ($isFullData && $entity instanceof Category && !$this->importParentCategoryField($entity, $existingEntity)) {
            // Skips category if parent category is not resolved.
            return null;
        }

        return parent::importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData);
    }

    /**
     * @param Category $category
     * @param Category|null $existingCategory
     *
     * @return bool
     */
    private function importParentCategoryField(Category $category, ?Category $existingCategory): bool
    {
        if ($this->isRootCategory($category)) {
            if ($category->getParentCategory()) {
                $errorMessages = [
                    sprintf(
                        'Skipping category "%s". Root category cannot have a parent',
                        (string) $category->getTitle()
                    ),
                ];
                $this->strategyHelper->addValidationErrors($errorMessages, $this->context);

                // Skips if tried to set parent category for root.
                return false;
            }

            // Does nothing if current category is root.
            return true;
        }

        $parentCategory = $category->getParentCategory();

        if (!$parentCategory) {
            $parentCategory = $existingCategory ? $existingCategory->getParentCategory() : $this->getRootCategory();
            $this->fieldHelper->setObjectValue($category, 'parentCategory', $parentCategory);

            return true;
        }

        $parentCategoryPath = $this->getCurrentParentCategoryPath();
        $searchContext = $this
            ->generateSearchContextForRelationsUpdate($category, Category::class, 'parentCategory', false);
        $existingParentCategory = $this->findExistingEntity($parentCategory, $searchContext);
        if (!$existingParentCategory) {
            $parentCategoryId = $parentCategory->getId();
            if ($parentCategoryId) {
                $errorMessages = [
                    sprintf(
                        'Skipping category "%s". Cannot find parent category with id "%s"',
                        (string) $category->getTitle(),
                        (string) $category->getParentCategory()->getId()
                    ),
                ];
                $this->strategyHelper->addValidationErrors($errorMessages, $this->context);

                return false;
            }

            if (!$parentCategoryId && $parentCategoryPath) {
                // Postpone row to try import it later.
                $this->postponeCategory($category);

                return false;
            }
        }

        $this->fieldHelper->setObjectValue($category, 'parentCategory', $existingParentCategory);

        return true;
    }

    /**
     * @param Category $category
     */
    protected function postponeCategory(Category $category): void
    {
        $this->context->addPostponedRow($this->context->getValue('rawItemData'));

        if (!$this->context->getOption('attempts')) {
            $this->context->addError(
                sprintf(
                    'Row #%d. Cannot find parent category "%s". Pushing category "%s" to the end of the queue.',
                    $this->strategyHelper->getCurrentRowNumber($this->context),
                    $this->getCurrentParentCategoryPath(),
                    (string) $category->getTitle()
                )
            );
        }

        if ($this->isLastAttempt()) {
            $this->context->addError(
                sprintf(
                    'Cannot find parent category "%s". Aborting processing of category "%s".',
                    $this->getCurrentParentCategoryPath(),
                    (string) $category->getTitle()
                )
            );
        }
    }

    /**
     * @return bool
     */
    protected function isLastAttempt(): bool
    {
        return $this->context->hasOption('max_attempts') &&
            (int) $this->context->getOption('attempts') === (int) $this->context->getOption('max_attempts');
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        $categoryPath = $searchContext['categoryPath'] ?? '';
        unset($searchContext['categoryPath']);

        $existingEntity = parent::findExistingEntity($entity, $searchContext);

        // Try to find category by category path.
        if (!$existingEntity && $categoryPath) {
            $existingEntity = $this->findCategoryByPath($categoryPath);
        }

        return $existingEntity;
    }

    /**
     * {@inheritdoc}
     *
     * Adds extra functionality to the base method:
     * - puts a "categoryPath" into search context so in ::findExistingMethod() we can find
     * a parent category by path
     */
    protected function generateSearchContextForRelationsUpdate($entity, $entityName, $fieldName, $isPersistRelation)
    {
        if ($entityName === Category::class && $fieldName === 'parentCategory') {
            return ['categoryPath' => $this->getCurrentParentCategoryPath()];
        }

        return parent::generateSearchContextForRelationsUpdate($entity, $entityName, $fieldName, $isPersistRelation);
    }

    /**
     * @return string
     */
    private function getCurrentParentCategoryPath(): string
    {
        $rawItemData = $this->context->getValue('rawItemData');

        return $rawItemData['parentCategory.title'] ?? '';
    }

    /**
     * @param string $categoryPath
     *
     * @return Category|null
     */
    private function findCategoryByPath(string $categoryPath): ?Category
    {
        /** @var Category|null $foundCategory */
        if ($foundCategory = $this->newEntitiesHelper->getEntity($categoryPath)) {
            // Category was found in cache.
            return $foundCategory;
        }

        $foundCategory = $this->categoryImportExportHelper->findCategoryByPath($categoryPath);

        // Adds found category to cache.
        $this->newEntitiesHelper->setEntity($categoryPath, $foundCategory);

        return $foundCategory;
    }

    /**
     * @param Category $category
     *
     * @return bool
     */
    private function isRootCategory(Category $category): bool
    {
        return (int) $this->getRootCategory()->getId() === (int) $category->getId();
    }

    /**
     * @return Category
     */
    private function getRootCategory(): Category
    {
        if (!$this->rootCategory) {
            $this->rootCategory = $this->categoryImportExportHelper->getRootCategory();
        }

        return $this->rootCategory;
    }

    /**
     * @return int
     */
    private function getLeftOffset(): int
    {
        if ($this->maxLeft === null) {
            $this->maxLeft = $this->categoryImportExportHelper->getMaxLeft();
        }

        return $this->maxLeft + $this->strategyHelper->getCurrentRowNumber($this->context);
    }
}
