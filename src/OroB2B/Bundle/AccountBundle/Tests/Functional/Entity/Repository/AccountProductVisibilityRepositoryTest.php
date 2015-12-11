<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountProductVisibilityRepositoryTest extends WebTestCase
{
    /** @var AccountProductVisibilityRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData'
            ]
        );
    }

    /**
     * @dataProvider setToDefaultValueAccountProductVisibilityForProductsWithoutCategoryDataProvider
     * @param string $categoryName
     * @param array $deletedCategoryProducts
     */
    public function testSetToDefaultValueAccountProductVisibilityForProductsWithoutCategory(
        $categoryName,
        array $deletedCategoryProducts
    ) {
        $productsAccountProductVisibilitiesBefore = $this->getProductsAccountProductVisibilities();
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            $this->assertContains($deletedCategoryProduct, $productsAccountProductVisibilitiesBefore);
        }

        /** @var Category $category */
        $category = $this->getReference($categoryName);
        $this->deleteCategory($category);

        $productsAccountProductVisibilitiesAfter = $this->getProductsAccountProductVisibilities();
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            $this->assertNotContains($deletedCategoryProduct, $productsAccountProductVisibilitiesAfter);
        }
    }

    /**
     * @return array
     */
    public function setToDefaultValueAccountProductVisibilityForProductsWithoutCategoryDataProvider()
    {
        return [
            [
                'category' => LoadCategoryData::FOURTH_LEVEL2,
                'deletedCategoryProducts' => ['product.8'],
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getProductsAccountProductVisibilities()
    {
        $accountProductVisibilities = $this->repository->findAll();

        $accountProductVisibilities = array_map(
            function (AccountProductVisibility $visibility) {
                return $visibility->getProduct()->getSku();
            },
            $accountProductVisibilities
        );

        return $accountProductVisibilities;
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
