<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder;

/**
 * @dbIsolation
 */
class CacheBuilderTest extends CacheBuilderTestCase
{
    /**
     * @var CacheBuilder
     */
    protected $cacheBuilder;

    /**
     * @return string
     */
    protected function getCacheBuilderContainerId()
    {
        return 'orob2b_account.visibility.cache.product.category.cache_builder';
    }

    /**
     * @dataProvider visibilityChangeDataProvider
     *
     * @param string $visibilityReference
     * @param string $visibility
     * @param array $expectedData
     */
    public function testVisibilityChange($visibilityReference, $visibility, array $expectedData)
    {
        /** @var VisibilityInterface $categoryVisibility */
        $categoryVisibility = $this->getReference($visibilityReference);

        $categoryVisibility->setVisibility($visibility);

        $em = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:Visibility\CategoryVisibility');
        $em->flush();

        $this->cacheBuilder->resolveVisibilitySettings($categoryVisibility);

        $this->assertProductVisibilityResolvedCorrect($expectedData);

        $categoryVisibility->setVisibility($this->getInversedVisibility($visibility));
        $em->flush();
        $this->flushCategoryVisibilityCache();

        $this->cacheBuilder->resolveVisibilitySettings($categoryVisibility);
    }

    /**
     * @param string $visibility
     * @return string
     */
    protected function getInversedVisibility($visibility)
    {
        return $visibility === CategoryVisibility::VISIBLE ? CategoryVisibility::HIDDEN : CategoryVisibility::VISIBLE;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function visibilityChangeDataProvider()
    {
        return [
            'change visibility to all' => [
                'visibilityReference' => 'category_1.visibility.all',
                'visibility' => CategoryVisibility::HIDDEN,
                'expectedData' => [
                    'hiddenProducts' => [
                        'product.1',
                        'product.2',
                        'product.5',
                        'product.4',
                        'product.7',
                    ],
                    'hiddenProductsByAccountGroups' => [
                        'account_group.group2' => [
                            'product.1',
                            'product.2',
                            'product.5',
                            'product.7',
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
                        ],
                        'account.level_1.2' => [
                            'product.7',
                        ],
                        'account.level_1.2.1' => [
                            'product.1',
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.5',
                            'product.7',
                        ],
                        'account.level_1.2.1.1' => [
                            'product.1',
                            'product.2',
                            'product.5',
                            'product.4',
                            'product.7',
                        ],
                        'account.level_1.3.1' => [
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.4',
                            'product.7',
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
            'change visibility account group' => [
                'visibilityReference' => 'category_1.visibility.account_group.group3',
                'visibility' => CategoryVisibility::HIDDEN,
                'expectedData' => [
                    'hiddenProducts' => [
                        'product.4',
                        'product.7',
                    ],
                    'hiddenProductsByAccountGroups' => [
                        'account_group.group2' => [
                            'product.7',
                        ],
                        'account_group.group3' => [
                            'product.1',
                            'product.3',
                            'product.6',
                            'product.5',
                            'product.4',
                            'product.7',
                        ],
                    ],
                    'hiddenProductsByAccounts' => [
                        'account.level_1.1' => [
                            'product.4',
                            'product.7',
                        ],
                        'account.level_1.2' => [
                            'product.7',
                        ],
                        'account.level_1.2.1' => [
                            'product.7',
                        ],
                        'account.level_1.3.1' => [
                            'product.1',
                            'product.3',
                            'product.6',
                            'product.5',
                            'product.4',
                            'product.7',
                        ],
                        'account.level_1.3.1.1' => [
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.7',
                        ],
                        'account.level_1.4' => [
                            'product.3',
                            'product.4',
                        ],
                    ],
                ]
            ],
            'change visibility account' => [
                'visibilityReference' => 'category_1.visibility.account.level_1.2.1',
                'visibility' => CategoryVisibility::HIDDEN,
                'expectedData' => [
                    'hiddenProducts' => [
                        'product.4',
                        'product.7',
                    ],
                    'hiddenProductsByAccountGroups' => [
                        'account_group.group2' => [
                            'product.7',
                        ],
                        'account_group.group3' => [
                            'product.3',
                            'product.6',
                        ],
                    ],
                    'hiddenProductsByAccounts' => [
                        'account.level_1.1' => [
                            'product.4',
                            'product.7',
                        ],
                        'account.level_1.2' => [
                            'product.7',
                        ],
                        'account.level_1.2.1' => [
                            'product.1',
                            'product.2',
                            'product.3',
                            'product.6',
                            'product.7',
                        ],
                        'account.level_1.3.1' => [
                            'product.3',
                            'product.6',
                            'product.4',
                            'product.7',
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
