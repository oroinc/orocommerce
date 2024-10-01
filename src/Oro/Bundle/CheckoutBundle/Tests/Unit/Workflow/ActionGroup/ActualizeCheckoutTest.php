<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ActualizeCheckout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActualizeCheckoutTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private CheckoutLineItemsFactory|MockObject $checkoutLineItemsFactory;
    private CheckoutShippingMethodsProviderInterface|MockObject $shippingMethodsProvider;
    private CheckoutSubtotalUpdater|MockObject $checkoutSubtotalUpdater;
    private ActualizeCheckout $actualizeCheckout;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->checkoutLineItemsFactory = $this->createMock(CheckoutLineItemsFactory::class);
        $this->shippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->checkoutSubtotalUpdater = $this->createMock(CheckoutSubtotalUpdater::class);

        $this->actualizeCheckout = new ActualizeCheckout(
            $this->actionExecutor,
            $this->userCurrencyManager,
            $this->checkoutLineItemsFactory,
            $this->shippingMethodsProvider,
            $this->checkoutSubtotalUpdater
        );
    }

    public function testExecuteWithUpdateData()
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setNotes('notes');

        $organization = new Organization();
        $currentWebsite = new Website();

        $customer = new Customer();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);
        $customerUser->setOrganization($organization);

        $source = $this->createMock(CheckoutSource::class);
        $source->expects($this->any())
            ->method('getEntity')
            ->willReturn($shoppingList);

        $checkout = new Checkout();
        $checkout->setCustomerUser($customerUser);
        $checkout->setCustomer(null);
        $checkout->setShippingMethod('paypal');

        $sourceCriteria = ['shoppingList' => $shoppingList];

        $checkoutData = [];
        $updateData = true;

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'copy_values',
                [$checkout, $checkoutData]
            );

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');
        $lineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $this->checkoutLineItemsFactory->expects($this->once())
            ->method('create')
            ->willReturn($checkout)
            ->willReturn($lineItems);

        $this->shippingMethodsProvider->expects($this->once())
            ->method('getPrice')
            ->willReturn(Price::create(100.0, 'USD'));

        $this->checkoutSubtotalUpdater->expects($this->once())
            ->method('recalculateCheckoutSubtotals')
            ->with($checkout);

        $result = $this->actualizeCheckout->execute(
            $checkout,
            $sourceCriteria,
            $currentWebsite,
            $updateData,
            $checkoutData
        );
        $this->assertInstanceOf(Checkout::class, $result);

        $this->assertEquals($customerUser, $result->getCustomerUser());
        $this->assertEquals($customer, $result->getCustomer());
        $this->assertEquals($organization, $result->getOrganization());
        $this->assertEquals($currentWebsite, $result->getWebsite());
        $this->assertEquals($lineItems, $result->getLineItems());
        $this->assertEquals('notes', $result->getCustomerNotes());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function testExecuteWithUpdateDataWithMinimalDataWithoutUpdate()
    {
        $shoppingList = new ShoppingList();

        $organization = new Organization();
        $currentWebsite = new Website();

        $customer = new Customer();
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);
        $customerUser->setOrganization($organization);

        $source = $this->createMock(CheckoutSource::class);
        $source->expects($this->any())
            ->method('getEntity')
            ->willReturn($shoppingList);

        $checkout = new Checkout();
        $checkout->setCustomerUser($customerUser);
        $checkout->setCustomer(null);

        $sourceCriteria = [];
        $checkoutData = [];
        $updateData = false;

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');
        $lineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $this->checkoutLineItemsFactory->expects($this->once())
            ->method('create')
            ->willReturn($checkout)
            ->willReturn($lineItems);

        $this->shippingMethodsProvider->expects($this->never())
            ->method('getPrice');

        $this->checkoutSubtotalUpdater->expects($this->once())
            ->method('recalculateCheckoutSubtotals')
            ->with($checkout);

        $result = $this->actualizeCheckout->execute(
            $checkout,
            $sourceCriteria,
            $currentWebsite,
            $updateData,
            $checkoutData
        );
        $this->assertInstanceOf(Checkout::class, $result);

        $this->assertEquals($customerUser, $result->getCustomerUser());
        $this->assertNull($result->getCustomer());
        $this->assertNull($result->getOrganization());
        $this->assertNull($result->getWebsite());
        $this->assertEquals($lineItems, $result->getLineItems());
        $this->assertNull($result->getCustomerNotes());
        $this->assertEquals('USD', $result->getCurrency());
    }
}
