<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Context\OrderContextDataConverter;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class OrderContextDataConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerUserRelationsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $relationsProvider;

    /**
     * @var OrderLineItemsToDiscountLineItemsConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemsConverter;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userCurrencyManager;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeManager;

    /**
     * @var SubtotalProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemSubtotalProvider;

    /**
     * @var OrderContextDataConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->relationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->lineItemsConverter = $this->createMock(OrderLineItemsToDiscountLineItemsConverter::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->lineItemSubtotalProvider = $this->createMock(SubtotalProviderInterface::class);

        $this->converter = new OrderContextDataConverter(
            $this->relationsProvider,
            $this->lineItemsConverter,
            $this->userCurrencyManager,
            $this->scopeManager,
            $this->lineItemSubtotalProvider
        );
    }

    public function testSupportsForWrongEntity()
    {
        $entity = new \stdClass();
        $this->assertFalse($this->converter->supports($entity));
    }

    public function testSupportsForCheckoutWithNonShoppingListAsSource()
    {
        $this->assertFalse($this->converter->supports(new \stdClass()));
    }

    public function testSupports()
    {
        $this->assertTrue($this->converter->supports(new Order()));
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
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customerUser = new CustomerUser();
        $billingAddress = new OrderAddress();
        $shippingAddress = new OrderAddress();
        $shippingMethod = 'some shipping method';

        $entity = new Order();
        $entity->setCustomerUser($customerUser);
        $entity->setBillingAddress($billingAddress);
        $entity->setShippingAddress($shippingAddress);
        $entity->setEstimatedShippingCostAmount(10.0);
        $entity->setCurrency('USD');
        $entity->setShippingMethod($shippingMethod);

        $this->relationsProvider->expects($this->once())
            ->method('getCustomer')
            ->with($customerUser)
            ->willReturn($customer);
        $this->relationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->with($customerUser)
            ->willReturn($customerGroup);

        $discountLineItems = $this->getDiscountLineItems($entity);
        $currency = $this->getCurrency();
        $scopeCriteria = $this->getScopeCriteria();
        $subtotalAmount = $this->getSubtotalAmount($entity);

        $this->assertEquals([
            ContextDataConverterInterface::CUSTOMER_USER => $customerUser,
            ContextDataConverterInterface::CUSTOMER => $customer,
            ContextDataConverterInterface::CUSTOMER_GROUP => $customerGroup,
            ContextDataConverterInterface::LINE_ITEMS => $discountLineItems,
            ContextDataConverterInterface::SUBTOTAL => $subtotalAmount,
            ContextDataConverterInterface::CURRENCY => $currency,
            ContextDataConverterInterface::CRITERIA => $scopeCriteria,
            ContextDataConverterInterface::BILLING_ADDRESS => $billingAddress,
            ContextDataConverterInterface::SHIPPING_ADDRESS => $shippingAddress,
            ContextDataConverterInterface::SHIPPING_COST => Price::create(10.0, 'USD'),
            ContextDataConverterInterface::SHIPPING_METHOD => $shippingMethod,
        ], $this->converter->getContextData($entity));
    }

    public function testGetContextDataWhenCustomerGroupWasNotFound()
    {
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $customerUser = new CustomerUser();

        $entity = new Order();
        $entity->setCustomerUser($customerUser);
        $entity->setCustomer($customer);
        $entity->setCurrency('USD');

        $discountLineItems = $this->getDiscountLineItems($entity);
        $currency = $this->getCurrency();
        $scopeCriteria = $this->getScopeCriteria();
        $subtotalAmount = $this->getSubtotalAmount($entity);

        $this->relationsProvider->expects($this->once())
            ->method('getCustomer')
            ->with($customerUser)
            ->willReturn($customer);
        $this->relationsProvider->expects($this->once())
            ->method('getCustomerGroup')
            ->with($customerUser)
            ->willReturn(null);

        $this->assertEquals([
            ContextDataConverterInterface::CUSTOMER_USER => $customerUser,
            ContextDataConverterInterface::CUSTOMER => $customer,
            ContextDataConverterInterface::CUSTOMER_GROUP => $customerGroup,
            ContextDataConverterInterface::LINE_ITEMS => $discountLineItems,
            ContextDataConverterInterface::SUBTOTAL => $subtotalAmount,
            ContextDataConverterInterface::CURRENCY => $currency,
            ContextDataConverterInterface::CRITERIA => $scopeCriteria,
            ContextDataConverterInterface::BILLING_ADDRESS => null,
            ContextDataConverterInterface::SHIPPING_ADDRESS => null,
            ContextDataConverterInterface::SHIPPING_COST => null,
            ContextDataConverterInterface::SHIPPING_METHOD => null,
        ], $this->converter->getContextData($entity));
    }

    /**
     * @param Order $entity
     * @return DiscountLineItem[]
     */
    private function getDiscountLineItems(Order $entity): array
    {
        $lineItems = [new OrderLineItem()];
        $discountLineItems = [new DiscountLineItem()];
        $entity->setLineItems(new ArrayCollection($lineItems));
        $this->lineItemsConverter->expects($this->once())
            ->method('convert')
            ->with($lineItems)
            ->willReturn($discountLineItems);

        return $discountLineItems;
    }

    /**
     * @return string
     */
    private function getCurrency(): string
    {
        $currency = 'USD';
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        return $currency;
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

    /**
     * @param Order $entity
     * @return float
     */
    private function getSubtotalAmount(Order $entity)
    {
        $subtotalAmount = 100.0;
        $this->lineItemSubtotalProvider->expects($this->once())
            ->method('getSubtotal')
            ->with($entity)
            ->willReturn((new Subtotal())->setAmount($subtotalAmount));

        return $subtotalAmount;
    }
}
