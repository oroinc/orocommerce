<?php

namespace OroB2B\Bundle\CatalogBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManager;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryTreeHandler
{
    const SUCCESS_STATUS = 1;
    const ERROR_STATUS = 0;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @var NestedTreeRepository
     */
    protected $categoryRepository;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        $this->categoryRepository = $managerRegistry->getRepository('OroB2BCatalogBundle:Category');
    }

    /**
     * @return array
     */
    public function createTree()
    {
        $categoryTree = $this->managerRegistry
            ->getRepository('OroB2BCatalogBundle:Category')
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
        $status = ['moved' => self::SUCCESS_STATUS];

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManagerForClass('OroB2BCatalogBundle:Category');
        $connection = $em->getConnection();

        $connection->beginTransaction();

        try {
            if ($parentId == '#') {
                throw new \LogicException('Can not create root catalog');
            }

            $category = $this->categoryRepository->find($categoryId);
            $parentCategory = $this->categoryRepository->find($parentId);

            $category->setParentCategory($parentCategory);
            $this->categoryRepository->persistAsFirstChildOf($category, $parentCategory);
            $em->flush();

            if ($position) {
                $this->categoryRepository->moveDown($category, $position);
                $em->flush();
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $status['moved'] = self::ERROR_STATUS;
            $status['error'] = $e->getMessage();
        }

        return ['status' => $status];
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
}
