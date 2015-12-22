<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

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
            ->getManagerForClass('OroB2BCatalogBundle:Category');

        $em->remove($category);
        $em->flush();
    }
}
