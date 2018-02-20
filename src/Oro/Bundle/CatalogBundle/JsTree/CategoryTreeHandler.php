<?php

namespace Oro\Bundle\CatalogBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

class CategoryTreeHandler extends AbstractTreeHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var CategoryTreeProvider */
    protected $categoryTreeProvider;

    /**
     * {@inheritdoc}
     *
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(
        $entityClass,
        ManagerRegistry $managerRegistry,
        TokenAccessorInterface $tokenAccessor,
        CategoryTreeProvider $categoryTreeProvider
    ) {
        parent::__construct($entityClass, $managerRegistry);

        $this->tokenAccessor = $tokenAccessor;
        $this->categoryTreeProvider = $categoryTreeProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNodes($root, $includeRoot)
    {
        return $this->categoryTreeProvider->getCategories(
            $this->tokenAccessor->getUser(),
            $root,
            $includeRoot
        );
    }

    /**
     * {@inheritdoc}
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

        return $category;
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
            'text'   => $entity->getDenormalizedDefaultTitle(),
            'state'  => [
                'opened' => $entity->getParentCategory() === null
            ]
        ];
    }
}
