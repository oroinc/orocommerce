<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;

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
     * @param CategoryRepository $categoryRepository
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param UserInterface|null $user
     * @param Category|null $root
     * @param bool $includeRoot
     * @return Category[]
     */
    public function getCategories($user, $root = null, $includeRoot = true)
    {
        $categories = $this->categoryRepository->getChildrenWithTitles($root, false, 'left', 'ASC', $includeRoot);

        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($user);
        $this->eventDispatcher->dispatch(CategoryTreeCreateAfterEvent::NAME, $event);

        return $event->getCategories();
    }
}
