<?php

namespace OroB2B\Bundle\CMSBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\CMSBundle\Entity\Repository\PageRepository;
use OroB2B\Bundle\RedirectBundle\Manager\SlugManager;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;

class PageTreeHandler extends AbstractTreeHandler
{
    /**
     * @var SlugManager
     */
    protected $slugManager;

    /**
     * @param string $entityClass
     * @param ManagerRegistry $managerRegistry
     * @param SlugManager $slugManager
     */
    public function __construct($entityClass, ManagerRegistry $managerRegistry, SlugManager $slugManager)
    {
        parent::__construct($entityClass, $managerRegistry);

        $this->slugManager = $slugManager;
    }

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
     * Need to sort root nodes by ID
     *
     * @param array $entities
     * @return array
     */
    protected function formatTree(array $entities)
    {
        $rootNodes = [];

        /** @var Page $page */
        foreach ($entities as $key => $page) {
            if (!$page->getParentPage()) {
                unset($entities[$key]);
                $rootNodes[] = $page;
            }
        }

        uasort($rootNodes, function (Page $a, Page $b) {
            return $a->getId() > $b->getId() ? 1 : -1;
        });

        $entities = array_merge($rootNodes, $entities);

        return parent::formatTree($entities);
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
        /** @var PageRepository $entityRepository */
        $entityRepository = $this->getEntityRepository();

        /** @var page $page */
        $page = $entityRepository->find($entityId);
        /** @var page $parentPage */
        $parentPage = $entityRepository->find($parentId);

        if (null === $parentPage) {
            $page->setParentPage(null);
        } else {
            if ($parentPage->getChildPages()->contains($page)) {
                $parentPage->removeChildPage($page);
            }

            $parentPage->addChildPage($page);

            if ($position) {
                $children = array_values($parentPage->getChildPages()->toArray());
                $entityRepository->persistAsNextSiblingOf($page, $children[$position - 1]);
            } else {
                $entityRepository->persistAsFirstChildOf($page, $parentPage);
            }
        }

        $this->slugManager->makeUrlUnique($page->getCurrentSlug());
    }
}
