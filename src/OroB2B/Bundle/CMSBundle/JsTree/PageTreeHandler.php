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
        /** @var page $page */
        $page = $this->getEntityRepository()->find($entityId);
        /** @var page $parentPage */
        $parentPage = $this->getEntityRepository()->find($parentId);

        if (null === $parentPage) {
            $page->setParentPage(null);

            if ($position) {
                $this->getEntityRepository()->persistAsNextSibling($page);
            } else {
                $this->getEntityRepository()->persistAsFirstChild($page);
            }
        } else {
            if ($parentPage->getChildPages()->contains($page)) {
                $parentPage->removeChildPage($page);
            }

            $parentPage->addChildPage($page);

            if ($position) {
                $children = array_values($parentPage->getChildPages()->toArray());
                $this->getEntityRepository()->persistAsNextSiblingOf($page, $children[$position - 1]);
            } else {
                $this->getEntityRepository()->persistAsFirstChildOf($page, $parentPage);
            }
        }
    }
}
