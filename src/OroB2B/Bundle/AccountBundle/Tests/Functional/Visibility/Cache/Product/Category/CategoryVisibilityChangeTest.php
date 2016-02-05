<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Tests\Functional\VisibilityTrait;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CategoryVisibilityChangeTest extends CategoryCacheTestCase
{
    use VisibilityTrait;

    /**
     * @dataProvider visibilityChangeDataProvider
     *
     * @param string $categoryReference
     * @param array $visibility
     * @param array $expectedData
     */
    public function testVisibilityChange($categoryReference, array $visibility, array $expectedData)
    {
        $categoryVisibility = $this->getVisibilityEntity($categoryReference, $visibility);

        $originalVisibility = $categoryVisibility->getVisibility();

        $categoryVisibility->setVisibility($visibility['visibility']);
        $this->updateVisibility($this->getContainer()->get('doctrine'), $categoryVisibility);
        $this->assertProductVisibilityResolvedCorrect($expectedData);

        $categoryVisibility->setVisibility($originalVisibility);
        $this->updateVisibility($this->getContainer()->get('doctrine'), $categoryVisibility);
    }

    /**
     * @param $categoryReference
     * @param array $visibility
     * @return \OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface
     */
    protected function getVisibilityEntity($categoryReference, array $visibility)
    {
        $registry = $this->getContainer()->get('doctrine');
        /** @var Category $category */
        $category = $this->getReference($categoryReference);

        switch ($visibility['type']) {
            case 'all':
                $visibilityEntity = $this->getCategoryVisibility($registry, $category);
                break;
            case 'account':
                /** @var Account $account */
                $account = $this->getReference($visibility[$visibility['type']]);
                $visibilityEntity = $this->getCategoryVisibilityForAccount($registry, $category, $account);
                break;
            case 'accountGroup':
                /** @var AccountGroup $accountGroup */
                $accountGroup = $this->getReference($visibility[$visibility['type']]);
                $visibilityEntity = $this->getCategoryVisibilityForAccountGroup($registry, $category, $accountGroup);
                break;
            default:
                throw new \InvalidArgumentException('Unknown visibility type');
        }

        return $visibilityEntity;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function visibilityChangeDataProvider()
    {
        return [
            'change visibility to all to parent category' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'all',
                    'visibility' => CategoryVisibility::PARENT_CATEGORY,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility to all to config' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'all',
                    'visibility' => CategoryVisibility::CONFIG,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ],
                    ],
                    'hiddenProducts' => [
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility to all to hidden' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'all',
                    'visibility' => CategoryVisibility::HIDDEN,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1',
                        'category_1_2',
                        'category_1_5',
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5',
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.2.1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1' => [
                            'category_1_2',
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                            'category_1_5',
                            'category_1_5_6',
                        ],
                        'account.level_1.2.1.1' => [
                            'category_1_5',
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.4' => [
                            'category_1_2',
                            'category_1_5',
                        ],
                    ],
                    'hiddenProducts' => [
                        'product.1',
                        'product.2',
                        'product.5',
                        'product.4',
                        'product.7',
                        'product.8',
                    ],
                    'hiddenProductsByAccountGroups' => [
                        'account_group.group2' => [
                            'product.1',
                            'product.2',
                            'product.5',
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
                            'product.5',
                            'product.4',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.2' => [
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.2.1' => [
                            'product.1',
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.5',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.2.1.1' => [
                            'product.1',
                            'product.2',
                            'product.5',
                            'product.4',
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
                            'product.1',
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.5',
                            'product.4',
                        ],
                        'account.level_1.4' => [
                            'product.2',
                            'product.3',
                            'product.5',
                        ],
                    ],
                ]
            ],
            'change visibility account group to hidden' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'accountGroup',
                    'accountGroup' => 'account_group.group3',
                    'visibility' => AccountGroupCategoryVisibility::HIDDEN,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ],
                        'account_group.group3' => [
                            'category_1',
                            'category_1_2_3',
                            'category_1_2_3_4',
                            'category_1_5',
                            'category_1_5_6',
                            'category_1_5_6_7',
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
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                            'product.1',
                            'product.3',
                            'product.6',
                            'product.5',
                            'product.4',
                            'product.7',
                            'product.8',
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
                            'product.1',
                            'product.3',
                            'product.6',
                            'product.5',
                            'product.4',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.3.1.1' => [
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.4' => [
                            'product.3',
                            'product.4',
                        ],
                    ],
                ]
            ],
            'change visibility account group to parent category' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'accountGroup',
                    'accountGroup' => 'account_group.group2',
                    'visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility account group to all' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'accountGroup',
                    'accountGroup' => 'account_group.group3',
                    'visibility' => AccountGroupCategoryVisibility::CATEGORY,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility account group to visible' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'accountGroup',
                    'accountGroup' => 'account_group.group3',
                    'visibility' => AccountGroupCategoryVisibility::VISIBLE,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility account to parent category' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'account',
                    'account' => 'account.level_1.2.1.1',
                    'visibility' => AccountCategoryVisibility::PARENT_CATEGORY,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility account to hidden' => [
                'categoryReference' => 'category_1',
                'visibility' => [
                    'type' => 'account',
                    'account' => 'account.level_1.2.1.1',
                    'visibility' => AccountCategoryVisibility::HIDDEN,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                        'account.level_1.2.1.1' => [
                            'category_1',
                            'category_1_5',
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1' => [
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ],
                    ],
                    'hiddenProducts' => [
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
                        'account.level_1.2.1.1' => [
                            'product.1',
                            'product.5',
                            'product.4',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.3.1' => [
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility account to visible' => [
                'categoryReference' => 'category_1_2_3',
                'visibility' => [
                    'type' => 'account',
                    'account' => 'account.level_1.1',
                    'visibility' => AccountCategoryVisibility::VISIBLE,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.2.1' => [
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1' => [
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                            'product.3',
                            'product.6',
                        ],
                    ],
                    'hiddenProductsByAccounts' => [
                        'account.level_1.1' => [
                            'product.1',
                            'product.2',
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility account to all' => [
                'categoryReference' => 'category_1_5_6',
                'visibility' => [
                    'type' => 'account',
                    'account' => 'account.level_1.2.1.1',
                    'visibility' => AccountCategoryVisibility::CATEGORY,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                        'account.level_1.2.1.1' => [
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1' => [
                            'category_1_5_6',
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                        'account.level_1.2.1.1' => [
                            'product.4',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.3.1' => [
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
                            'product.3',
                        ],
                    ],
                ]
            ],
            'change visibility account to group' => [
                'categoryReference' => 'category_1_5_6',
                'visibility' => [
                    'type' => 'account',
                    'account' => 'account.level_1.3.1',
                    'visibility' => AccountCategoryVisibility::ACCOUNT_GROUP,
                ],
                'expectedData' => [
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategoriesByAccountGroups' => [
                        'account_group.group1' => [
                            'category_1',
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
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
                            'category_1_5_6_7',
                        ],
                        'account.level_1.3.1.1' => [
                            'category_1_2',
                            'category_1_2_3',
                            'category_1_2_3_4',
                        ]
                    ],
                    'hiddenProducts' => [
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
                            'product.3',
                            'product.6',
                            'product.7',
                            'product.8',
                        ],
                        'account.level_1.3.1.1' => [
                            'product.2',
                            'product.3',
                            'product.6',
                        ],
                        'account.level_1.4' => [
                            'product.3',
                        ],
                    ],
                ]
            ],
        ];
    }
}
