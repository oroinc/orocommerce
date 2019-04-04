<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Returns category tree restricted by certain user as well as category root (will be suggested by current organization
 * if no passed explicitly )
 */
class CategoryTreeProvider
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var MasterCatalogRootProvider
     */
    private $masterCatalogRootProvider;

    /**
     * @param CategoryRepository $categoryRepository
     * @param EventDispatcherInterface $eventDispatcher
     * @param MasterCatalogRootProvider $masterCatalogRootProvider
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        EventDispatcherInterface $eventDispatcher,
        MasterCatalogRootProvider $masterCatalogRootProvider
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->masterCatalogRootProvider = $masterCatalogRootProvider;
    }

    /**
     * @param UserInterface|null $user
     * @param Category|null $root
     * @param bool $includeRoot
     * @return Category[]
     */
    public function getCategories($user, $root = null, $includeRoot = true)
    {
        if (!$root) {
            $root = $this->masterCatalogRootProvider->getMasterCatalogRootForCurrentOrganization();
        }
        $categories = $this->categoryRepository->getChildren($root, false, 'left', 'ASC', $includeRoot);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->dispatch(CategoryTreeCreateAfterEvent::NAME, $event);

        return $event->getCategories();
    }
}
