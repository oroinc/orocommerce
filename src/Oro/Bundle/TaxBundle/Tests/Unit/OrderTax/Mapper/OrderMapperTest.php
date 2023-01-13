<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\Mapper;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Event\ContextEventDispatcher;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\OrderTax\Mapper\OrderLineItemMapper;
use Oro\Bundle\TaxBundle\OrderTax\Mapper\OrderMapper;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderMapperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const ORDER_ID = 123;
    private const ORDER_SUBTOTAL = 234.34;

    private const CONTEXT_KEY = 'context_key';
    private const CONTEXT_VALUE = 'context_value';

    /** @var OrderMapper */
    private $mapper;

    /** @var OrderLineItemMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $orderLineItemMapper;

    /** @var PreloadingManager */
    private $preloadingManager;

    protected function setUp(): void
    {
        $this->orderLineItemMapper = $this->createMock(OrderLineItemMapper::class);
        $this->preloadingManager = $this->createMock(PreloadingManager::class);

        $addressProvider = $this->createMock(TaxationAddressProvider::class);
        $addressProvider->expects($this->any())
            ->method('getDestinationAddress')
            ->willReturnArgument(0);
        $addressProvider->expects($this->any())
            ->method('getTaxationAddress')
            ->willReturnArgument(1);

        $eventDispatcher = $this->createMock(ContextEventDispatcher::class);
        $eventDispatcher->expects($this->any())
            ->method('dispatch')
            ->willReturn(new \ArrayObject([self::CONTEXT_KEY => self::CONTEXT_VALUE]));

        $this->mapper = new OrderMapper(
            $eventDispatcher,
            $addressProvider,
            $this->orderLineItemMapper,
            $this->preloadingManager
        );
    }

    public function testMap(): void
    {
        $this->orderLineItemMapper->expects($this->once())
            ->method('map')
            ->willReturn(new Taxable());

        $order = $this->createOrder(self::ORDER_ID, self::ORDER_SUBTOTAL);

        $this->preloadingManager->expects($this->once())
            ->method('preloadInEntities')
            ->with(
                $order->getLineItems()->toArray(),
                ['product' => ['taxCode' => []]]
            );

        $taxable = $this->mapper->map($order);

        $this->assertInstanceOf(Taxable::class, $taxable);
        $this->assertEquals(self::ORDER_ID, $taxable->getIdentifier());
        $this->assertEquals('1', $taxable->getQuantity());
        $this->assertEquals('0', $taxable->getPrice());
        $this->assertEquals('234.34', $taxable->getAmount());
        $this->assertEquals($order->getShippingAddress(), $taxable->getTaxationAddress());
        $this->assertEquals($order->getBillingAddress(), $taxable->getDestination());
        $this->assertNull($taxable->getOrigin());
        $this->assertEquals(self::CONTEXT_VALUE, $taxable->getContextValue(self::CONTEXT_KEY));
        $this->assertNotEmpty($taxable->getItems());
        $this->assertCount(1, $taxable->getItems());
        $this->assertInstanceOf(Taxable::class, $taxable->getItems()->current());
        $this->assertEquals('20', $taxable->getShippingCost());
    }

    private function createOrder(int $id, float $subtotal): Order
    {
        $billingAddress = (new OrderAddress())
            ->setFirstName('FirstName')
            ->setLastName('LastName')
            ->setStreet('street');
        $shippingAddress = (new OrderAddress())
            ->setFirstName('FirstName')
            ->setLastName('LastName')
            ->setStreet('street');

        $order = $this->getEntity(Order::class, ['id' => $id]);
        $order
            ->setSubtotal($subtotal)
            ->addLineItem(new OrderLineItem())
            ->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress)
            ->setCurrency('$')
            ->setEstimatedShippingCostAmount(10)
            ->setOverriddenShippingCostAmount(20);

        return $order;
    }
}
