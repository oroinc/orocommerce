<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutSubtotalAmountProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PromotionBundle\Context\CheckoutContextDataConverter;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Discount\Converter\OrderLineItemsToDiscountLineItemsConverter;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class CheckoutContextDataConverterTest extends \PHPUnit_Framework_TestCase
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
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutLineItemsManager;

    /**
     * @var CheckoutSubtotalAmountProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSubtotalAmountProvider;

    /**
     * @var CheckoutContextDataConverter
     */
    private $converter;

    protected function setUp()
    {
        $this->relationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->lineItemsConverter = $this->createMock(OrderLineItemsToDiscountLineItemsConverter::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->checkoutSubtotalAmountProvider = $this->createMock(CheckoutSubtotalAmountProvider::class);
        $this->converter = new CheckoutContextDataConverter(
            $this->relationsProvider,
            $this->lineItemsConverter,
            $this->userCurrencyManager,
            $this->scopeManager,
            $this->checkoutLineItemsManager,
            $this->checkoutSubtotalAmountProvider
        );
    }

    public function testSupportsForWrongEntity()
    {
        $entity = new \stdClass();
        $this->assertFalse($this->converter->supports($entity));
    }

    public function testSupportsForCheckoutWithNonShoppingListAsSource()
    {
        $this->assertFalse($this->converter->supports($this->getCheckout(\stdClass::class)));
    }

    public function testSupports()
    {
        $this->assertTrue($this->converter->supports($this->getCheckout()));
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
        $shippingCost = Price::create(10.0, 'USD');
        $paymentMethod = 'some payment method';
        $shippingMethod = 'some shipping method';

        $entity = $this->getCheckout();
        $entity->setCustomerUser($customerUser);
        $entity->setBillingAddress($billingAddress);
        $entity->setShippingAddress($shippingAddress);
        $entity->setShippingCost($shippingCost);
        $entity->setPaymentMethod($paymentMethod);
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

        $this->assertSame([
            ContextDataConverterInterface::CUSTOMER_USER => $customerUser,
            ContextDataConverterInterface::CUSTOMER => $customer,
            ContextDataConverterInterface::CUSTOMER_GROUP => $customerGroup,
            ContextDataConverterInterface::LINE_ITEMS => $discountLineItems,
            ContextDataConverterInterface::SUBTOTAL => $subtotalAmount,
            ContextDataConverterInterface::CURRENCY => $currency,
            ContextDataConverterInterface::CRITERIA => $scopeCriteria,
            ContextDataConverterInterface::BILLING_ADDRESS => $billingAddress,
            ContextDataConverterInterface::SHIPPING_ADDRESS => $shippingAddress,
            ContextDataConverterInterface::SHIPPING_COST => $shippingCost,
            ContextDataConverterInterface::PAYMENT_METHOD => $paymentMethod,
            ContextDataConverterInterface::SHIPPING_METHOD => $shippingMethod,
        ], $this->converter->getContextData($entity));
    }

    public function testGetContextDataWhenCustomerGroupIsNotFounded()
    {
        $customerGroup = new CustomerGroup();
        $customer = new Customer();
        $customer->setGroup($customerGroup);
        $customerUser = new CustomerUser();

        $entity = $this->getCheckout();
        $entity->setCustomerUser($customerUser);
        $entity->setCustomer($customer);

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

        $this->assertSame([
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
            ContextDataConverterInterface::PAYMENT_METHOD => null,
            ContextDataConverterInterface::SHIPPING_METHOD => null,
        ], $this->converter->getContextData($entity));
    }

    /**
     * @param Checkout $entity
     * @return DiscountLineItem[]
     */
    private function getDiscountLineItems(Checkout $entity): array
    {
        $lineItems = [new OrderLineItem()];
        $discountLineItems = [new DiscountLineItem()];
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($entity)
            ->willReturn(new ArrayCollection($lineItems));
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
     * @param Checkout $entity
     * @return float
     */
    private function getSubtotalAmount(Checkout $entity)
    {
        $subtotalAmount = 100.0;
        $this->checkoutSubtotalAmountProvider->expects($this->once())
            ->method('getSubtotalAmount')
            ->with($entity)
            ->willReturn($subtotalAmount);

        return $subtotalAmount;
    }

    /**
     * @param string $sourceEntityClass
     * @return Checkout
     */
    private function getCheckout($sourceEntityClass = ShoppingList::class)
    {
        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn(new $sourceEntityClass);
        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        return $checkout;
    }
}
