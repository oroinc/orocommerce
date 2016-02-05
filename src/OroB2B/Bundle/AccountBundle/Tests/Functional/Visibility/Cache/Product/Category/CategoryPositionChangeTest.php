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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function positionChangeDataProvider()
    {
        return [
            [
                'categoryReference' => 'category_1_2',
                'newParentCategoryReference' => 'category_1_5_6',
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_2',
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                        ],
                        'account_group.group3' => [
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ],
                    ],
                    'hiddenCategoriesByAccounts' => [
                        'account.level_1' => [
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.2.1' => [
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1' => [
                            'category_1_2',
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ],
                        'account.level_1.4' => [
                            'category_1_2',

                        ]
                    ],
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
