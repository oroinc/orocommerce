<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Storage;

use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityData;

class CategoryVisibilityDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider gettersDataProvider
     * @param array $visibleIds
     * @param array $hiddenIds
     * @param $categoryId
     * @param $expectedVisibility
     */
    public function testGetters(array $visibleIds, array $hiddenIds, $categoryId, $expectedVisibility)
    {
        $categoryVisibilityData = new CategoryVisibilityData($visibleIds, $hiddenIds);
        $this->assertEquals($visibleIds, $categoryVisibilityData->getVisibleCategoryIds());
        $this->assertEquals($hiddenIds, $categoryVisibilityData->getHiddenCategoryIds());
        $this->assertEquals($expectedVisibility, $categoryVisibilityData->isCategoryVisible($categoryId));
    }

    /**
     * @return array
     */
    public function gettersDataProvider()
    {
        return [
            'visible categoryId' => [
                'visibleIds' => [1, 2, 3],
                'hiddenIds' => [4, 5, 6],
                'categoryId' => 3,
                'expectedVisibility' => true,
            ],
            'hidden categoryId' => [
                'visibleIds' => [1, 2, 3],
                'hiddenIds' => [4, 5, 6],
                'categoryId' => 4,
                'expectedVisibility' => false,
            ],
        ];
    }

    /**
     * @dataProvider mergeDataProvider
     * @param CategoryVisibilityData $first
     * @param CategoryVisibilityData $second
     * @param array $expectedData
     */
    public function testMerge(CategoryVisibilityData $first, CategoryVisibilityData $second, array $expectedData)
    {
        $data = $first->merge($second);
        $this->assertCount(count($expectedData['visible']), $data->getVisibleCategoryIds());
        foreach ($data->getVisibleCategoryIds() as $id) {
            $this->assertContains($id, $expectedData['visible']);
        }
        $this->assertCount(count($expectedData['hidden']), $data->getHiddenCategoryIds());
        foreach ($data->getHiddenCategoryIds() as $id) {
            $this->assertContains($id, $expectedData['hidden']);
        }
    }

    /**
     * @return array
     */
    public function mergeDataProvider()
    {
        return [
            'hidden' => [
                'first' => new CategoryVisibilityData([1, 2, 3], [4, 5]),
                'second' => new CategoryVisibilityData([1, 2], [3, 5]),
                'expectedData' =>[
                    'visible' => [1, 2],
                    'hidden' => [3, 4, 5],
                ],
            ],
            'visible' => [
                'first' => new CategoryVisibilityData([1, 2, 3], [4, 5]),
                'second' => new CategoryVisibilityData([1, 2, 4], []),
                'expectedData' =>[
                    'visible' => [1, 2, 3, 4],
                    'hidden' => [5],
                ],
            ],
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Ids [2, 3] are contained in visible and hidden arrays
     */
    public function testConstructor()
    {
        new CategoryVisibilityData([1, 2, 3], [2, 3, 4]);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Ids [2, 3] are contained in visible and hidden arrays
     */
    public function testExceptionFromArray()
    {
        CategoryVisibilityData::fromArray([
            CategoryVisibilityData::VISIBLE_KEY => [1, 2, 3],
            CategoryVisibilityData::HIDDEN_KEY => [2, 3, 4]
        ]);
    }
}
