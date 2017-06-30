<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\PromotionBundle\Context\OrderContextDataConverter;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class OrderContextDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeManager;

    /**
     * @var OrderContextDataConverter
     */
    protected $orderContextDataConverter;

    protected function setUp()
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->orderContextDataConverter = new OrderContextDataConverter($this->scopeManager);
    }

    public function testGetContextData()
    {
        $currency = 'USD';
        $lineItemsCollection = new ArrayCollection([new OrderLineItem()]);
        $address = new OrderAddress();
        $shippingAmount = 1.0;
        $shippingMethod = 'UPS';
        $customer = (new Customer())->setGroup(new CustomerGroup());
        $customerUser = new CustomerUser();
        $shippingCost = (new Price())->setValue(1.0)->setCurrency('USD');
        $subTotal = 1.0;
        $scopeCriteria = $this->getScopeCriteria();
        
        $expectedResult = [
            ContextDataConverterInterface::SHIPPING_COST => $shippingCost,
            ContextDataConverterInterface::LINE_ITEMS => $lineItemsCollection,
            ContextDataConverterInterface::SUBTOTAL => $subTotal,
            ContextDataConverterInterface::SHIPPING_ADDRESS => $address,
            ContextDataConverterInterface::BILLING_ADDRESS => $address,
            ContextDataConverterInterface::SHIPPING_METHOD => $shippingMethod,
            ContextDataConverterInterface::CUSTOMER => $customer,
            ContextDataConverterInterface::CUSTOMER_USER => $customerUser,
            ContextDataConverterInterface::CUSTOMER_GROUP => $customer->getGroup(),
            ContextDataConverterInterface::CURRENCY => $currency,
            ContextDataConverterInterface::CRITERIA => $scopeCriteria
        ];
        
        $order = new Order();
        $order->setCurrency('USD');
        $order->setSubtotal(1.0);
        $order->setLineItems($lineItemsCollection);
        $order->setBillingAddress($address);
        $order->setShippingAddress($address);
        $order->setEstimatedShippingCostAmount($shippingAmount);
        $order->setShippingMethod($shippingMethod);
        $order->setCustomer($customer);
        $order->setCustomerUser($customerUser);

        $this->assertEquals($expectedResult, $this->orderContextDataConverter->getContextData($order));
    }

    public function testSupports()
    {
        $this->assertTrue($this->orderContextDataConverter->supports(new Order()));
    }

    public function testSupportsForWrongEntity()
    {
        $entity = new \stdClass();
        $this->assertFalse($this->orderContextDataConverter->supports($entity));
    }

    /**
     * @return ScopeCriteria
     */
    private function getScopeCriteria(): ScopeCriteria
    {
        $scopeCriteria = new ScopeCriteria([], []);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('promotion')
            ->willReturn($scopeCriteria);

        return $scopeCriteria;
    }
}
