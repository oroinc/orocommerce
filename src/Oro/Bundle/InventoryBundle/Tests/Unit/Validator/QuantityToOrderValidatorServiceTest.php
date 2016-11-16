<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class QuantityToOrderValidatorServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityFallbackResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fallbackResolver;

    /**
     * @var QuantityToOrderValidatorService
     */
    protected $quantityToOrderValidatorService;

    protected function setUp()
    {
        $this->fallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quantityToOrderValidatorService = new QuantityToOrderValidatorService($this->fallbackResolver);
    }

    public function testIsLineItemListValidReturnsTrueIfNoProduct()
    {
        $lineItems = [new LineItem()];
        $this->assertTrue($this->quantityToOrderValidatorService->isLineItemListValid($lineItems));
    }

    public function testIsLineItemListValidReturnsFalseIfInvalidLimits()
    {
        $lineItem = new LineItem();
        $lineItem->setProduct(new Product());
        $lineItem->setQuantity(1);
        $lineItems = [$lineItem];

        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturn(3);
        $this->assertFalse($this->quantityToOrderValidatorService->isLineItemListValid($lineItems));
    }

    public function testIsLineItemListValidReturnsTrueIfValidLimits()
    {
        $lineItem = new LineItem();
        $lineItem->setProduct(new Product());
        $lineItem->setQuantity(3);
        $lineItems = [$lineItem];

        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturn(3);
        $this->assertTrue($this->quantityToOrderValidatorService->isLineItemListValid($lineItems));
    }

    public function testIsHigherThanMaxLimit()
    {
        $this->assertFalse($this->quantityToOrderValidatorService->isHigherThanMaxLimit(5, 3));
        $this->assertFalse($this->quantityToOrderValidatorService->isHigherThanMaxLimit(3, 3));
        $this->assertTrue($this->quantityToOrderValidatorService->isHigherThanMaxLimit(3, 5));
    }

    public function testIsLowerThenMinLimit()
    {
        $this->assertFalse($this->quantityToOrderValidatorService->isLowerThenMinLimit(3, 5));
        $this->assertFalse($this->quantityToOrderValidatorService->isLowerThenMinLimit(3, 3));
        $this->assertTrue($this->quantityToOrderValidatorService->isLowerThenMinLimit(5, 3));
    }

    public function testIsMaxLimitLowerThenMinLimitReturnFalseIfNotNumeric()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product * */
        $product = $this->getMock(Product::class);
        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturn('');

        $this->assertFalse($this->quantityToOrderValidatorService->isMaxLimitLowerThenMinLimit($product));
    }

    public function testIsMaxLimitLowerThenMinLimitReturnFalse()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product * */
        $product = $this->getMock(Product::class);
        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->will($this->onConsecutiveCalls(3, 5));

        $this->assertFalse($this->quantityToOrderValidatorService->isMaxLimitLowerThenMinLimit($product));
    }

    public function testIsMaxLimitLowerThenMinLimitReturnTrue()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product * */
        $product = $this->getMock(Product::class);
        $this->fallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->will($this->onConsecutiveCalls(5, 3));

        $this->assertTrue($this->quantityToOrderValidatorService->isMaxLimitLowerThenMinLimit($product));
    }

    public function testGetMinimumLimit()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product * */
        $product = $this->getMock(Product::class);
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn(1);

        $this->assertEquals(1, $this->quantityToOrderValidatorService->getMinimumLimit($product));
    }

    public function testGetMaximumLimit()
    {
        /** @var Product|\PHPUnit_Framework_MockObject_MockObject $product * */
        $product = $this->getMock(Product::class);
        $this->fallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn(1);

        $this->assertEquals(1, $this->quantityToOrderValidatorService->getMaximumLimit($product));
    }
}
