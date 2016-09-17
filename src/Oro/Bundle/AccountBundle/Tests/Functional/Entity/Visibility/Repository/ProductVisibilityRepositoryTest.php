<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class ProductVisibilityRepositoryTest extends AbstractProductVisibilityRepositoryTestCase
{
    /**
     * @var ProductVisibilityRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(ProductVisibility::class);

        $this->loadFixtures(
            [
                'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    /**
     * @dataProvider setToDefaultWithoutCategoryDataProvider
     * @param $categoryName
     * @param array $expected
     */
    public function testSetToDefaultWithoutCategory($categoryName, array $expected)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);
        $this->deleteCategory($category);
        $queryHelper = $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
        $this->repository->setToDefaultWithoutCategory($queryHelper);
        $actual = $this->getProductsByVisibilities();

        $this->assertSameSize($expected, $actual);
        foreach ($actual as $value) {
            $this->assertContains($value, $expected);
        }
    }

    /**
     * @return array
     */
    public function setToDefaultWithoutCategoryDataProvider()
    {
        return [
            'Delete FOURTH_LEVEL2' => [
                'categoryName' => LoadCategoryData::FOURTH_LEVEL2,
                'expected' => [
                    [
                        'product' => 'product.1',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::CONFIG
                    ],

                    [
                        'product' => 'product.2',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::VISIBLE
                    ],
                    [
                        'product' => 'product.3',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::VISIBLE
                    ],
                    [
                        'product' => 'product.4',
                        'website' => 'Default',
                        'visibility' => ProductVisibility::HIDDEN
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
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getProductsByVisibilities()
    {
        $website = $this->getDefaultWebsite();
        return array_map(
            function (ProductVisibility $visibility) {
                return [
                    'product' => $visibility->getProduct()->getSku(),
                    'website' => $visibility->getWebsite()->getName(),
                    'visibility' => $visibility->getVisibility()
                ];
            },
            $this->repository->findBy(['website' => $website])
        );
    }

    /**
     * @return \Oro\Bundle\WebsiteBundle\Entity\Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroWebsiteBundle:Website')
            ->getDefaultWebsite();
    }
}
