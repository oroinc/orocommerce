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

    protected function setUp()
    {
        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(['deleteAll', 'delete', 'fetch', 'save'])
            ->getMockForAbstractClass();

        $this->calculator = $this->getMock('OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator');

        $this->storage = new CategoryVisibilityStorage($this->cacheProvider, $this->calculator);
    }

    protected function tearDown()
    {
        unset($this->cacheProvider, $this->calculator, $this->storage);
    }

    /**
     * @dataProvider getCategoryVisibilityDataProvider
     *
     * @param int|null accountId
     * @param array|null $cacheValue
     * @param array|null $calcValue
     * @param array $expectedCacheValue
     * @param CategoryVisibilityData $expectedValue
     */
    public function testGetCategoryVisibilityData(
        $accountId,
        $cacheValue,
        $calcValue,
        array $expectedCacheValue,
        CategoryVisibilityData $expectedValue
    ) {
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->willReturn($cacheValue);

        if ($calcValue) {
            $this->calculator->expects($this->once())
                ->method('getVisibility')
                ->with($accountId)
                ->willReturn($calcValue);

            $this->cacheProvider->expects($this->once())
                ->method('save')
                ->with(
                    $accountId ?: CategoryVisibilityStorage::ANONYMOUS_CACHE_KEY,
                    $expectedCacheValue
                );
        }

        $this->assertEquals($expectedValue, $this->storage->getCategoryVisibilityData($accountId));
    }

    /**
     * @return array
     */
    public function getCategoryVisibilityDataProvider()
    {
        return [
            'exist in cache' => [
                'accountId' => self::ACCOUNT_ID,
                'cacheValue' => [
                    'visibility' => true,
                    'ids' => [1, 2, 3]
                ],
                'calcValue' => null,
                'expectedCacheValue' => [
                    'visibility' => true,
                    'ids' => [1, 2, 3]
                ],
                'expectedValue' => new CategoryVisibilityData([1, 2, 3], true)
            ],
            'not exist in cache invisible' => [
                'accountId' => self::ACCOUNT_ID,
                'cacheValue' => null,
                'calcValue' => [
                    'visible' => [1, 2, 3],
                    'invisible' => [42]
                ],
                'expectedCacheValue' => [
                    'visibility' => false,
                    'ids' => [42]
                ],
                'expectedValue' => new CategoryVisibilityData([42], false)
            ],
            'not exist in cache visible' => [
                'accountId' => self::ACCOUNT_ID,
                'cacheValue' => null,
                'calcValue' => [
                    'visible' => [4, 5, 6],
                    'invisible' => [7, 8, 9, 10, 11, 12]
                ],
                'expectedCacheValue' => [
                    'visibility' => true,
                    'ids' => [4, 5, 6]
                ],
                'expectedValue' => new CategoryVisibilityData([4, 5, 6], true)
            ],
            'not exist in cache visible for anonymous' => [
                'accountId' => null,
                'cacheValue' => null,
                'calcValue' => [
                    'visible' => [4, 5, 6],
                    'invisible' => [7, 8, 9, 10, 11, 12]
                ],
                'expectedCacheValue' => [
                    'visibility' => true,
                    'ids' => [4, 5, 6]
                ],
                'expectedValue' => new CategoryVisibilityData([4, 5, 6], true)
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
