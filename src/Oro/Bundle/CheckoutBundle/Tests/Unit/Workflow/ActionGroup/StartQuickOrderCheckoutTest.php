<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartQuickOrderCheckout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckoutInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class StartQuickOrderCheckoutTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private UserCurrencyManager|MockObject $userCurrencyManager;
    private CheckoutRepository|MockObject $checkoutRepository;
    private ManagerRegistry|MockObject $registry;
    private StartShoppingListCheckoutInterface|MockObject $startShoppingListCheckout;
    private WorkflowManager|MockObject $workflowManager;
    private OrderLimitProviderInterface|MockObject $shoppingListLimitProvider;
    private OrderLimitFormattedProviderInterface|MockObject $shoppingListLimitFormattedProvider;
    private TranslatorInterface|MockObject $translator;

    private StartQuickOrderCheckout $startQuickOrderCheckout;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->startShoppingListCheckout = $this->createMock(StartShoppingListCheckoutInterface::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->shoppingListLimitProvider = $this->createMock(OrderLimitProviderInterface::class);
        $this->shoppingListLimitFormattedProvider = $this->createMock(OrderLimitFormattedProviderInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->startQuickOrderCheckout = new StartQuickOrderCheckout(
            $this->actionExecutor,
            $this->userCurrencyManager,
            $this->checkoutRepository,
            $this->registry,
            $this->startShoppingListCheckout,
            $this->workflowManager
        );
        $this->startQuickOrderCheckout->setOrderLimitProviders(
            $this->shoppingListLimitProvider,
            $this->shoppingListLimitFormattedProvider
        );
        $this->startQuickOrderCheckout->setTranslator(
            $this->translator
        );
    }

    public function testExecuteWithoutTransitionName(): void
    {
        $shoppingList = new ShoppingList();
        $currentUser = $this->createMock(CustomerUser::class);
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('workflow_name');

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'get_active_user_or_null',
                ['attribute' => null]
            )
            ->willReturn(['attribute' => $currentUser]);

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMinimumOrderAmountMet')
            ->willReturn(true);

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMaximumOrderAmountMet')
            ->willReturn(true);

        $this->startShoppingListCheckout->expects($this->once())
            ->method('execute')
            ->with($shoppingList, false, true, true, true, true, false)
            ->willReturn(['checkout' => $checkout, 'workflowItem' => $workflowItem]);

        $result = $this->startQuickOrderCheckout->execute($shoppingList);

        $this->assertEquals(['checkout' => $checkout, 'workflowItem' => $workflowItem], $result);
    }

    public function testExecuteWithoutTransitionNameForGuest(): void
    {
        $shoppingList = new ShoppingList();
        $currentUser = $this->createMock(CustomerUser::class);
        $currentUser->expects($this->any())
            ->method('isGuest')
            ->willReturn(true);
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('workflow_name');

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('getName')->willReturn('b2b_checkout_flow');
        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);
        $this->userCurrencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('USD');

        $this->checkoutRepository->expects($this->once())
            ->method('findCheckoutByCustomerUserAndSourceCriteriaWithCurrency')
            ->with($currentUser, ['shoppingList' => $shoppingList], 'b2b_checkout_flow', 'USD')
            ->willReturn($checkout);

        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('remove')
            ->with($checkout);
        $em->expects($this->once())
            ->method('flush')
            ->with($checkout);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($em);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'get_active_user_or_null',
                ['attribute' => null]
            )
            ->willReturn(['attribute' => $currentUser]);

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMinimumOrderAmountMet')
            ->willReturn(true);

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMaximumOrderAmountMet')
            ->willReturn(true);

        $this->startShoppingListCheckout->expects($this->once())
            ->method('execute')
            ->with($shoppingList, false, true, true, true, true, false)
            ->willReturn(['checkout' => $checkout, 'workflowItem' => $workflowItem]);

        $result = $this->startQuickOrderCheckout->execute($shoppingList);

        $this->assertEquals(['checkout' => $checkout, 'workflowItem' => $workflowItem], $result);
    }

    public function testExecuteTransitionName(): void
    {
        $shoppingList = new ShoppingList();
        $currentUser = $this->createMock(CustomerUser::class);
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn('workflow_name');

        $this->actionExecutor->expects($this->exactly(2))
            ->method('executeAction')
            ->willReturnMap([
                ['get_active_user_or_null', ['attribute' => null], ['attribute' => $currentUser]],
                [
                    'transit_workflow',
                    [
                        'entity' => $checkout,
                        'workflow' => 'workflow_name',
                        'transition' => 'test_transition'
                    ],
                    null
                ]
            ]);

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMinimumOrderAmountMet')
            ->willReturn(true);

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMaximumOrderAmountMet')
            ->willReturn(true);

        $this->startShoppingListCheckout->expects($this->once())
            ->method('execute')
            ->with($shoppingList, false, true, true, true, true, false)
            ->willReturn(['checkout' => $checkout, 'workflowItem' => $workflowItem]);

        $result = $this->startQuickOrderCheckout->execute($shoppingList, 'test_transition');

        $this->assertEquals(['checkout' => $checkout, 'workflowItem' => $workflowItem], $result);
    }

    /**
     * @dataProvider executeOrderAmountsNotMetProvider
     */
    public function testExecuteOrderAmountsNotMet(
        bool $isMinimumOrderAmountMet,
        string $minimumOrderAmountFormatted,
        string $minimumOrderAmountDifferenceFormatted,
        bool $isMaximumOrderAmountMet,
        string $maximumOrderAmountFormatted,
        string $getMaximumOrderAmountDifferenceFormatted,
        array $expected
    ): void {
        $shoppingList = new ShoppingList();

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static fn (string $key, array $params) => sprintf(
                    '%s:%s:%s',
                    $key,
                    $params['%amount%'],
                    $params['%difference%']
                )
            );

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMinimumOrderAmountMet')
            ->willReturn($isMinimumOrderAmountMet);
        $this->shoppingListLimitFormattedProvider->expects($this->any())
            ->method('getMinimumOrderAmountFormatted')
            ->willReturn($minimumOrderAmountFormatted);
        $this->shoppingListLimitFormattedProvider->expects($this->any())
            ->method('getMinimumOrderAmountDifferenceFormatted')
            ->willReturn($minimumOrderAmountDifferenceFormatted);

        $this->shoppingListLimitProvider->expects($this->once())
            ->method('isMaximumOrderAmountMet')
            ->willReturn($isMaximumOrderAmountMet);
        $this->shoppingListLimitFormattedProvider->expects($this->any())
            ->method('getMaximumOrderAmountFormatted')
            ->willReturn($maximumOrderAmountFormatted);
        $this->shoppingListLimitFormattedProvider->expects($this->any())
            ->method('getMaximumOrderAmountDifferenceFormatted')
            ->willReturn($getMaximumOrderAmountDifferenceFormatted);

        $result = $this->startQuickOrderCheckout->execute($shoppingList, 'test_transition');

        $this->assertEquals(['errors' => $expected], $result);
    }

    public function executeOrderAmountsNotMetProvider(): array
    {
        return [
            'minimum amount not met' => [
                'isMinimumOrderAmountMet' => false,
                'minimumOrderAmountFormatted' => '$123.45',
                'minimumOrderAmountDifferenceFormatted' => '$23.50',
                'isMaximumOrderAmountMet' => true,
                'maximumOrderAmountFormatted' => '',
                'getMaximumOrderAmountDifferenceFormatted' => '',
                'expected' => [
                    'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_flash:$123.45:$23.50',
                ],
            ],
            'maximum amount not met' => [
                'isMinimumOrderAmountMet' => true,
                'minimumOrderAmountFormatted' => '',
                'minimumOrderAmountDifferenceFormatted' => '',
                'isMaximumOrderAmountMet' => false,
                'maximumOrderAmountFormatted' => '$543.21',
                'getMaximumOrderAmountDifferenceFormatted' => '$5.32',
                'expected' => [
                    'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_flash:$543.21:$5.32',
                ],
            ],
            'minimum and maximum amounts not met' => [
                'isMinimumOrderAmountMet' => false,
                'minimumOrderAmountFormatted' => '$123.45',
                'minimumOrderAmountDifferenceFormatted' => '$23.50',
                'isMaximumOrderAmountMet' => false,
                'maximumOrderAmountFormatted' => '$543.21',
                'getMaximumOrderAmountDifferenceFormatted' => '$5.32',
                'expected' => [
                    'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_flash:$123.45:$23.50',
                    'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_flash:$543.21:$5.32',
                ],
            ],
        ];
    }
}
