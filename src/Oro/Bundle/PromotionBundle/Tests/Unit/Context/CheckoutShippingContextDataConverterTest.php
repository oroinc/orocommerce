<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Context\CheckoutShippingContextDataConverter;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

class CheckoutShippingContextDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutContextDataConverter;

    /** @var CheckoutShippingContextDataConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->checkoutContextDataConverter = $this->createMock(ContextDataConverterInterface::class);
        $this->converter = new CheckoutShippingContextDataConverter($this->checkoutContextDataConverter);
    }

    /**
     * @dataProvider getSupportsDataProvider
     */
    public function testSupports(bool $isSupported, bool $expectedResult): void
    {
        $entity = new Checkout();

        $this->checkoutContextDataConverter
            ->method('supports')
            ->with($entity)
            ->willReturn($isSupported);

        $this->assertEquals($expectedResult, $this->converter->supports($entity));
    }

    public function getSupportsDataProvider(): array
    {
        return [
            'not supported entity' => [
                'isSupported' => false,
                'expectedResult' => false,
            ],
            'supported entity' => [
                'isSupported' => true,
                'expectedResult' => true,
            ],
        ];
    }

    public function testGetContextDataWhenThrowsException(): void
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Source entity "stdClass" is not supported.');

        $this->converter->getContextData($entity);
    }

    public function testGetContextData(): void
    {
        $checkout = $this->getCheckout();
        $context = ['context' => 'data'];

        $this->checkoutContextDataConverter->expects($this->once())
            ->method('supports')
            ->with($checkout)
            ->willReturn(true);
        $this->checkoutContextDataConverter->expects($this->once())
            ->method('getContextData')
            ->with($checkout)
            ->willReturn($context);

        $shippingContextData = [
            CheckoutShippingContextDataConverter::SHIPPING_METHOD => $checkout->getShippingMethod(),
            CheckoutShippingContextDataConverter::SHIPPING_METHOD_TYPE => $checkout->getShippingMethodType(),
            CheckoutShippingContextDataConverter::SHIPPING_COST => $checkout->getShippingCost(),
        ];

        $this->assertEquals(array_merge($context, $shippingContextData), $this->converter->getContextData($checkout));
    }

    private function getCheckout(): Checkout
    {
        $checkout = new Checkout();
        $checkout->setShippingMethod('method_name');
        $checkout->setShippingMethodType(1);
        $checkout->setShippingCost(Price::create(10, 'USD'));

        return $checkout;
    }
}
