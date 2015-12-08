<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Model\Action;

use OroB2B\Bundle\AccountBundle\Model\Action\ChangeCategoryPosition;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class ChangeCategoryPositionTest extends CategoryCaseActionTestCase
{
    /**
     * @var ChangeCategoryPosition
     */
    protected $action;

    /**
     * @return string
     */
    protected function getActionContainerId()
    {
        return 'orob2b_account.model.action.change_category_position';
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

        $this->context->expects($this->once())
            ->method('getEntity')
            ->willReturn($category);

        $this->action->execute($this->context);

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
                            'product.4',
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
