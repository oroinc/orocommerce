<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountGroupProductVisibilityRepositoryTest extends WebTestCase
{
    /** @var AccountGroupProductVisibilityRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData'
            ]
        );
    }

    /**
     * @dataProvider setToDefaultValueAccountGroupProductVisibilityForProductsWithoutCategoryDataProvider
     * @param string $categoryName
     * @param array $deletedCategoryProducts
     */
    public function testSetToDefaultValueAccountGroupProductVisibilityForProductsWithoutCategory(
        $categoryName,
        array $deletedCategoryProducts
    ) {
        $productsAccountGroupProductVisibilitiesBefore = $this->getProductsAccountGroupProductVisibilities();
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            $this->assertContains($deletedCategoryProduct, $productsAccountGroupProductVisibilitiesBefore);
        }

        /** @var Category $category */
        $category = $this->getReference($categoryName);
        $this->deleteCategory($category);

        $productsAccountGroupProductVisibilitiesAfter = $this->getProductsAccountGroupProductVisibilities();
        foreach ($deletedCategoryProducts as $deletedCategoryProduct) {
            $this->assertNotContains($deletedCategoryProduct, $productsAccountGroupProductVisibilitiesAfter);
        }
    }

    /**
     * @return array
     */
    public function setToDefaultValueAccountGroupProductVisibilityForProductsWithoutCategoryDataProvider()
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
    protected function getProductsAccountGroupProductVisibilities()
    {
        $AccountGroupProductVisibilities = $this->repository->findAll();

        $AccountGroupProductVisibilities = array_map(
            function (AccountGroupProductVisibility $visibility) {
                return $visibility->getProduct()->getSku();
            },
            $AccountGroupProductVisibilities
        );

        return $AccountGroupProductVisibilities;
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
