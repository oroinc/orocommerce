<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Model\Action\ChangeCategoryVisibility;

/**
 * @dbIsolation
 */
class ChangeCategoryVisibilityTest extends CategoryCaseActionTestCase
{
    /**
     * @var ChangeCategoryVisibility
     */
    protected $action;

    /**
     * @return string
     */
    protected function getActionContainerId()
    {
        return 'orob2b_account.model.action.change_category_visibility';
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

        $this->context->expects($this->exactly(1))
            ->method('getEntity')
            ->willReturn($categoryVisibility);

        $this->action->execute($this->context);

        $this->assertProductVisibilityResolvedCorrect($expectedData);

        // TODO waiting for CategoryVisibilityResolver implementation
//        $categoryVisibility->setVisibility($this->getInversedVisibility($visibility));
//
//        $this->action->execute($this->context);
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
            // TODO waiting for CategoryVisibilityResolver implementation
//            'change visibility account group' => [
//                'visibilityReference' => 'category_1.visibility.account_group.group3',
//                'visibility' => CategoryVisibility::HIDDEN,
//                'expectedData' => [
//                    'hiddenProducts' => [
//                        'product.4',
//                        'product.7',
//                    ],
//                    'hiddenProductsByAccountGroups' => [
//                        'account_group.group2' => [
//                            'product.7',
//                        ],
//                        'account_group.group3' => [
//                            'product.1',
//                            'product.3',
//                            'product.6',
//                            'product.5',
//                            'product.4',
//                            'product.7',
//                        ],
//                    ],
//                    'hiddenProductsByAccounts' => [
//                        'account.level_1.1' => [
//                            'product.4',
//                            'product.7',
//                        ],
//                        'account.level_1.2' => [
//                            'product.7',
//                        ],
//                        'account.level_1.2.1' => [
//                            'product.7',
//                        ],
//                        'account.level_1.3.1' => [
//                            'product.1',
//                            'product.3',
//                            'product.6',
//                            'product.5',
//                            'product.4',
//                            'product.7',
//                        ],
//                        'account.level_1.3.1.1' => [
//                            'product.2',
//                            'product.3',
//                            'product.6',
//                            'product.7',
//                        ],
//                        'account.level_1.4' => [
//                            'product.3',
//                            'product.4',
//                        ],
//                    ],
//                ]
//            ],
//            'change visibility account' => [
//                'visibilityReference' => 'category_1.visibility.account.level_1.2.1',
//                'visibility' => CategoryVisibility::HIDDEN,
//                'expectedData' => [
//                    'hiddenProducts' => [
//                        'product.4',
//                        'product.7',
//                    ],
//                    'hiddenProductsByAccountGroups' => [
//                        'account_group.group2' => [
//                            'product.7',
//                        ],
//                        'account_group.group3' => [
//                            'product.3',
//                            'product.6',
//                        ],
//                    ],
//                    'hiddenProductsByAccounts' => [
//                        'account.level_1.1' => [
//                            'product.4',
//                            'product.7',
//                        ],
//                        'account.level_1.2' => [
//                            'product.7',
//                        ],
//                        'account.level_1.2.1' => [
//                            'product.1',
//                            'product.2',
//                            'product.3',
//                            'product.6',
//                            'product.7',
//                        ],
//                        'account.level_1.3.1' => [
//                            'product.3',
//                            'product.6',
//                            'product.4',
//                            'product.7',
//                        ],
//                        'account.level_1.3.1.1' => [
//                            'product.2',
//                            'product.3',
//                            'product.6',
//                            'product.4',
//                        ],
//                        'account.level_1.4' => [
//                            'product.3',
//                        ],
//                    ],
//                ]
//            ],
        ];
    }
}
