<?php

namespace OroB2B\Bundle\CMSBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\CMSBundle\Entity\Repository\PageRepository;

class PageTreeHandler
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return array
     */
    public function createTree()
    {
        $categoryTree = $this->getPageRepository()
            ->getChildren(null, false, 'left', 'ASC');

        return $this->formatTree($categoryTree);
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
