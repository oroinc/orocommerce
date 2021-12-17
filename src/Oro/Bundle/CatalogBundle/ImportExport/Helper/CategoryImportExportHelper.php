<?php

namespace Oro\Bundle\CatalogBundle\ImportExport\Helper;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Contains methods for Category import.
 * Decreases complexity, responsibility and simplifies unit testing of CategoryAddOrReplaceStrategy.
 */
class CategoryImportExportHelper
{
    private const CATEGORY_PATH_DELIMITER = ' / ';

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var string */
    private $unescapedDelimiter;

    /** @var string */
    private $escapedDelimiter;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        $this->unescapedDelimiter = trim(self::CATEGORY_PATH_DELIMITER);
        $this->escapedDelimiter = $this->unescapedDelimiter.$this->unescapedDelimiter;
    }

    public function getCategoryPath(Category $category): string
    {
        // Collects categories titles path.
        $categoryPath = [];
        /** @var Category|null $eachCategory */
        while ($eachCategory = (!empty($eachCategory) ? $eachCategory->getParentCategory() : $category)) {
            $title = $this->escapeTitle((string)$eachCategory->getTitle());
            array_unshift($categoryPath, $title);
        }

        return implode(self::CATEGORY_PATH_DELIMITER, $categoryPath);
    }

    public function getPersistedCategoryPath(Category $category): string
    {
        $categoryPath = array_map([$this, 'escapeTitle'], $this->getRepository()->getCategoryPath($category));

        return implode(self::CATEGORY_PATH_DELIMITER, $categoryPath);
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
        $categoryPathParts = explode(self::CATEGORY_PATH_DELIMITER, $categoryPath);
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
     * Get root category by organization(do not use organization from session or check from acl!)
     */
    public function getRootCategory(Organization $organization): Category
    {
        $queryBuilder = $this->getRepository()->getMasterCatalogRootQueryBuilder();
        $queryBuilder
            ->andWhere('category.organization = :organization')
            ->setParameter('organization', $organization);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    public function getMaxLeft(): int
    {
        return $this->getRepository()->getMaxLeft();
    }

    private function escapeTitle(string $title): string
    {
        return str_replace($this->unescapedDelimiter, $this->escapedDelimiter, $title);
    }

    private function unescapeTitle(string $title): string
    {
        return str_replace($this->escapedDelimiter, $this->unescapedDelimiter, $title);
    }

    private function getRepository(): CategoryRepository
    {
        return $this->doctrine
            ->getManagerForClass(Category::class)
            ->getRepository(Category::class);
    }
}
