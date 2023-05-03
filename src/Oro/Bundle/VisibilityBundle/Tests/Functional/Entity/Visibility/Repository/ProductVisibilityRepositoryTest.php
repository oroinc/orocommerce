<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Visibility\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\ProductVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

/**
 * @group CommunityEdition
 */
class ProductVisibilityRepositoryTest extends AbstractProductVisibilityRepositoryTestCase
{
    /** @var ProductVisibilityRepository */
    protected $repository;

    /** @var InsertFromSelectQueryExecutor */
    private $insertExecutor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->repository = self::getContainer()->get('oro_visibility.product_raw_repository');
        $this->insertExecutor = $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');

        $this->loadFixtures([LoadCategoryProductData::class, LoadProductVisibilityData::class]);
    }

    /**
     * @dataProvider setToDefaultWithoutCategoryDataProvider
     */
    public function testSetToDefaultWithoutCategory(string $categoryName, array $deletedCategoryProducts)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);
        $this->deleteCategory($category);
        $scopes = self::getContainer()->get('oro_scope.scope_manager')
            ->findRelatedScopes(ProductVisibility::VISIBILITY_TYPE);
        foreach ($scopes as $scope) {
            $this->repository->setToDefaultWithoutCategory($this->insertExecutor, $scope);
            $actual = $this->getProductsByVisibilitiesScope($scope);
            self::assertSameSize($deletedCategoryProducts, $actual);
            foreach ($actual as $value) {
                self::assertContains($value, $deletedCategoryProducts);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setToDefaultWithoutCategoryDataProvider(): array
    {
        return [
            'Delete FOURTH_LEVEL2' => [
                'categoryName' => LoadCategoryData::FOURTH_LEVEL2,
                'expected' => [
                    [
                        'product' => 'product-1',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product-2',
                        'visibility' => ProductVisibility::VISIBLE
                    ],
                    [
                        'product' => 'product-3',
                        'visibility' => ProductVisibility::VISIBLE
                    ],
                    [
                        'product' => 'product-4',
                        'visibility' => ProductVisibility::HIDDEN
                    ],
                    [
                        'product' => 'product-5',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product-6',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'продукт-7',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'product-8',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                    [
                        'product' => 'продукт-9',
                        'visibility' => ProductVisibility::CONFIG
                    ],
                ]
            ],
        ];
    }

    private function getProductsByVisibilitiesScope(Scope $scope): array
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
