<?php

namespace OroB2B\Bundle\CatalogBundle\JsTree;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\CatalogBundle\Event\CategoryTreeCreateAfterEvent;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

class CategoryTreeHandler extends AbstractTreeHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * {@inheritdoc}
     *
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        $entityClass,
        ManagerRegistry $managerRegistry,
        SecurityFacade $securityFacade,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($entityClass, $managerRegistry);

        $this->securityFacade = $securityFacade;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes($root, $includeRoot)
    {
        /** @var CategoryRepository $repository */
        $repository = $this->getEntityRepository();
        $categories = $repository->getChildrenWithTitles(null, false, 'left', 'ASC');
        $event = new CategoryTreeCreateAfterEvent($categories);
        $event->setUser($this->securityFacade->getLoggedUser());
        $this->eventDispatcher->dispatch(CategoryTreeCreateAfterEvent::NAME, $event);

        return $event->getCategories();
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
        /** @var Category $category */
        $category = $this->getEntityRepository()->find($entityId);
        /** @var Category $parentCategory */
        $parentCategory = $this->getEntityRepository()->find($parentId);

        if ($parentCategory->getChildCategories()->contains($category)) {
            $parentCategory->removeChildCategory($category);
        }

        $parentCategory->addChildCategory($category);

        if ($position) {
            $children = array_values($parentCategory->getChildCategories()->toArray());
            $this->getEntityRepository()->persistAsNextSiblingOf($category, $children[$position - 1]);
        } else {
            $this->getEntityRepository()->persistAsFirstChildOf($category, $parentCategory);
        }
    }

    /**
     * Returns an array formatted as:
     * array(
     *     'id'     => int,    // tree item id
     *     'parent' => int,    // tree item parent id
     *     'text'   => string  // tree item label
     * )
     *
     * @param Category $entity
     * @param Category|null $root
     * @return array
     */
    protected function formatEntity($entity, $root)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParentCategory() ? $entity->getParentCategory()->getId() : '#',
            'text'   => $entity->getDefaultTitle()->getString(),
            'state'  => [
                'opened' => $entity->getParentCategory() === null
            ]
        ];
    }
}
