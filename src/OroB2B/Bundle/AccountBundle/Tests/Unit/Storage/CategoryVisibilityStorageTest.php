<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Storage;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityData;
use OroB2B\Bundle\AccountBundle\Storage\CategoryVisibilityStorage;

class CategoryVisibilityStorageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    const ACCOUNT_ID = 42;

    /** @var CategoryVisibilityStorage */
    protected $storage;

    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $cacheProvider;

    /** @var CategoryVisibilityCalculator|\PHPUnit_Framework_MockObject_MockObject */
    protected $calculator;

    /** @var Account */
    protected $account;

    protected function setUp()
    {
        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(['deleteAll', 'delete', 'fetch', 'save'])
            ->getMockForAbstractClass();

        $this->calculator = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = new CategoryVisibilityStorage($this->cacheProvider, $this->calculator);
        $this->account = $this->createEntity('OroB2B\Bundle\AccountBundle\Entity\Account', self::ACCOUNT_ID);
    }

    protected function tearDown()
    {
        unset($this->cacheProvider, $this->calculator, $this->storage);
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param Account|null account
     * @param array|null $cacheValue
     * @param array|null $calcValue
     * @param array $expectedCacheValue
     * @param CategoryVisibilityData $expectedValue
     */
    public function testGetData(
        $account,
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
                ->with($account)
                ->willReturn($calcValue);
            $accountId = (null !== $account) ? $account->getId() : null;
            $this->cacheProvider->expects($this->once())
                ->method('save')
                ->with(
                    $accountId ?: CategoryVisibilityStorage::ANONYMOUS_CACHE_KEY,
                    $expectedCacheValue
                );
        }

        $this->assertEquals($expectedValue, $this->storage->getData($account));
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            'exist in cache' => [
                'account' => $this->account,
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
                'account' => $this->account,
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
                'account' => $this->account,
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
                'account' => null,
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
