<?php

namespace Oro\Bundle\CatalogBundle\JsTree;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

/**
 * Provides actions that can be performed with the category tree
 */
class CategoryTreeHandler extends AbstractTreeHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var CategoryTreeProvider */
    protected $categoryTreeProvider;

    /** @var MasterCatalogRootProviderInterface */
    private $masterCatalogRootProvider;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        $entityClass,
        ManagerRegistry $managerRegistry,
        TokenAccessorInterface $tokenAccessor,
        CategoryTreeProvider $categoryTreeProvider,
        MasterCatalogRootProviderInterface $masterCatalogRootProvider
    ) {
        parent::__construct($entityClass, $managerRegistry);

        $this->tokenAccessor = $tokenAccessor;
        $this->categoryTreeProvider = $categoryTreeProvider;
        $this->masterCatalogRootProvider = $masterCatalogRootProvider;
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
     * @param bool $includeRoot
     * @return array
     */
    public function createTreeByMasterCatalogRoot($includeRoot = true)
    {
        $root = $this->masterCatalogRootProvider->getMasterCatalogRoot();
        $tree = $this->getNodes($root, $includeRoot);

        return $this->formatTree($tree, $root, $includeRoot);
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
