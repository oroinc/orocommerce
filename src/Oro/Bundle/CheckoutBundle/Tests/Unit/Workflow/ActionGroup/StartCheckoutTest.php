<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Provider\PrepareCheckoutSettingsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\FindOrCreateCheckoutInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PrepareCheckoutSettingsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartCheckout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateWorkflowItemInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class StartCheckoutTest extends TestCase
{
    use EntityTrait;

    private PrepareCheckoutSettingsInterface|MockObject $prepareCheckoutSettings;
    private FindOrCreateCheckoutInterface|MockObject $findOrCreateCheckout;
    private PrepareCheckoutSettingsProvider|MockObject $prepareCheckoutSettingsProvider;
    private UpdateWorkflowItemInterface|MockObject $updateWorkflowItem;
    private TokenStorageInterface|MockObject $tokenStorage;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private ActionExecutor|MockObject $actionExecutor;

    private StartCheckout $startCheckout;

    protected function setUp(): void
    {
        $this->prepareCheckoutSettings = $this->createMock(PrepareCheckoutSettingsInterface::class);
        $this->findOrCreateCheckout = $this->createMock(FindOrCreateCheckoutInterface::class);
        $this->prepareCheckoutSettingsProvider = $this->createMock(PrepareCheckoutSettingsProvider::class);
        $this->updateWorkflowItem = $this->createMock(UpdateWorkflowItemInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);

        $this->startCheckout = new StartCheckout(
            $this->prepareCheckoutSettings,
            $this->findOrCreateCheckout,
            $this->prepareCheckoutSettingsProvider,
            $this->updateWorkflowItem,
            $this->tokenStorage,
            $this->urlGenerator,
            $this->actionExecutor
        );
    }

    public function testExecuteWithForceUpdateSettings()
    {
        $sourceCriteria = ['some_criteria' => 'value'];
        $data = ['shippingAddress' => new OrderAddress()];
        $settings = [];
        $sourceEntity = new ShoppingList();
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntity);
        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);
        $checkout->setSource($checkoutSource);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);

        $findResult = [
            'checkout' => $checkout,
            'workflowItem' => $workflowItem,
            'updateData' => true,
        ];

        $this->findOrCreateCheckout
            ->method('execute')
            ->with($sourceCriteria, $data, false, false, null)
            ->willReturn($findResult);

        $preparedSettings = ['prepared_settings' => 'value'];
        $this->prepareCheckoutSettings
            ->method('execute')
            ->with($sourceEntity)
            ->willReturn($preparedSettings);

        $this->prepareCheckoutSettingsProvider
            ->method('prepareSettings')
            ->with($checkout, $preparedSettings)
            ->willReturn(['merged_settings' => 'value']);

        $this->updateWorkflowItem
            ->method('execute')
            ->with($checkout, ['merged_settings' => 'value', 'shipping_address' => $data['shippingAddress']]);

        $this->tokenStorage
            ->method('getToken')
            ->willReturn(null);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->with(
                ExtendableCondition::NAME,
                [
                    'events' => ['extendable_condition.start_checkout'],
                    'showErrors' => false,
                    'eventData' => [
                        'checkout' => $workflowItem->getEntity(),
                        'validateOnStartCheckout' => true,
                        ExtendableConditionEvent::CONTEXT_KEY => new ActionData([
                            'checkout' => $workflowItem->getEntity(),
                            'validateOnStartCheckout' => true
                        ])
                    ]
                ],
            )
            ->willReturn(true);

        $this->urlGenerator
            ->method('generate')
            ->with('oro_checkout_frontend_checkout', ['id' => $checkout->getId()])
            ->willReturn('generated_url');

        $result = $this->startCheckout->execute(
            $sourceCriteria,
            false,
            $data,
            $settings,
            false,
            false,
            null,
            true
        );

        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('redirectUrl', $result);
        $this->assertEquals('generated_url', $result['redirectUrl']);
        $this->assertSame($checkout, $result['checkout']);
        $this->assertSame($workflowItem, $result['workflowItem']);
    }

    public function testExecuteWithoutForceUpdateSettings()
    {
        $sourceCriteria = ['some_criteria' => 'value'];
        $data = ['shippingAddress' => new OrderAddress()];
        $settings = [];
        $sourceEntity = new ShoppingList();
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntity);
        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);
        $checkout->setSource($checkoutSource);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);

        $findResult = [
            'checkout' => $checkout,
            'workflowItem' => $workflowItem,
            'updateData' => false,
        ];

        $this->findOrCreateCheckout
            ->method('execute')
            ->with($sourceCriteria, $data, false, false, null)
            ->willReturn($findResult);

        $this->prepareCheckoutSettings->expects($this->never())
            ->method('execute');

        $this->prepareCheckoutSettingsProvider->expects($this->never())
            ->method('prepareSettings');

        $this->updateWorkflowItem->expects($this->never())
            ->method('execute');

        $this->tokenStorage
            ->method('getToken')
            ->willReturn(null);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->with(
                ExtendableCondition::NAME,
                [
                    'events' => ['extendable_condition.start_checkout'],
                    'showErrors' => false,
                    'eventData' => [
                        'checkout' => $workflowItem->getEntity(),
                        'validateOnStartCheckout' => true,
                        ExtendableConditionEvent::CONTEXT_KEY => new ActionData([
                            'checkout' => $workflowItem->getEntity(),
                            'validateOnStartCheckout' => true
                        ])
                    ]
                ],
            )
            ->willReturn(true);

        $this->urlGenerator
            ->method('generate')
            ->with('oro_checkout_frontend_checkout', ['id' => $checkout->getId()])
            ->willReturn('generated_url');

        $result = $this->startCheckout->execute(
            $sourceCriteria,
            false,
            $data,
            $settings,
            false,
            false,
            null,
            true
        );

        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('redirectUrl', $result);
        $this->assertEquals('generated_url', $result['redirectUrl']);
        $this->assertSame($checkout, $result['checkout']);
        $this->assertSame($workflowItem, $result['workflowItem']);
    }

    public function testExecuteWithoutForceUpdateSettingsForVisitor()
    {
        $sourceCriteria = ['some_criteria' => 'value'];
        $data = ['shippingAddress' => new OrderAddress()];
        $settings = [];
        $sourceEntity = new ShoppingList();
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntity);
        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);
        $checkout->setSource($checkoutSource);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $checkout->setRegisteredCustomerUser(new CustomerUser());

        $findResult = [
            'checkout' => $checkout,
            'workflowItem' => $workflowItem,
            'updateData' => false,
        ];

        $this->findOrCreateCheckout
            ->method('execute')
            ->with($sourceCriteria, $data, false, false, null)
            ->willReturn($findResult);

        $this->prepareCheckoutSettings->expects($this->never())
            ->method('execute');

        $this->prepareCheckoutSettingsProvider->expects($this->never())
            ->method('prepareSettings');

        $this->updateWorkflowItem->expects($this->never())
            ->method('execute');

        $visitor = $this->createMock(CustomerVisitor::class);
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $token->expects($this->any())
            ->method('getVisitor')
            ->willReturn($visitor);
        $this->tokenStorage
            ->method('getToken')
            ->willReturn($token);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('flush_entity');

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->with(
                ExtendableCondition::NAME,
                [
                    'events' => ['extendable_condition.start_checkout'],
                    'showErrors' => false,
                    'eventData' => [
                        'checkout' => $workflowItem->getEntity(),
                        'validateOnStartCheckout' => true,
                        ExtendableConditionEvent::CONTEXT_KEY => new ActionData([
                            'checkout' => $workflowItem->getEntity(),
                            'validateOnStartCheckout' => true
                        ])
                    ]
                ],
            )
            ->willReturn(true);

        $this->urlGenerator
            ->method('generate')
            ->with('oro_checkout_frontend_checkout', ['id' => $checkout->getId()])
            ->willReturn('generated_url');

        $result = $this->startCheckout->execute(
            $sourceCriteria,
            false,
            $data,
            $settings,
            false,
            false,
            null,
            true
        );

        $this->assertArrayNotHasKey('errors', $result);
        $this->assertArrayHasKey('redirectUrl', $result);
        $this->assertEquals('generated_url', $result['redirectUrl']);
        $this->assertSame($checkout, $result['checkout']);
        $this->assertNull($result['checkout']->getRegisteredCustomerUser());
        $this->assertSame($workflowItem, $result['workflowItem']);
    }

    public function testExecuteWithErrors()
    {
        $sourceCriteria = ['some_criteria' => 'value'];
        $data = ['shippingAddress' => new OrderAddress()];
        $settings = [];
        $sourceEntity = new ShoppingList();
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($sourceEntity);
        $checkout = $this->getEntity(Checkout::class, ['id' => 1]);
        $checkout->setSource($checkoutSource);
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);

        $findResult = [
            'checkout' => $checkout,
            'workflowItem' => $workflowItem,
            'updateData' => false,
        ];

        $this->findOrCreateCheckout
            ->method('execute')
            ->with($sourceCriteria, $data, false, false, null)
            ->willReturn($findResult);

        $this->prepareCheckoutSettings->expects($this->never())
            ->method('execute');

        $this->prepareCheckoutSettingsProvider->expects($this->never())
            ->method('prepareSettings');

        $this->updateWorkflowItem->expects($this->never())
            ->method('execute');

        $this->tokenStorage
            ->method('getToken')
            ->willReturn(null);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->with(
                ExtendableCondition::NAME,
                [
                    'events' => ['extendable_condition.start_checkout'],
                    'showErrors' => false,
                    'eventData' => [
                        'checkout' => $workflowItem->getEntity(),
                        'validateOnStartCheckout' => true,
                        ExtendableConditionEvent::CONTEXT_KEY => new ActionData([
                            'checkout' => $workflowItem->getEntity(),
                            'validateOnStartCheckout' => true
                        ])
                    ]
                ],
            )
            ->willReturn(false);

        $this->urlGenerator->expects($this->never())
            ->method('generate');

        $result = $this->startCheckout->execute(
            $sourceCriteria,
            false,
            $data,
            $settings,
            false,
            false,
            null,
            true
        );

        $this->assertArrayNotHasKey('redirectUrl', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertSame($checkout, $result['checkout']);
        $this->assertSame($workflowItem, $result['workflowItem']);
    }
}
