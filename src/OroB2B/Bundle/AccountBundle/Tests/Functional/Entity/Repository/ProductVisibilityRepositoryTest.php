<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\ProductVisibilityRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class ProductVisibilityRepositoryTest extends WebTestCase
{
    /** @var ProductVisibilityRepository */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData'
            ]
        );
    }

    /**
     * @dataProvider updateToConfigProductVisibilityDataProvider
     * @param $categoryName
     * @param array $expected
     */
    public function testUpdateToConfigProductVisibility($categoryName, array $expected)
    {
        $this->setProductsVisibilitiesToDefault();

        /** @var Category $category */
        $category = $this->getReference($categoryName);

        // updateToConfigProductVisibility called in CategoryListener when removed category
        $this->deleteCategory($category);

        $productVisibilities = $this->repository->findAll();

        $productVisibilities = array_map(
            function (ProductVisibility $visibility) {
                return [
                    'product' => $visibility->getProduct()->getSku(),
                    'website' => $visibility->getWebsite()->getName(),
                    'visibility' => $visibility->getVisibility()
                ];
            },
            $productVisibilities
        );

        foreach ($productVisibilities as $productVisibility) {
            $this->assertContains($productVisibility, $expected);
        }
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateToConfigProductVisibilityDataProvider()
    {
        return [
            'Delete Test Third Level 2' => [
                'categoryName' => LoadCategoryData::THIRD_LEVEL2,
                'expected' => [
                    [
                        'product' => 'product.4',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.5',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.7',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],

                    [
                        'product' => 'product.8',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.4',
                        'website' => 'US',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.5',
                        'website' => 'US',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.7',
                        'website' => 'US',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.8',
                        'website' => 'US',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.4',
                        'website' => 'Canada',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.5',
                        'website' => 'Canada',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.7',
                        'website' => 'Canada',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.8',
                        'website' => 'Canada',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                ]
            ],
        ];
    }

    /**
     * Remove all product visibilities.
     * It's similarly to set product visibilities to default value 'Visibility To Parent Category'.
     */
    protected function setProductsVisibilitiesToDefault()
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:Visibility\ProductVisibility');

        $productVisibilities = $this->repository->findAll();

        foreach ($productVisibilities as $productVisibility) {
            $em->remove($productVisibility);
        }

        $em->flush();
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
