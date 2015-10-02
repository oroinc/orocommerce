<?php

namespace OroB2B\Bundle\CatalogBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Component\Tree\Handler\AbstractTreeHandler;

class CategoryTreeHandler extends AbstractTreeHandler
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var CategoryVisibilityStorage */
    protected $categoryVisibilityStorage;

    /**
     * {@inheritdoc}
     *
     * @param SecurityFacade $securityFacade
     * @param CategoryVisibilityStorage $categoryVisibilityStorage
     */
    public function __construct(
        $entityClass,
        ManagerRegistry $managerRegistry,
        SecurityFacade $securityFacade,
        CategoryVisibilityStorage $categoryVisibilityStorage
    ) {
        parent::__construct($entityClass, $managerRegistry);

        $this->securityFacade = $securityFacade;
        $this->categoryVisibilityStorage = $categoryVisibilityStorage;
    }

    /**
     * @return array
     */
    public function createTree()
    {
        $categories = $this->getEntityRepository()->getChildrenWithTitles(null, false, 'left', 'ASC');
        $categories = $this->formatTree($categories);

        $user = $this->securityFacade->getLoggedUser();
        if (!$user instanceof User) {
            /** @var AccountUser $user */
            $categories = $this->filterCategories(
                $categories,
                $user instanceof AccountUser ? $user->getAccount()->getId() : null
            );
        }

        return $categories;
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
            'parent' => $entity->getParentCategory() ? $entity->getParentCategory()->getId() : '#',
            'text'   => $entity->getDefaultTitle()->getString(),
            'state'  => [
                'opened' => $entity->getParentCategory() === null
            ]
        ];
    }

    /**
     * @param array|Category[] $categories
     * @param int|null $accountId
     * @return array
     */
    protected function filterCategories(array $categories, $accountId = null)
    {
        $visibilityData = $this->categoryVisibilityStorage->getData($accountId);

        $isVisible = $visibilityData->isVisible();
        $ids = $visibilityData->getIds();

        $categoriesTree = $this->buildCategoriesTree($categories);
        foreach ($categoriesTree as &$category) {
            $inIds = in_array($category['id'], $ids, true);
            if (($isVisible && !$inIds) || (!$isVisible && $inIds)) {
                $this->unsetTree($categoriesTree, $category);
            }
            unset($category['children']);
        }

        return array_values($categoriesTree);
    }

    /**
     * @param array $categories
     * @return array
     */
    protected function buildCategoriesTree(array $categories)
    {
        $tree = [];

        foreach ($categories as $category) {
            $tree[$category['id']] = $category;
        }

        foreach ($tree as &$category) {
            $parentId = $category['parent'];

            if ($parentId && $parentId !== '#') {
                $tree[$category['parent']]['children'][] = &$category;
            }
        }

        return $tree;
    }

    /**
     * @param array $tree
     * @param array $category
     */
    protected function unsetTree(array &$tree, array $category)
    {
        unset($tree[$category['id']]);

        if (array_key_exists('children', $category)) {
            foreach ($category['children'] as $child) {
                $this->unsetTree($tree, $child);
            }
        }
    }
}
