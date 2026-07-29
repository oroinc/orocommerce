<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\OrderTax\ContextHandler;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\OrderTax\ContextHandler\OrderHandler;
use Oro\Bundle\TaxBundle\Provider\TaxCodeProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TaxCodeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $taxCodeProvider;

    /** @var CustomerUserRelationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUserRelationsProvider;

    /** @var OrderHandler */
    private $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->taxCodeProvider = $this->createMock(TaxCodeProvider::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);

        $this->handler = new OrderHandler($this->taxCodeProvider, $this->customerUserRelationsProvider);
    }

    public function testIncorrectOrderClass()
    {
        $object = new \stdClass();
        $contextEvent = new ContextEvent($object);
        $this->handler->onContextEvent($contextEvent);

        $this->assertSame($object, $contextEvent->getMappingObject());
        $this->assertEmpty($contextEvent->getContext());
    }

    public function testOnContextEventCustomer()
    {
        $customer = new Customer();
        $products = [
            $this->getEntity(Product::class, ['id' => 1]),
            $this->getEntity(Product::class, ['id' => 2])
        ];

        $order = $this->getEntity(Order::class, [
            'lineItems' => [
                $this->getEntity(OrderLineItem::class, ['id' => 1, 'product' => $products[0]]),
                $this->getEntity(OrderLineItem::class, ['id' => 2, 'product' => $products[1]])
            ]
        ]);
        $order->setCustomer($customer);
        $event = new ContextEvent($order);
        $oldContext = clone $event->getContext();

        $customerTaxCode = new CustomerTaxCode();
        $customerTaxCode->setCode('ACCOUNT_TAX_CODE');
        $this->taxCodeProvider->expects($this->once())
            ->method('getTaxCode')
            ->with(TaxCodeInterface::TYPE_ACCOUNT, $customer)
            ->willReturn($customerTaxCode);

        $this->taxCodeProvider->expects($this->once())
            ->method('preloadTaxCodes')
            ->with(TaxCodeInterface::TYPE_PRODUCT, $products);

        $this->handler->onContextEvent($event);
        $this->assertNotEquals($oldContext, $event->getContext());
        $this->assertEquals('ACCOUNT_TAX_CODE', $event->getContext()->offsetGet(Taxable::ACCOUNT_TAX_CODE));
        $this->assertEquals($order, $event->getContext()->offsetGet('scopeValue'));
    }

    public function testOnContextEventNoCustomerFallsBackToAnonymousGroup()
    {
        $anonymousGroup = new CustomerGroup();

        $order = $this->getEntity(Order::class, ['lineItems' => []]);
        $event = new ContextEvent($order);

        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomerGroup')
            ->with(null)
            ->willReturn($anonymousGroup);

        $customerTaxCode = new CustomerTaxCode();
        $customerTaxCode->setCode('ANONYMOUS_GROUP_TAX_CODE');
        $this->taxCodeProvider->expects(self::once())
            ->method('getTaxCode')
            ->with(TaxCodeInterface::TYPE_ACCOUNT_GROUP, $anonymousGroup)
            ->willReturn($customerTaxCode);

        $this->handler->onContextEvent($event);
        $this->assertEquals(
            'ANONYMOUS_GROUP_TAX_CODE',
            $event->getContext()->offsetGet(Taxable::ACCOUNT_TAX_CODE)
        );
    }

    public function testOnContextEventNoCustomerAndNoAnonymousGroupConfigured()
    {
        $order = $this->getEntity(Order::class, ['lineItems' => []]);
        $event = new ContextEvent($order);

        $this->customerUserRelationsProvider->expects(self::once())
            ->method('getCustomerGroup')
            ->with(null)
            ->willReturn(null);

        $this->taxCodeProvider->expects(self::never())
            ->method('getTaxCode');

        $this->handler->onContextEvent($event);
        $this->assertNull($event->getContext()->offsetGet(Taxable::ACCOUNT_TAX_CODE));
    }
}
