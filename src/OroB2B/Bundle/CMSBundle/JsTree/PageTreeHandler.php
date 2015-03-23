<?php

namespace OroB2B\Bundle\CMSBundle\JsTree;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;

class PageTreeHandler extends AbstractTreeHandler
{
    /**
     * Returns an array formatted as:
     * array(
     *     'id'     => int,    // tree item id
     *     'parent' => int,    // tree item parent id
     *     'text'   => string  // tree item label
     * )
     *
     * @param Page $entity
     * @return array
     */
    protected function formatEntity($entity)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParentPage() ? $entity->getParentPage()->getId() : '#',
            'text'   => $entity->getTitle(),
            'state'  => [
                'opened' => $entity->getParentPage() === null
            ]
        ];
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
    }
}
