<?php

namespace OroB2B\Bundle\CatalogBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryTreeHandler
{
    /** @var ManagerRegistry */
    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine  = $doctrine;
    }

    /**
     * @param int $selectedCategoryId
     * @return array
     */
    public function createTree($selectedCategoryId)
    {
        $categoryTree = [
            'categories' => $this->doctrine
                ->getRepository('OroB2BCatalogBundle:Category')
                ->getChildren(null, false, 'left', 'ASC'),
            'selected' => (int)$selectedCategoryId
        ];

        return $this->formatTree($categoryTree);
    }
    
    /**
     * @param array $tree
     * @return array
     */
    protected function formatTree(array $tree)
    {
        $formattedTree = [];
        $selectedCategoryId = $tree['selected'];

        foreach ($tree['categories'] as $category) {
            $formattedTree[] = $this->formatCategory($category, $selectedCategoryId);
        }

        return $formattedTree;
    }

    /**
     * Returns an array formatted as:
     * array(
     *     'id'     => int,    // tree item id
     *     'parent' => int,    // tree item parent id
     *     'text'   => string  // tree item label
     * )
     *
     * @param Category $category
     * @param int $selectedCategoryId
     * @return array
     */
    protected function formatCategory(Category $category, $selectedCategoryId)
    {
        return array(
            'id' => $category->getId(),
            'parent' => $category->getParentCategory() ? $category->getParentCategory()->getId() : '#',
            'text' => $category->getDefaultTitle()->getString(),
            'state' => [
                'selected' => ($category->getId() === $selectedCategoryId) ? true : false
            ]
        );
    }
}
