<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Calculator;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityCalculator;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityData;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class CategoryVisibilityCalculatorTest extends WebTestCase
{
    /**
     * @var CategoryVisibilityCalculator
     */
    protected $calculator;

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
        ]);

        $this->calculator = $this->getContainer()->get('orob2b_account.calculator.category_visibility_calculator');
    }

    /**
     * @dataProvider testCalculateDataProvider
     * @param array $expectedData
     */
    public function testCalculate(array $expectedData)
    {
        $result = $this->calculator->calculate();

        $this->assertCategoriesVisibility($expectedData, $result);
    }

    /**
     * @return array
     */
    public function testCalculateDataProvider()
    {
        return [
            [
                'expectedData' => [
                    'visibleCategories' => [
                        'Master catalog',
                        'category_1',
                        'category_1_2',
                        'category_1_2_3',
                        'category_1_2_3_4',
                        'category_1_5',
                    ],
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider testCalculateForAccountGroupDataProvider
     * @param string $accountGroupReference
     * @param array $expectedData
     */
    public function testCalculateForAccountGroup($accountGroupReference, $expectedData)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupReference);

        $result = $this->calculator->calculateForAccountGroup($accountGroup);

        $this->assertCategoriesVisibility($expectedData, $result);
    }

    /**
     * @return array
     */
    public function testCalculateForAccountGroupDataProvider()
    {
        return [
            [
                'accountGroupReference' => 'account_group.group1',
                'expectedData' => [
                    'visibleCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'hiddenCategories' => [
                        'category_1',
                        'category_1_2',
                        'category_1_2_3',
                        'category_1_2_3_4',
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider testCalculateForAccountDataProvider
     * @param string $accountReference
     * @param array $expectedData
     */
    public function testCalculateForAccount($accountReference, $expectedData)
    {
        /** @var Account $account */
        $account = $this->getReference($accountReference);

        $data = $this->calculator->calculateForAccount($account);

        $this->assertCategoriesVisibility($expectedData, $data);
    }

    /**
     * @return array
     */
    public function testCalculateForAccountDataProvider()
    {
        return [
            [
                'accountReference' => 'account.level_1',
                'expectedData' => [
                    'visibleCategories' => [
                        'category_1',
                    ],
                    'hiddenCategories' => [
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ]
                ],
            ],
        ];
    }

    /**
     * @param array $expectedData
     * @param CategoryVisibilityData $data
     */
    protected function assertCategoriesVisibility(array $expectedData, CategoryVisibilityData $data)
    {
        $this->assertCount(count($expectedData['visibleCategories']), $data->getVisibleCategoryIds());
        foreach ($expectedData['visibleCategories'] as $categoryReference) {
            $category = $this->getCategory($categoryReference);
            $this->assertContains($category->getId(), $data->getVisibleCategoryIds());
            $this->assertNotContains($category->getId(), $data->getHiddenCategoryIds());
            $this->assertTrue($data->isCategoryVisible($category->getId()));
        }

        $this->assertCount(count($expectedData['hiddenCategories']), $data->getHiddenCategoryIds());
        foreach ($expectedData['hiddenCategories'] as $categoryReference) {
            $category = $this->getCategory($categoryReference);
            $this->assertContains($category->getId(), $data->getHiddenCategoryIds());
            $this->assertNotContains($category->getId(), $data->getVisibleCategoryIds());
            $this->assertFalse($data->isCategoryVisible($category->getId()));
        }
    }

    /**
     * @param string $categoryReference
     * @return Category
     */
    protected function getCategory($categoryReference)
    {
        if ($categoryReference === 'Master catalog') {
            return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BCatalogBundle:Category')
                ->getRepository('OroB2BCatalogBundle:Category')->getMasterCatalogRoot();
        }
        return $this->getReference($categoryReference);
    }
}
