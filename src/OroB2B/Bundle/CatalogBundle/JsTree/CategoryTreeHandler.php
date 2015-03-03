<?php

namespace OroB2B\Bundle\CatalogBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryTreeHandler
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine  = $doctrine;
    }

    /**
     * @return array
     */
    public function createTree()
    {
        $categoryTree = $this->doctrine
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getChildren(null, false, 'left', 'ASC');

        return $this->formatTree($categoryTree);
    }
    
    /**
     * @param Category[] $categories
     * @return array
     */
    protected function formatTree($categories)
    {
        $formattedTree = [];

        foreach ($categories as $category) {
            $formattedTree[] = $this->formatCategory($category);
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
     * @return array
     */
    protected function formatCategory(Category $category)
    {
        return [
            'id' => $category->getId(),
            'parent' => $category->getParentCategory() ? $category->getParentCategory()->getId() : '#',
            'text' => $category->getDefaultTitle()->getString(),
            'state' => [
                'opened' => $category->getParentCategory() === null
            ]
        ];
    }
}
