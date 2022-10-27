<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Strategy;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\Event\CategoryStrategyAfterProcessEntityEvent;
use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\LocaleBundle\ImportExport\Strategy\LocalizedFallbackValueAwareStrategy;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Import strategy for Category.
 * Additionally is responsible for:
 * - handles resolving of parentCategory relation
 * - sets dummy values to Gedmo tree columns as they must be properly filled in CategoryWriter
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CategoryAddOrReplaceStrategy extends LocalizedFallbackValueAwareStrategy
{
    /** @var CategoryImportExportHelper|null */
    protected $categoryImportExportHelper;

    /** @var TokenAccessorInterface|null */
    protected $tokenAccessor;

    /** @var Category[]|null */
    protected $rootCategories;

    /** @var int|null */
    protected $maxLeft;

    public function setCategoryImportExportHelper(CategoryImportExportHelper $categoryImportExportHelper): void
    {
        $this->categoryImportExportHelper = $categoryImportExportHelper;
    }

    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
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
     *
     * @param Category $category
     */
    protected function afterProcessEntity($category)
    {
        $this->eventDispatcher->dispatch(
            new CategoryStrategyAfterProcessEntityEvent($category, $this->context->getValue('itemData'))
        );

        /** @var Category|null $category */
        $category = parent::afterProcessEntity($category);

        if ($category) {
            // Sets dummy values to tree columns to make Gedmo recalculate them.
            // These columns will be properly filled in CategoryWriter.
            $category
                ->setLevel(0)
                ->setRight(0);
            if (!$category->getId()) {
                $organization = $this->getOrganization($category);
                $category
                    // We have to set numbers correlating with rows to keep proper ordering. At the same time we should
                    // not interfere with existing ordering - the value should be greater than any of already existing.
                    ->setLeft($this->getLeftOffset())
                    ->setRoot($this->getRootCategory($organization)->getId());
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
        if ($isFullData && $entity instanceof Category) {
            if (!$this->importParentCategoryField($entity, $existingEntity)) {
                // Skips category if parent category is not resolved.
                return null;
            }
        }

        return parent::importEntityFields($entity, $existingEntity, $isFullData, $entityIsRelation, $itemData);
    }

    protected function importParentCategoryField(Category $category, ?Category $existingCategory): bool
    {
        if ($this->isRootCategory($category)) {
            if ($category->getParentCategory()) {
                $errorMessages = [
                    sprintf(
                        'Skipping category "%s". Root category cannot have a parent',
                        (string)$category->getTitle()
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
            $organization = $this->getOrganization($category);
            $parentCategory = $existingCategory
                ? $existingCategory->getParentCategory()
                : $this->getRootCategory($organization);
            $this->fieldHelper->setObjectValue($category, 'parentCategory', $parentCategory);

            return true;
        }

        $searchContext = $this
            ->generateSearchContextForRelationsUpdate($category, Category::class, 'parentCategory', false);
        $existingParentCategory = $this->findExistingEntity($parentCategory, $searchContext);
        if (!$existingParentCategory) {
            $parentCategoryId = $parentCategory->getId();
            if ($parentCategoryId) {
                $errorMessages = [
                    sprintf(
                        'Skipping category "%s". Cannot find parent category with id "%s"',
                        (string)$category->getTitle(),
                        (string)$category->getParentCategory()->getId()
                    ),
                ];
                $this->strategyHelper->addValidationErrors($errorMessages, $this->context);

                return false;
            }

            $parentCategoryPath = $this->getCurrentParentCategoryPath();
            if (!$parentCategoryId && $parentCategoryPath) {
                // Postpone row to try import it later.
                $this->postponeCategory($category);

                return false;
            }
        }

        $this->fieldHelper->setObjectValue($category, 'parentCategory', $existingParentCategory);

        return true;
    }

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
        $organization = $searchContext['organization'] ?? null;
        unset($searchContext['categoryPath'], $searchContext['organization']);

        $existingEntity = parent::findExistingEntity($entity, $searchContext);

        // Try to find category by category path.
        if (!$existingEntity && $categoryPath) {
            $existingEntity = $this->findCategoryByPath($categoryPath, $organization);
        }

        return $existingEntity;
    }

    /**
     * {@inheritdoc}
     *
     * Adds extra functionality to the base method:
     * - puts a "categoryPath" and "organization" into search context so in ::findExistingMethod() we can find
     * a parent category by path
     */
    protected function generateSearchContextForRelationsUpdate($entity, $entityName, $fieldName, $isPersistRelation)
    {
        if ($entityName === Category::class && $fieldName === 'parentCategory') {
            return [
                'categoryPath' => $this->getCurrentParentCategoryPath(),
                'organization' => $this->getOrganization($entity),
            ];
        }

        return parent::generateSearchContextForRelationsUpdate($entity, $entityName, $fieldName, $isPersistRelation);
    }

    protected function getOrganization(?Category $category): Organization
    {
        $organization = null;

        if ($category) {
            $organization = $this->getCategoryOrganization($category);
        }

        if (!$organization) {
            $organization = $this->tokenAccessor->getOrganization();
            if ($organization === null) {
                throw new \LogicException('Cannot detect category organization');
            }
        }

        return $organization;
    }

    protected function getCategoryOrganization(Category $category): ?Organization
    {
        $organization = $category->getOrganization();
        if ($organization) {
            $organization = $this->findExistingEntity($organization);
        } else {
            $existingCategory = $this->findExistingEntity($category);
            if ($existingCategory) {
                $organization = $existingCategory->getOrganization();
            }
        }

        return $organization;
    }

    protected function getCurrentParentCategoryPath(): string
    {
        $rawItemData = $this->context->getValue('rawItemData');

        return $rawItemData['parentCategory.title'] ?? '';
    }

    protected function findCategoryByPath(string $categoryPath, Organization $organization): ?Category
    {
        /** @var Category|null $foundCategory */
        if ($foundCategory = $this->newEntitiesHelper->getEntity($categoryPath)) {
            // Category was found in cache.
            return $foundCategory;
        }

        $foundCategory = $this->categoryImportExportHelper->findCategoryByPath($categoryPath, $organization);

        // Adds found category to cache.
        $this->newEntitiesHelper->setEntity($categoryPath, $foundCategory);

        return $foundCategory;
    }

    protected function isRootCategory(Category $category): bool
    {
        $organization = $this->getOrganization($category);

        return (int)$this->getRootCategory($organization)->getId() === (int)$category->getId();
    }

    protected function getRootCategory(Organization $organization): Category
    {
        if (!isset($this->rootCategories[$organization->getId()])) {
            $this->rootCategories[$organization->getId()] = $this->categoryImportExportHelper
                ->getRootCategory($organization);
        }

        return $this->rootCategories[$organization->getId()];
    }

    protected function getLeftOffset(): int
    {
        if ($this->maxLeft === null) {
            $this->maxLeft = $this->categoryImportExportHelper->getMaxLeft();
        }

        return $this->maxLeft + $this->strategyHelper->getCurrentRowNumber($this->context);
    }
}
