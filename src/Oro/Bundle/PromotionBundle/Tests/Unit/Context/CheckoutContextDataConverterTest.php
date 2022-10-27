<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Context\CheckoutContextDataConverter;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutContextDataConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CheckoutToOrderConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutToOrderConverter;

    /**
     * @var ContextDataConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderContextDataConverter;

    /**
     * @var CheckoutContextDataConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->checkoutToOrderConverter  = $this->createMock(CheckoutToOrderConverter::class);
        $this->orderContextDataConverter = $this->createMock(ContextDataConverterInterface::class);
        $this->converter                 = new CheckoutContextDataConverter(
            $this->checkoutToOrderConverter,
            $this->orderContextDataConverter
        );
    }

    /**
     * @dataProvider supportsDataProvider
     * @param object $entity
     * @param boolean $isSupported
     */
    public function testSupports($entity, $isSupported)
    {
        $this->assertSame($isSupported, $this->converter->supports($entity));
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'supported entity' => [
                'entity' => $this->getCheckout(),
                'isSupported' => true
            ],
            'support all source entities except QuoteDemand' => [
                'entity' => $this->getCheckout(\stdClass::class),
                'isSupported' => true
            ],
            'not support QuoteDemand source' => [
                'entity' => $this->getCheckout(QuoteDemand::class),
                'isSupported' => false
            ],
            'not supported entity' => [
                'entity' => new \stdClass(),
                'isSupported' => false
            ],
            'supported without source entity' => [
                'entity' => $this->getCheckout(null),
                'isSupported' => true
            ],
        ];
    }

    public function testGetContextDataWhenThrowsException()
    {
        $entity = new \stdClass();
        $this->expectException(UnsupportedSourceEntityException::class);
        $this->expectExceptionMessage('Source entity "stdClass" is not supported.');

        $this->converter->getContextData($entity);
    }

    public function testGetContextData()
    {
        $checkout = $this->getCheckout();
        $order = new Order();
        $context = ['context' => 'data'];

        $this->checkoutToOrderConverter
            ->expects($this->any())
            ->method('getOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->orderContextDataConverter
            ->expects($this->any())
            ->method('getContextData')
            ->with($order)
            ->willReturn($context);

        $this->assertEquals($context, $this->converter->getContextData($checkout));
    }

    /**
     * @param string $sourceEntityClass
     * @return Checkout
     */
    private function getCheckout($sourceEntityClass = ShoppingList::class)
    {
        /** @var CheckoutSource|\PHPUnit\Framework\MockObject\MockObject $checkoutSource */
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntityClass ? new $sourceEntityClass : null);
        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        return $checkout;
    }
}
