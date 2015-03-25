<?php

namespace OroB2B\Bundle\CatalogBundle\JsTree;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;

class CategoryTreeHandler extends AbstractTreeHandler
{
    /**
     * @return array
     */
    public function createTree()
    {
        $tree = $this->getEntityRepository()
            ->getChildrenWithTitles(null, false, 'left', 'ASC');

        return $this->formatTree($tree);
    }

    /**
     * Move node processing
     *
     * @param int $entityId
     * @param int $parentId
     * @param int $position
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
        /** @var Category $category */
        $category = $this->getEntityRepository()->find($entityId);
        /** @var Category $parentCategory */
        $parentCategory = $this->getEntityRepository()->find($parentId);

        if ($parentCategory->getChildCategories()->contains($category)) {
            $parentCategory->removeChildCategory($category);
        }

        $parentCategory->addChildCategory($category);

        if ($position) {
            $children = array_values($parentCategory->getChildCategories()->toArray());
            $this->getEntityRepository()->persistAsNextSiblingOf($category, $children[$position - 1]);
        } else {
            $this->getEntityRepository()->persistAsFirstChildOf($category, $parentCategory);
        }
    }

    /**
     * Returns an array formatted as:
     * array(
     *     'id'     => int,    // tree item id
     *     'parent' => int,    // tree item parent id
     *     'text'   => string  // tree item label
     * )
     *
     * @param Category $entity
     * @return array
     */
    protected function formatEntity($entity)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParentCategory() ? $entity->getParentCategory()->getId() : '#',
            'text'   => $entity->getDefaultTitle()->getString(),
            'state'  => [
                'opened' => $entity->getParentCategory() === null
            ]
        ];
    }
}
