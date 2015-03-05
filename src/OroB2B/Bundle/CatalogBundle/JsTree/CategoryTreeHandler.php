<?php

namespace OroB2B\Bundle\CatalogBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

class CategoryTreeHandler
{
    const SUCCESS_STATUS = 1;
    const ERROR_STATUS = 0;

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
        $categoryTree = $this->getCategoryRepository()
            ->getChildrenWithTitles(null, false, 'left', 'ASC');

        return $this->formatTree($categoryTree);
    }

    /**
     * Move a category to another parent category
     *
     * @param int $categoryId
     * @param int $parentId
     * @param int $position
     * @return array
     */
    public function moveCategory($categoryId, $parentId, $position)
    {
        $status = ['status' => self::SUCCESS_STATUS];

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass('OroB2BCatalogBundle:Category');
        $connection = $em->getConnection();

        $connection->beginTransaction();

        try {
            /**
             * @var Category $category
             */
            $category = $this->getCategoryRepository()->find($categoryId);
            /**
             * @var Category $parentCategory
             */
            $parentCategory = $this->getCategoryRepository()->find($parentId);

            if ($parentCategory->getChildCategories()->contains($category)) {
                $parentCategory->removeChildCategory($category);
            }

            $parentCategory->addChildCategory($category);

            if ($position) {
                $children = array_values($parentCategory->getChildCategories()->toArray());
                $this->getCategoryRepository()->persistAsNextSiblingOf($category, $children[$position - 1]);
            } else {
                $this->getCategoryRepository()->persistAsFirstChildOf($category, $parentCategory);
            }

            $em->flush();
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $status['status'] = self::ERROR_STATUS;
            $status['error'] = $e->getMessage();
        }

        return $status;
    }
    
    /**
     * @param Category[] $categories
     * @return array
     */
    protected function formatTree($categories)
    {
        $formattedTree = [];

        foreach ($categories as $category) {
            $formattedTree[] = $this->formatCategory($category);
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
     * @param Category $category
     * @return array
     */
    protected function formatCategory(Category $category)
    {
        return [
            'id' => $category->getId(),
            'parent' => $category->getParentCategory() ? $category->getParentCategory()->getId() : '#',
            'text' => $category->getDefaultTitle()->getString(),
            'state' => [
                'opened' => $category->getParentCategory() === null
            ]
        ];
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        return $this->managerRegistry->getRepository('OroB2BCatalogBundle:Category');
    }
}
