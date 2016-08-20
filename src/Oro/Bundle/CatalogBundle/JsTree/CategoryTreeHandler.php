<?php

namespace Oro\Bundle\CatalogBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

class CategoryTreeHandler extends AbstractTreeHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @var CategoryTreeProvider
     */
    protected $categoryTreeProvider;

    /**
     * {@inheritdoc}
     *
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        $entityClass,
        ManagerRegistry $managerRegistry,
        SecurityFacade $securityFacade,
        CategoryTreeProvider $categoryTreeProvider
    ) {
        parent::__construct($entityClass, $managerRegistry);

        $this->securityFacade = $securityFacade;
        $this->categoryTreeProvider = $categoryTreeProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNodes($root, $includeRoot)
    {
        return $this->categoryTreeProvider->getCategories(
            $this->securityFacade->getLoggedUser(),
            $root,
            $includeRoot
        );
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
     * @return array
     */
    protected function formatEntity($entity)
    {
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParentCategory() ? $entity->getParentCategory()->getId() : null,
            'text'   => $entity->getDefaultTitle()->getString(),
            'state'  => [
                'opened' => $entity->getParentCategory() === null
            ]
        ];
    }
}
