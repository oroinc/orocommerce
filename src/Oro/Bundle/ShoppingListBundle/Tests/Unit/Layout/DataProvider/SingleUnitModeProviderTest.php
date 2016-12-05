<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\SingleUnitModeProvider;

class SingleUnitModeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|SingleUnitModeService */
    private $singleUnitService;

    /** @var SingleUnitModeProvider $unitModeProvider */
    protected $unitModeProvider;

    public function setUp()
    {
        $this->singleUnitService = $this->getMockBuilder(SingleUnitModeService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->unitModeProvider = new SingleUnitModeProvider($this->singleUnitService);
    }

    public function testGetProductStates()
    {
        $productState = true;
        $this->singleUnitService->expects(static::once())
            ->method('isProductPrimaryUnitSingleAndDefault')
            ->willReturn($productState);

        $product = new Product();
        $lineItem = (new LineItem())->setProduct($product);
        $shoppingList = (new ShoppingList())->addLineItem($lineItem);

        $productStatuses = $this->unitModeProvider->getProductStates($shoppingList);

        static::assertSame([$product->getId() => $productState], $productStatuses);
    }

    public function testGetProductStatesOnNull()
    {
        static::assertSame([], $this->unitModeProvider->getProductStates());
    }
}
