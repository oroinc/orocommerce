<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Returns category tree restricted by certain user as well as category root (will be suggested by current organization
 * if not passed explicitly)
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
     * @var MasterCatalogRootProviderInterface
     */
    private $masterCatalogRootProvider;

    public function __construct(
        ManagerRegistry $registry,
        EventDispatcherInterface $eventDispatcher,
        MasterCatalogRootProviderInterface $masterCatalogRootProvider
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
            $root = $this->masterCatalogRootProvider->getMasterCatalogRoot();
        }

        $categories = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->getChildren($root, false, 'left', 'ASC', $includeRoot);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->dispatch($event, CategoryTreeCreateAfterEvent::NAME);

        return $event->getCategories();
    }

    /**
     * @return Category[]
     */
    public function getParentCategories(?UserInterface $user, Category $category): array
    {
        $categories = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->getPath($category);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->dispatch($event, CategoryTreeCreateAfterEvent::NAME);

        return $event->getCategories();
    }
}
