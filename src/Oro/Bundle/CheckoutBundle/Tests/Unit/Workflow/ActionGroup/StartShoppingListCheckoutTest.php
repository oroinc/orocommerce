<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartCheckoutInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckout;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartShoppingListCheckoutTest extends TestCase
{
    private ShoppingListUrlProvider|MockObject $shoppingListUrlProvider;
    private ShoppingListLimitManager|MockObject $shoppingListLimitManager;
    private StartCheckoutInterface|MockObject $startCheckout;

    private StartShoppingListCheckout $startShoppingListCheckout;

    protected function setUp(): void
    {
        $this->shoppingListUrlProvider = $this->createMock(ShoppingListUrlProvider::class);
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->startCheckout = $this->createMock(StartCheckoutInterface::class);

        $this->startShoppingListCheckout = new StartShoppingListCheckout(
            $this->shoppingListUrlProvider,
            $this->shoppingListLimitManager,
            $this->startCheckout
        );
    }

    public function testExecuteWithSingleShoppingList(): void
    {
        $shoppingList = new ShoppingList();
        $editLink = 'http://example.com/shopping-list';

        // Set up mocks
        $this->shoppingListUrlProvider->expects($this->once())
            ->method('getFrontendUrl')
            ->with($shoppingList)
            ->willReturn($editLink);

        $this->shoppingListLimitManager->expects($this->once())
            ->method('isOnlyOneEnabled')
            ->willReturn(true);

        $startResult = [
            'checkout' => new Checkout(),
            'workflowItem' => new WorkflowItem()
        ];
        $this->startCheckout->expects($this->once())
            ->method('execute')
            ->with(
                ['shoppingList' => $shoppingList],
                false,
                [],
                [
                    'allow_manual_source_remove' => false,
                    'remove_source' => false,
                    'clear_source' => true,
                    'edit_order_link' => $editLink,
                    'source_remove_label' => 'oro.frontend.shoppinglist.workflow.remove_source.label'
                ],
                false,
                false,
                null,
                true
            )
            ->willReturn($startResult);

        $result = $this->startShoppingListCheckout->execute(
            $shoppingList,
            false,
            false,
            true,
            true,
            true,
            false
        );

        $this->assertEquals($startResult, $result);
    }

    public function testExecuteWithMultipleShoppingLists(): void
    {
        $shoppingList = new ShoppingList();
        $editLink = 'http://example.com/shopping-list';

        // Set up mocks
        $this->shoppingListUrlProvider->expects($this->once())
            ->method('getFrontendUrl')
            ->with($shoppingList)
            ->willReturn($editLink);

        $this->shoppingListLimitManager->expects($this->once())
            ->method('isOnlyOneEnabled')
            ->willReturn(false);

        $startResult = [
            'checkout' => new Checkout(),
            'workflowItem' => new WorkflowItem()
        ];
        $this->startCheckout->expects($this->once())
            ->method('execute')
            ->with(
                ['shoppingList' => $shoppingList],
                false,
                [],
                [
                    'allow_manual_source_remove' => true,
                    'remove_source' => true,
                    'clear_source' => false,
                    'edit_order_link' => $editLink,
                    'source_remove_label' => 'oro.frontend.shoppinglist.workflow.remove_source.label'
                ],
                false,
                false,
                null,
                true
            )
            ->willReturn($startResult);

        $result = $this->startShoppingListCheckout->execute(
            $shoppingList,
            false,
            false,
            true,
            true,
            true,
            false
        );

        $this->assertEquals($startResult, $result);
    }
}
