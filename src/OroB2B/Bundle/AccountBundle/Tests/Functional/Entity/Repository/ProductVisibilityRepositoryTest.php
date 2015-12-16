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

        $this->assertEquals($expected, $productVisibilities);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateToConfigProductVisibilityDataProvider()
    {
        return [
            'Delete FOURTH_LEVEL2' => [
                'categoryName' => LoadCategoryData::FOURTH_LEVEL2,
                'expected' => [
                    [
                        'product' => 'product.7',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],

                    [
                        'product' => 'product.8',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ]
                ]
            ],
        ];
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
