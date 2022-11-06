<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;

abstract class AbstractProductVisibilityRepositoryTestCase extends WebTestCase
{
    /** @var EntityRepository */
    protected $repository;

    abstract public function setToDefaultWithoutCategoryDataProvider(): array;

    /**
     * @dataProvider setToDefaultWithoutCategoryDataProvider
     */
    public function testSetToDefaultWithoutCategory(string $categoryName, array $deletedCategoryProducts)
    {
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            self::assertContains($deletedCategoryProduct, $this->getProductsByVisibilities());
        }

        /** @var Category $category */
        $category = $this->getReference($categoryName);
        $this->deleteCategory($category);
        $this->repository->setToDefaultWithoutCategory();
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            self::assertNotContains($deletedCategoryProduct, $this->getProductsByVisibilities());
        }
    }

    protected function getProductsByVisibilities(): array
    {
        return array_map(
            function (VisibilityInterface $visibility) {
                return $visibility->getTargetEntity()->getSku();
            },
            $this->repository->findAll()
        );
    }

    protected function deleteCategory(Category $category): void
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Category::class);
        $em->remove($category);
        $em->flush();
    }
}
