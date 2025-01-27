<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Event\CheckoutActualizeEvent;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Model\CheckoutBySourceCriteriaManipulator;
use Oro\Bundle\CheckoutBundle\Model\CheckoutSubtotalUpdater;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CheckoutBySourceCriteriaManipulatorTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private CheckoutRepository|MockObject $checkoutRepository;
    private CheckoutLineItemsFactory|MockObject $checkoutLineItemsFactory;
    private CheckoutShippingMethodsProviderInterface|MockObject $shippingMethodsProvider;
    private CheckoutSubtotalUpdater|MockObject $checkoutSubtotalUpdater;
    private EventDispatcherInterface|MockObject $eventDispatcher;

    private CheckoutBySourceCriteriaManipulator $checkoutBySourceCriteriaManipulator;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->checkoutLineItemsFactory = $this->createMock(CheckoutLineItemsFactory::class);
        $this->shippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);
        $this->checkoutSubtotalUpdater = $this->createMock(CheckoutSubtotalUpdater::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->checkoutBySourceCriteriaManipulator = new CheckoutBySourceCriteriaManipulator(
            $this->actionExecutor,
            $this->checkoutRepository,
            $this->checkoutLineItemsFactory,
            $this->shippingMethodsProvider,
            $this->checkoutSubtotalUpdater,
            $this->eventDispatcher
        );
    }

    private function getCheckout(int $id): Checkout
    {
        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, $id);

        return $checkout;
    }

    private function getCheckoutSource(CheckoutSourceEntityInterface $source): CheckoutSource
    {
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects(self::any())
            ->method('getEntity')
            ->willReturn($source);

        return $checkoutSource;
    }

    private function getCustomerUser(): CustomerUser
    {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer(new Customer());
        $customerUser->setOrganization(new Organization());

        return $customerUser;
    }

    private function expectsActualizeCheckoutCalls(
        CheckoutSourceEntityInterface $source,
        Collection $checkoutLineItems
    ): void {
        $this->checkoutLineItemsFactory->expects(self::once())
            ->method('create')
            ->with($source)
            ->willReturn($checkoutLineItems);

        $this->checkoutSubtotalUpdater->expects(self::once())
            ->method('recalculateCheckoutSubtotals')
            ->with(self::isInstanceOf(Checkout::class));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(CheckoutActualizeEvent::class));
    }

    public function testActualizeCheckoutWithoutUpdate(): void
    {
        $source = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceCriteria = ['source_entity' => $source];
        $currency = 'USD';
        $updateData = false;
        $checkoutData = ['field' => 'value'];
        $website = new Website();
        $customerUser = $this->createMock(CustomerUser::class);
        $checkoutSource = $this->getCheckoutSource($source);

        $checkout = $this->getCheckout(1);
        $checkout->setCustomerUser($customerUser);
        $checkout->setSource($checkoutSource);

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);

        $this->expectsActualizeCheckoutCalls($source, $checkoutLineItems);

        $this->actionExecutor->expects(self::never())
            ->method('executeAction')
            ->with('copy_values');

        $result = $this->checkoutBySourceCriteriaManipulator->actualizeCheckout(
            $checkout,
            $website,
            $sourceCriteria,
            $currency,
            $checkoutData,
            $updateData
        );

        self::assertSame($checkout, $result);
        self::assertEquals($checkoutLineItems, $result->getLineItems());
        self::assertSame($currency, $result->getCurrency());
        self::assertNull($checkout->getCustomer());
        self::assertNull($checkout->getWebsite());
    }

    public function testActualizeCheckoutWithoutUpdateWithShippingMethod(): void
    {
        $source = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceCriteria = ['source_entity' => $source];
        $currency = 'USD';
        $updateData = false;
        $checkoutData = ['field' => 'value'];
        $website = new Website();
        $customerUser = $this->createMock(CustomerUser::class);
        $checkoutSource = $this->getCheckoutSource($source);
        $shippingPrice = Price::create(12, 'USD');

        $checkout = $this->getCheckout(1);
        $checkout->setCustomerUser($customerUser);
        $checkout->setSource($checkoutSource);
        $checkout->setShippingMethod('test');

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);

        $this->expectsActualizeCheckoutCalls($source, $checkoutLineItems);

        $this->shippingMethodsProvider->expects(self::once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn($shippingPrice);

        $this->actionExecutor->expects(self::never())
            ->method('executeAction')
            ->with('copy_values');

        $result = $this->checkoutBySourceCriteriaManipulator->actualizeCheckout(
            $checkout,
            $website,
            $sourceCriteria,
            $currency,
            $checkoutData,
            $updateData
        );

        self::assertSame($checkout, $result);
        self::assertEquals($checkoutLineItems, $result->getLineItems());
        self::assertSame($currency, $result->getCurrency());
        self::assertNull($checkout->getCustomer());
        self::assertNull($checkout->getWebsite());
        self::assertSame($shippingPrice, $checkout->getShippingCost());
    }

    public function testActualizeCheckoutWithoutCustomerUser(): void
    {
        $source = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceCriteria = ['source_entity' => $source];
        $currency = 'USD';
        $updateData = true;
        $checkoutData = ['field' => 'value'];
        $website = new Website();
        $checkoutSource = $this->getCheckoutSource($source);

        $checkout = $this->getCheckout(1);
        $checkout->setSource($checkoutSource);

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);

        $this->expectsActualizeCheckoutCalls($source, $checkoutLineItems);

        $this->actionExecutor->expects(self::never())
            ->method('executeAction')
            ->with('copy_values');

        $result = $this->checkoutBySourceCriteriaManipulator->actualizeCheckout(
            $checkout,
            $website,
            $sourceCriteria,
            $currency,
            $checkoutData,
            $updateData
        );

        self::assertSame($checkout, $result);
        self::assertEquals($checkoutLineItems, $result->getLineItems());
        self::assertSame($currency, $result->getCurrency());
        self::assertNull($checkout->getCustomer());
        self::assertNull($checkout->getWebsite());
    }

    public function testActualizeCheckoutWithUpdate(): void
    {
        $source = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceCriteria = ['source_entity' => $source];
        $currency = 'USD';
        $updateData = true;
        $checkoutData = ['field' => 'value'];
        $website = new Website();
        $customerUser = $this->getCustomerUser();
        $checkoutSource = $this->getCheckoutSource($source);

        $checkout = $this->getCheckout(1);
        $checkout->setCustomerUser($customerUser);
        $checkout->setSource($checkoutSource);

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);

        $this->expectsActualizeCheckoutCalls($source, $checkoutLineItems);

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'copy_values',
                [$checkout, $checkoutData]
            );

        $result = $this->checkoutBySourceCriteriaManipulator->actualizeCheckout(
            $checkout,
            $website,
            $sourceCriteria,
            $currency,
            $checkoutData,
            $updateData
        );

        self::assertSame($checkout, $result);
        self::assertEquals($checkoutLineItems, $result->getLineItems());
        self::assertSame($currency, $result->getCurrency());
        self::assertSame($customerUser->getCustomer(), $checkout->getCustomer());
        self::assertSame($website, $checkout->getWebsite());
        self::assertSame($customerUser->getOrganization(), $checkout->getOrganization());
    }

    public function testFindCheckoutWithCustomerUser(): void
    {
        $source = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceCriteria = ['source_entity' => $source];
        $currentUser = $this->createMock(CustomerUser::class);
        $currency = 'USD';

        $checkout = $this->getCheckout(1);

        $this->checkoutRepository->expects(self::once())
            ->method('findCheckoutByCustomerUserAndSourceCriteriaWithCurrency')
            ->willReturn($checkout);

        $result = $this->checkoutBySourceCriteriaManipulator->findCheckout(
            $sourceCriteria,
            $currentUser,
            $currency,
            'test'
        );

        self::assertSame($checkout, $result);
    }

    public function testFindCheckoutWithoutCustomerUser(): void
    {
        $source = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceCriteria = ['source_entity' => $source];
        $currentUser = null;
        $currency = 'USD';

        $checkout = $this->getCheckout(1);

        $this->checkoutRepository->expects(self::once())
            ->method('findCheckoutBySourceCriteriaWithCurrency')
            ->willReturn($checkout);

        $result = $this->checkoutBySourceCriteriaManipulator->findCheckout(
            $sourceCriteria,
            $currentUser,
            $currency,
            'test'
        );

        self::assertSame($checkout, $result);
    }

    public function testCreateCheckout(): void
    {
        $source = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceCriteria = ['source_entity' => $source];
        $currency = 'USD';
        $checkoutData = ['field' => 'value'];
        $customerUser = $this->getCustomerUser();
        $website = new Website();
        $checkoutSource = $this->getCheckoutSource($source);

        $checkout = new Checkout();
        $checkout->setCustomerUser($customerUser);
        $checkout->setSource($checkoutSource);

        $checkoutLineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);

        $this->expectsActualizeCheckoutCalls($source, $checkoutLineItems);

        $this->actionExecutor->expects(self::exactly(2))
            ->method('executeAction')
            ->willReturnMap([
                ['copy_values', [$checkout, $checkoutData]],
                [
                    'create_entity',
                    ['class' => CheckoutSource::class, 'data' => $sourceCriteria, 'attribute' => null],
                    ['attribute' => $checkoutSource]
                ]
            ]);

        $result = $this->checkoutBySourceCriteriaManipulator->createCheckout(
            $website,
            $sourceCriteria,
            $customerUser,
            $currency,
            $checkoutData
        );

        self::assertInstanceOf(Checkout::class, $result);
        self::assertEquals($checkoutLineItems, $result->getLineItems());
        self::assertSame($currency, $result->getCurrency());
        self::assertSame($website, $result->getWebsite());
        self::assertSame($customerUser->getCustomer(), $result->getCustomer());
        self::assertSame($customerUser->getOrganization(), $result->getOrganization());
        self::assertInstanceOf(\DateTime::class, $result->getCreatedAt());
        self::assertEquals($result->getCreatedAt(), $result->getUpdatedAt());
        self::assertNotSame($result->getCreatedAt(), $result->getUpdatedAt());
    }
}
