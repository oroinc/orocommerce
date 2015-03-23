<?php

namespace OroB2B\Bundle\CMSBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\CMSBundle\Entity\Repository\PageRepository;
use OroB2B\Bundle\RedirectBundle\Manager\SlugManager;

class PageTreeHandler
{
    const SUCCESS_STATUS = true;
    const ERROR_STATUS   = false;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var SlugManager
     */
    protected $slugManager;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry, SlugManager $slugManager)
    {
        $this->managerRegistry = $managerRegistry;
        $this->slugManager     = $slugManager;
    }

    /**
     * @return array
     */
    public function createTree()
    {
        $tree = $this->getPageRepository()
            ->getChildren(null, false, 'left', 'ASC');

        return $this->formatTree($tree);
    }

    /**
     * Move a page to another parent page
     *
     * @param int $pageId
     * @param int $parentId
     * @param int $position
     * @return array
     */
    public function movePage($pageId, $parentId, $position)
    {
        $status = ['status' => self::SUCCESS_STATUS];

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass('OroB2BCMSBundle:Page');
        $connection = $em->getConnection();

        $connection->beginTransaction();

        try {
            /** @var page $page */
            $page = $this->getPageRepository()->find($pageId);
            /** @var page $parentPage */
            $parentPage = $this->getPageRepository()->find($parentId);

            if (null === $parentPage) {
                $page->setParentPage(null);

                if ($position) {
                    $this->getPageRepository()->persistAsNextSibling($page);
                } else {
                    $this->getPageRepository()->persistAsFirstChild($page);
                }
            } else {
                if ($parentPage->getChildPages()->contains($page)) {
                    $parentPage->removeChildPage($page);
                }

                $parentPage->addChildPage($page);

                if ($position) {
                    $children = array_values($parentPage->getChildPages()->toArray());
                    $this->getPageRepository()->persistAsNextSiblingOf($page, $children[$position - 1]);
                } else {
                    $this->getPageRepository()->persistAsFirstChildOf($page, $parentPage);
                }
            }

            $this->slugManager->makeUrlUnique($page->getCurrentSlug());

            $em->flush();
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $status['status'] = self::ERROR_STATUS;
            $status['error']  = $e->getMessage();
        }

        return $status;
    }

    /**
     * @param Page[] $pages
     * @return array
     */
    protected function formatTree($pages)
    {
        $formattedTree = [];

        foreach ($pages as $page) {
            $formattedTree[] = $this->formatPage($page);
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
     * @param Page $page
     * @return array
     */
    protected function formatPage(Page $page)
    {
        return [
            'id'     => $page->getId(),
            'parent' => $page->getParentPage() ? $page->getParentPage()->getId() : '#',
            'text'   => $page->getTitle(),
            'state'  => [
                'opened' => $page->getParentPage() === null
            ]
        ];
    }

    /**
     * @return PageRepository
     */
    protected function getPageRepository()
    {
        return $this->managerRegistry->getRepository('OroB2BCMSBundle:Page');
    }
}
