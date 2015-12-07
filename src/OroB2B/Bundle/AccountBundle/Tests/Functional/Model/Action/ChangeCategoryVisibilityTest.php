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
        // TODO
        return 'container.id';
    }

    /**
     * @dataProvider visibilityChangeDataProvider
     *
     * @param string $categoryVisibilityReference
     * @param string $visibility
     * @param array $expectedData
     */
    public function testVisibilityChange($categoryVisibilityReference, $visibility, array $expectedData)
    {
        $this->markTestIncomplete('Waiting for action service');

        /** @var VisibilityInterface $categoryVisibility */
        $categoryVisibility = $this->getReference($categoryVisibilityReference);

        $categoryVisibility->setVisibility($visibility);

        $this->context->expects($this->once())
            ->method('getEntity')
            ->willReturn($categoryVisibility);

        $this->action->execute($this->context);

        $this->assertProductVisibilityResolvedCorrect($expectedData);
    }

    /**
     * @return array
     */
    public function visibilityChangeDataProvider()
    {
        return [
            [
                'categoryVisibilityReference' => 'category_1.visibility.all',
                'visibility' => CategoryVisibility::HIDDEN,
                'expectedData' => [
                    'hiddenProducts' => [
                        'product.1',
                        'product.2',
                        'product.5',
                        'product.6',
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
                            'product.5',
                            'product.4',
                            'product.7',
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
        ];
    }
}
