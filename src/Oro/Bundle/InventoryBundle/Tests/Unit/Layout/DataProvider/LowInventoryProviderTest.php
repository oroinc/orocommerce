<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Layout\DataProvider\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class LowInventoryProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LowInventoryQuantityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lowInventoryQuantityManager;

    /**
     * @var LowInventoryProvider
     */
    protected $lowInventoryProvider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->lowInventoryQuantityManager = $this->getMockBuilder(LowInventoryQuantityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lowInventoryProvider = new LowInventoryProvider(
            $this->lowInventoryQuantityManager
        );
    }

    public function testIsLowInventoryTrue()
    {
        $this->lowInventoryQuantityManager->expects($this->once())
            ->method('isLowInventoryProduct')
            ->willReturn(true);

        $this->assertTrue($this->lowInventoryProvider->isLowInventory(new Product()));
    }

    public function testIsLowInventoryFalse()
    {
        $this->lowInventoryQuantityManager->expects($this->once())
            ->method('isLowInventoryProduct')
            ->willReturn(false);

        $this->assertFalse($this->lowInventoryProvider->isLowInventory(new Product()));
    }
}
