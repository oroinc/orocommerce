<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractProductVisibilityRepositoryTestCase extends WebTestCase
{
    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @return array
     */
    abstract public function setToDefaultWithoutCategoryDataProvider();

    /**
     * @dataProvider setToDefaultWithoutCategoryDataProvider
     * @param string $categoryName
     * @param array $deletedCategoryProducts
     */
    public function testSetToDefaultWithoutCategory(
        $categoryName,
        array $deletedCategoryProducts
    ) {
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            $this->assertContains($deletedCategoryProduct, $this->getProductsByVisibilities());
        }

        /** @var Category $category */
        $category = $this->getReference($categoryName);
        $this->deleteCategory($category);
        $this->repository->setToDefaultWithoutCategory();
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            $this->assertNotContains($deletedCategoryProduct, $this->getProductsByVisibilities());
        }
    }

    /**
     * @return array
     */
    protected function getProductsByVisibilities()
    {
        return array_map(
            function (VisibilityInterface $visibility) {
                return $visibility->getTargetEntity()->getSku();
            },
            $this->repository->findAll()
        );
    }

    /**
     * @param Category $category
     */
    protected function deleteCategory(Category $category)
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category');

        $em->remove($category);
        $em->flush();
    }
}
