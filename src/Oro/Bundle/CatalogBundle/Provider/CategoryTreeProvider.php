<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
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
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var MasterCatalogRootProvider
     */
    private $masterCatalogRootProvider;

    /**
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     * @param MasterCatalogRootProvider $masterCatalogRootProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        MasterCatalogRootProvider $masterCatalogRootProvider
    ) {
        $this->registry = $registry;
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

        $categories = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->getChildren($root, false, 'left', 'ASC', $includeRoot);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->dispatch(CategoryTreeCreateAfterEvent::NAME, $event);

        return $event->getCategories();
    }
}
