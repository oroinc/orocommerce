<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\ProductResolvedCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class CategoryPositionChangeTest extends CategoryCacheTestCase
{
    /**
     * @var ProductResolvedCacheBuilder
     */
    protected $cacheBuilder;

    /**
     * @return string
     */
    protected function getCacheBuilderContainerId()
    {
        return 'orob2b_account.visibility.cache.product.category.product_resolved_cache_builder';
    }

    /**
     * @dataProvider positionChangeDataProvider
     *
     * @param string $categoryReference
     * @param string $newParentCategoryReference
     * @param array $expectedData
     */
    public function testPositionChange($categoryReference, $newParentCategoryReference, array $expectedData)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        /** @var Category $newParentCategory */
        $newParentCategory = $this->getReference($newParentCategoryReference);

        $category->setParentCategory($newParentCategory);

        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility')
            ->flush();

        $this->assertProductVisibilityResolvedCorrect($expectedData);
    }

    /**
     * @return array
     */
    public function positionChangeDataProvider()
    {
        return [
            [
                'categoryReference' => 'category_1_2',
                'newParentCategoryReference' => 'category_1_5_6',
                'expectedData' => [
                    'hiddenProducts' => [
                        'product.2',
                        'product.4',
                        'product.7',
                        'product.8',
                    ],
                    'hiddenProductsByAccountGroups' => [
                        'account_group.group2' => [
                            'product.7',
                            'product.8',
                        ],
                        'account_group.group3' => [
                            'product.2',
                            'product.3',
                            'product.6',
                        ],
                    ],
                    'hiddenProductsByAccounts' => [
                        'account.level_1.1' => [
                            'product.1',
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.4',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.2' => [
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.2.1' => [
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.3.1' => [
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.4',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.3.1.1' => [
                            'product.2',
                            'product.3',
                            'product.6',
                        ],
                        'account.level_1.4' => [
                            'product.2',
                            'product.3',
                        ],
                    ],
                ]
            ],
        ];
    }
}
