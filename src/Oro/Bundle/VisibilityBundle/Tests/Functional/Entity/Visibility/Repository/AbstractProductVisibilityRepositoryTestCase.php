<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
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
            static::assertContains($deletedCategoryProduct, $this->getProductsByVisibilities());
        }

        /** @var Category $category */
        $category = $this->getReference($categoryName);
        $this->deleteCategory($category);
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            static::assertNotContains($deletedCategoryProduct, $this->getProductsByVisibilities());
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
        /* @var $em EntityManager */
        $em = static::getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroCatalogBundle:Category');

        $em->remove($category);
        $em->flush();
    }
}
