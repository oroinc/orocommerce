<?php

namespace Oro\Bundle\CMSBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Entity\Repository\PageRepository;
use Oro\Bundle\RedirectBundle\Manager\SlugManager;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

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
            'parent' => $entity->getParentPage() ? $entity->getParentPage()->getId() : null,
            'text'   => $entity->getTitle(),
            'state'  => [
                'opened' => $entity->getParentPage() === null
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getNodes($root, $includeRoot)
    {
        $entities = parent::getNodes($root, $includeRoot);
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

        return array_merge($rootNodes, $entities);
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
