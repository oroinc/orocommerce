<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Contains methods for Category import.
 * Decreases complexity, responsibility and simplifies unit testing of CategoryAddOrReplaceStrategy.
 */
class CategoryImportExportHelper
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $unescapedDelimiter;

    /** @var string */
    private $escapedDelimiter;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->unescapedDelimiter = trim(Category::CATEGORY_PATH_DELIMITER);
        $this->escapedDelimiter = $this->unescapedDelimiter.$this->unescapedDelimiter;
    }

    /**
     * @param Category $category
     *
     * @return string
     */
    public function getCategoryPath(Category $category): string
    {
        // Collects categories titles path.
        $categoryPath = [];
        /** @var Category|null $eachCategory */
        while ($eachCategory = (!empty($eachCategory) ? $eachCategory->getParentCategory() : $category)) {
            $title = $this->escapeTitle((string)$eachCategory->getTitle());
            array_unshift($categoryPath, $title);
        }

        return implode(Category::CATEGORY_PATH_DELIMITER, $categoryPath);
    }

    /**
     * @param Category $category
     *
     * @return string
     */
    public function getPersistedCategoryPath(Category $category): string
    {
        $categoryPath = array_map([$this, 'escapeTitle'], $this->getRepository()->getCategoryPath($category));

        return implode(Category::CATEGORY_PATH_DELIMITER, $categoryPath);
    }

    /**
     * @param string $categoryPath Category path consisting from categories titles, e.g. "All / Cat1 / Cat2"
     * @param Organization $organization
     *
     * @return Category|null
     */
    public function findCategoryByPath(string $categoryPath, Organization $organization): ?Category
    {
        $foundCategory = null;
        $categoryRepo = $this->getRepository();
        $categoryPathParts = explode(Category::CATEGORY_PATH_DELIMITER, $categoryPath);
        $categoryPathStack = [];
        // Goes through the path in reverse order, i.e. gets "Cat 2" from path "All / Cat1 / Cat2" at first.
        while ($categoryTitle = array_pop($categoryPathParts)) {
            $categoryTitle = $this->unescapeTitle($categoryTitle);

            if (!$foundCategory) {
                // Tries to find a category by title and organization.
                $foundCategory = $categoryRepo->findOneOrNullByDefaultTitleAndParent($categoryTitle, $organization);
                if ($foundCategory !== null) {
                    // If found - restart loop with categories which were not found in previous iterations, treating
                    // found category as parent for them.
                    $categoryPathParts = $categoryPathStack;
                    continue;
                }

                // If not found - adds its title to stack so we can go back to it once higher category is found.
                $categoryPathStack[] = $categoryTitle;
            } else {
                // Tries to find a category by organization and by found in previous iteration parent category.
                $foundCategory = $categoryRepo
                    ->findOneOrNullByDefaultTitleAndParent($categoryTitle, $organization, $foundCategory);
                if ($foundCategory === null) {
                    // If not found - break from loop, we did not find a category by path.
                    break;
                }
            }
        }

        return $foundCategory;
    }

    /**
     * @param Organization $organization
     *
     * @return Category
     */
    public function getRootCategory(Organization $organization): Category
    {
        return $this->getRepository()->getMasterCatalogRoot($organization);
    }

    /**
     * @return int
     */
    public function getMaxLeft(): int
    {
        return $this->getRepository()->getMaxLeft();
    }

    /**
     * @param string $title
     *
     * @return string
     */
    private function escapeTitle(string $title): string
    {
        return str_replace($this->unescapedDelimiter, $this->escapedDelimiter, $title);
    }

    /**
     * @param string $title
     *
     * @return string
     */
    private function unescapeTitle(string $title): string
    {
        return str_replace($this->escapedDelimiter, $this->unescapedDelimiter, $title);
    }

    /**
     * @return CategoryRepository
     */
    private function getRepository(): CategoryRepository
    {
        return $this->doctrine
            ->getManagerForClass(Category::class)
            ->getRepository(Category::class);
    }
}
