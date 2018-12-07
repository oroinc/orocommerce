<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\Doctrine;

use Oro\Bundle\ProductBundle\EventListener\Doctrine\ProductUnitEntityListener;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductUnitsProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productUnitsProvider;

    /** @var ProductUnitEntityListener */
    private $listener;

    protected function setUp()
    {
        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);

        $this->listener = new ProductUnitEntityListener($this->productUnitsProvider);
    }

    public function testInvalidateProductUnitCache()
    {
        $this->productUnitsProvider->expects($this->once())
            ->method('clearCache');

        $this->listener->invalidateProductUnitCache();
    }
}
