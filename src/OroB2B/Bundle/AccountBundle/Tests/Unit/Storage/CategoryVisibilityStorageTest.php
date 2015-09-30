<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Storage;

use Doctrine\Common\Cache\CacheProvider;

use OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityData;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage;

class CategoryVisibilityStorageTest extends \PHPUnit_Framework_TestCase
{
    const ACCOUNT_ID = 42;

    /** @var CategoryVisibilityStorage */
    protected $storage;

    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $cacheProvider;

    /** @var CategoryVisibilityCalculator|\PHPUnit_Framework_MockObject_MockObject */
    protected $calculator;

    public function setUp()
    {
        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(['deleteAll', 'delete', 'fetch', 'save'])
            ->getMockForAbstractClass();

        $this->calculator = $this->getMock('OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator');

        $this->storage = new CategoryVisibilityStorage($this->cacheProvider, $this->calculator);
    }

    /**
     * @dataProvider getCategoryVisibilityDataProvider
     * @param array|null $cacheValue
     * @param array|null $calcValue
     * @param CategoryVisibilityData $expectedValue
     */
    public function testGetCategoryVisibilityData($cacheValue, $calcValue, CategoryVisibilityData $expectedValue)
    {
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->willReturn($cacheValue);

        if ($calcValue) {
            $this->calculator->expects($this->once())
                ->method('getVisibility')
                ->with(self::ACCOUNT_ID)
                ->willReturn($calcValue);
        }

        $this->assertEquals($expectedValue, $this->storage->getCategoryVisibilityData(self::ACCOUNT_ID));
    }

    /**
     * @return array
     */
    public function getCategoryVisibilityDataProvider()
    {
        return [
            'exist in cache' => [
                'cacheValue' => [
                    'visibility' => true,
                    'ids' => [1, 2, 3]
                ],
                'calcValue' => null,
                'expectedValue' => new CategoryVisibilityData(true, [1, 2, 3])
            ],
            'not exist in cache invisible' => [
                'cacheValue' => null,
                'calcValue' => [
                    'visible' => [1, 2, 3],
                    'invisible' => [42]
                ],
                'expectedValue' => new CategoryVisibilityData(false, [42])
            ],
            'not exist in cache visible' => [
                'cacheValue' => null,
                'calcValue' => [
                    'visible' => [4, 5, 6],
                    'invisible' => [7, 8, 9, 10, 11, 12]
                ],
                'expectedValue' => new CategoryVisibilityData(true, [4, 5, 6])
            ]
        ];
    }

    public function testClearAllData()
    {
        $this->cacheProvider->expects($this->once())
            ->method('deleteAll');

        $this->storage->clearData();
    }

    public function testClearDataForAccount()
    {
        $this->cacheProvider->expects($this->any())
            ->method('delete')
            ->with(self::ACCOUNT_ID);

        $this->storage->clearData([self::ACCOUNT_ID]);
    }
}
