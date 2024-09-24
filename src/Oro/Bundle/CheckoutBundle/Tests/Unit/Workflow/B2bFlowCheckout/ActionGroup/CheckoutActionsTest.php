<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CheckoutActions;
use Oro\Bundle\CurrencyBundle\Entity\MultiCurrency;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CheckoutActionsTest extends TestCase
{
    use EntityTrait;

    private EntityAliasResolver|MockObject $entityAliasResolver;
    private EntityNameResolver|MockObject $entityNameResolver;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private ActionExecutor|MockObject $actionExecutor;
    private AddressActionsInterface|MockObject $addressActions;
    private CheckoutActions $checkoutActions;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->addressActions = $this->createMock(AddressActionsInterface::class);

        $this->checkoutActions = new CheckoutActions(
            $this->entityAliasResolver,
            $this->entityNameResolver,
            $this->urlGenerator,
            $this->actionExecutor,
            $this->addressActions
        );
    }

    public function testGetCheckoutUrl(): void
    {
        $checkout = $this->getEntity(Checkout::class, ['id' => 123]);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_checkout_frontend_checkout', ['id' => 123, 'transition' => 'some_transition'])
            ->willReturn('/checkout/123/some_transition');

        $url = $this->checkoutActions->getCheckoutUrl($checkout, 'some_transition');

        $this->assertEquals('/checkout/123/some_transition', $url);
    }

    public function testPurchase(): void
    {
        $checkout = $this->getEntity(Checkout::class, ['id' => 123]);
        $checkout->setPaymentMethod('credit_card');
        $order = new Order();
        $order->setTotal(100);
        $order->setCurrency('USD');

        $this->urlGenerator->expects($this->exactly(3))
            ->method('generate')
            ->willReturnMap([
                [
                    'oro_checkout_frontend_checkout',
                    ['id' => 123, 'transition' => 'finish_checkout'],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/success'
                ],
                [
                    'oro_checkout_frontend_checkout',
                    ['id' => 123, 'transition' => 'payment_error'],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/error'
                ],
                [
                    'oro_checkout_frontend_checkout',
                    ['id' => 123, 'transition' => 'paid_partially'],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/partial'
                ],
            ]);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'payment_purchase',
                [
                    'attribute' => new PropertyPath('responseData'),
                    'object' => $order,
                    'amount' => 100,
                    'currency' => 'USD',
                    'paymentMethod' => 'credit_card',
                    'transactionOptions' => [
                        'successUrl' => '/success',
                        'failureUrl' => '/error',
                        'partiallyPaidUrl' => '/partial',
                        'failedShippingAddressUrl' => '/error',
                        'checkoutId' => 123
                    ]
                ]
            )
            ->willReturn(new ActionData(['responseData' => ['success' => true]]));

        $result = $this->checkoutActions->purchase($checkout, $order);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('responseData', $result);
    }

    public function testFinishCheckout(): void
    {
        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);
        $order = $this->getEntity(Order::class, ['id' => 2]);
        $order->addLineItem(new OrderLineItem());
        $order->setCurrency('USD');
        $totalObject = MultiCurrency::create(100, 'USD');
        $subtotalObject = MultiCurrency::create(100, 'USD');
        $order->setTotalObject($totalObject);
        $order->setSubtotalObject($subtotalObject);

        $sourceEntity = $this->getEntity(ShoppingList::class, ['id' => 3]);
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntity);
        $checkout->setSource($checkoutSource);

        $this->addressActions->expects($this->once())
            ->method('actualizeAddresses')
            ->with($checkout, $order);

        $this->actionExecutor->expects($this->once())
            ->method('executeActionGroup')
            ->with('b2b_flow_checkout_send_order_confirmation_email', [
                'checkout' => $checkout,
                'order' => $order,
                'workflow' => 'b2b_flow_checkout'
            ]);

        $this->entityAliasResolver->expects($this->once())
            ->method('getAlias')
            ->with(Order::class)
            ->willReturn('order_alias');
        $this->entityNameResolver->expects($this->once())
            ->method('getName')
            ->with($sourceEntity)
            ->willReturn('SL1');

        $this->checkoutActions->finishCheckout($checkout, $order);

        $this->assertTrue($checkout->isCompleted());

        $completedData = $checkout->getCompletedData();
        $this->assertEquals(1, $completedData->offsetGet('itemsCount'));
        $this->assertEquals(
            [['entityAlias' => 'order_alias', 'entityId' => ['id' => 2]]],
            $completedData->offsetGet('orders')
        );
        $this->assertEquals('USD', $completedData->offsetGet('currency'));
        $this->assertEquals(100, $completedData->offsetGet('subtotal'));
        $this->assertEquals(100, $completedData->offsetGet('total'));
        $this->assertEquals('SL1', $completedData->offsetGet('startedFrom'));
    }

    public function testSendConfirmationEmail(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $order = $this->createMock(Order::class);

        $this->actionExecutor->expects($this->once())
            ->method('executeActionGroup')
            ->with('b2b_flow_checkout_send_order_confirmation_email', [
                'checkout' => $checkout,
                'order' => $order,
                'workflow' => 'b2b_flow_checkout'
            ]);

        $this->checkoutActions->sendConfirmationEmail($checkout, $order);
    }

    public function testFinalizeSourceEntityClearSource(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('clear_checkout_source_entity', [$checkout]);

        $this->checkoutActions->finalizeSourceEntity($checkout, false, false, false, true);
    }

    public function testFinalizeSourceEntityAutoRemoveSource(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('remove_checkout_source_entity', [$checkout]);

        $this->checkoutActions->finalizeSourceEntity($checkout, true, false, false, false);
    }

    public function testFinalizeSourceEntityManualRemoveSource(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('remove_checkout_source_entity', [$checkout]);

        $this->checkoutActions->finalizeSourceEntity($checkout, false, true, true, false);
    }
}
