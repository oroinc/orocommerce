<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

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
        $this->client->useHashNavigation(true);
        $this->repository = static::getContainer()
            ->get('doctrine')
            ->getRepository(ProductVisibility::class);

        $this->loadFixtures(
            [
                LoadCategoryProductData::class,
                LoadProductVisibilityData::class,
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
        $queryHelper = static::getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
        $scopes = static::getContainer()->get('oro_scope.scope_manager')->findRelatedScopes('product_visibility');
        foreach ($scopes as $scope) {
            $this->repository->setToDefaultWithoutCategory($queryHelper, $scope);
            $actual = $this->getProductsByVisibilitiesScope($scope);
            static::assertSameSize($expected, $actual);
            foreach ($actual as $value) {
                static::assertContains($value, $expected);
            }
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
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.2',
                        'visibility' => ProductVisibility::VISIBLE
                    ],
                    [
                        'product' => 'product.3',
                        'visibility' => ProductVisibility::VISIBLE
                    ],
                    [
                        'product' => 'product.4',
                        'visibility' => ProductVisibility::HIDDEN
                    ],
                    [
                        'product' => 'product.5',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.6',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.7',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product.8',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                ]
            ],
        ];
    }

    /**
     * @param Scope $scope
     * @return array
     */
    protected function getProductsByVisibilitiesScope(Scope $scope)
    {
        return array_map(
            function (ProductVisibility $visibility) {
                return [
                    'product' => $visibility->getProduct()->getSku(),
                    'visibility' => $visibility->getVisibility()
                ];
            },
            $this->repository->findBy(['scope' => $scope])
        );
    }
}
