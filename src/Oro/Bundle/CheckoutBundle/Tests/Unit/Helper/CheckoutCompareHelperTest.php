<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Helper;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroup;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutCompareHelper;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShoppingListLineItemDiffMapper;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorageInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckoutCompareHelperTest extends TestCase
{
    private CheckoutDiffStorageInterface|MockObject $diffStorage;
    private ShoppingListLineItemDiffMapper|MockObject $diffMapper;
    private WorkflowManager|MockObject $workflowManager;
    private ActionGroupRegistry|MockObject $actionGroupRegistry;
    private CheckoutCompareHelper $helper;

    protected function setUp(): void
    {
        $this->diffStorage = $this->createMock(CheckoutDiffStorageInterface::class);
        $this->diffMapper = $this->createMock(ShoppingListLineItemDiffMapper::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->actionGroupRegistry = $this->createMock(ActionGroupRegistry::class);

        $this->helper = new CheckoutCompareHelper(
            $this->diffStorage,
            $this->diffMapper,
            $this->workflowManager,
            $this->actionGroupRegistry
        );
    }

    public function testCompare(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $shoppingList = new ShoppingList();

        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($shoppingList);
        $actionData = new ActionData(['shoppingList' => $shoppingList, 'forceStartCheckout' => false]);
        $actionGroup = $this->createMock(ActionGroup::class);

        $this->actionGroupRegistry->expects($this->once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($actionGroup);
        $actionGroup->expects($this->once())
            ->method('execute')
            ->with($actionData);

        $this->helper->compare($checkout);
    }

    public function testCompareIgnore(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $quoteDemand = new QuoteDemand();

        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($quoteDemand);
        $this->actionGroupRegistry->expects($this->never())
            ->method('findByName')
            ->with('start_shoppinglist_checkout');

        $this->helper->compare($checkout);
    }

    public function testRestartCheckoutIfSourceLineItemsChangedWithChanges(): void
    {
        $state1 = [
            'shopping_list_line_item' => [
                'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
            ]
        ];
        $state2 = [
            'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iout_of_stock',
            'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
        ];

        $checkout = $this->createMock(Checkout::class);
        $rawCheckout = $this->createMock(Checkout::class);
        $token = '_state_token';

        $workflowItem = $this->getWorkflowItem($checkout, $token);

        $this->compareStates($checkout, $token, $state1, $rawCheckout, $state2, false);

        $workflowName = '_checkout_workflow_name';
        $shoppingList = new ShoppingList();
        $checkoutSource = $this->createMock(CheckoutSourceStub::class);
        $actionData = new ActionData(['shoppingList' => $shoppingList, 'forceStartCheckout' => true]);
        $actionGroup = $this->createMock(ActionGroup::class);

        $workflowItem->expects($this->once())
            ->method('getWorkflowName')
            ->willReturn($workflowName);
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);
        $checkout->expects($this->once())
            ->method('getSource')
            ->willReturn($checkoutSource);
        $checkoutSource->expects($this->once())
            ->method('getShoppingList')
            ->willReturn($shoppingList);
        $this->workflowManager->expects($this->once())
            ->method('resetWorkflowItem')
            ->with($workflowItem);
        $this->workflowManager->expects($this->once())
            ->method('startWorkflow')
            ->with($workflowName, $checkout);
        $this->actionGroupRegistry->expects($this->once())
            ->method('findByName')
            ->with('start_shoppinglist_checkout')
            ->willReturn($actionGroup);
        $actionGroup->expects($this->once())
            ->method('execute')
            ->with($actionData);

        $this->helper->resetCheckoutIfSourceLineItemsChanged($checkout, $rawCheckout);
    }

    public function testRestartCheckoutIfSourceLineItemsChangedWithoutChanges(): void
    {
        $state1 = [
            'shopping_list_line_item' => [
                'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
                'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
            ]
        ];
        $state2 = [
            'sSKU123-uset-q1-pUSD120-w10kg-d1x1x1cm-iin_stock',
            'sSKU123-uitem-q1-pUSD10-w1kg-d1x1x1cm-iin_stock'
        ];

        $checkout = $this->createMock(Checkout::class);
        $rawCheckout = $this->createMock(Checkout::class);
        $token = '_state_token';

        $workflowItem = $this->getWorkflowItem($checkout, $token);

        $this->compareStates($checkout, $token, $state1, $rawCheckout, $state2, true);

        $workflowItem->expects($this->never())
            ->method('getWorkflowName');
        $workflowItem->expects($this->never())
            ->method('getEntity');
        $this->workflowManager->expects($this->never())
            ->method('resetWorkflowItem');
        $this->workflowManager->expects($this->never())
            ->method('startWorkflow');
        $this->actionGroupRegistry->expects($this->never())
            ->method('get')
            ->with('start_shoppinglist_checkout');

        $this->helper->resetCheckoutIfSourceLineItemsChanged($checkout, $rawCheckout);
    }

    public function testRestartCheckoutIfSourceLineItemsChangedEmptyCheckout(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $rawCheckout = null;
        $this->workflowManager->expects($this->never())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout);
        $this->helper->resetCheckoutIfSourceLineItemsChanged($checkout, $rawCheckout);

        $checkout = null;
        $rawCheckout = $this->createMock(Checkout::class);
        $this->workflowManager->expects($this->never())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout);
        $this->helper->resetCheckoutIfSourceLineItemsChanged($checkout, $rawCheckout);
    }

    public function testRestartCheckoutIfSourceLineItemsChangedEmptyToken(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $rawCheckout = $this->createMock(Checkout::class);
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([]);
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unable to find correct WorkflowItem for current checkout');
        $this->helper->resetCheckoutIfSourceLineItemsChanged($checkout, $rawCheckout);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowData = $this->createMock(WorkflowData::class);
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([$workflowItem]);
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);
        $workflowData->expects($this->once())
            ->method('has')
            ->with('state_token')
            ->willReturn(false);
        $workflowData->expects($this->never())
            ->method('get')
            ->with('state_token');
        $this->helper->resetCheckoutIfSourceLineItemsChanged($checkout, $rawCheckout);
    }

    protected function getLineItem(string $productSku, string $productUnitCode, int $quantity): CheckoutLineItem
    {
        $item = new CheckoutLineItem();

        $item->setProductSku($productSku)
            ->setProductUnitCode($productUnitCode)
            ->setQuantity($quantity);

        return $item;
    }

    private function compareStates(
        Checkout $checkout,
        string $token,
        array $state1,
        ?Checkout $checkout2,
        array $state2,
        bool $compareResult
    ): void {
        $this->diffMapper->expects($this->once())
            ->method('getName')
            ->willReturn('shopping_list_line_item');
        $this->diffStorage->expects($this->once())
            ->method('getState')
            ->with($checkout, $token)
            ->willReturn($state1);
        $this->diffMapper->expects($this->once())
            ->method('getCurrentState')
            ->with($checkout2)
            ->willReturn($state2);

        $this->diffMapper->expects($this->once())
            ->method('isStatesEqual')
            ->with($checkout, $state1['shopping_list_line_item'], $state2)
            ->willReturn($compareResult);
    }

    private function getWorkflowItem(MockObject|Checkout $checkout, string $token): MockObject|WorkflowItem
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowData = $this->createMock(WorkflowData::class);
        $this->workflowManager->expects($this->once())
            ->method('getWorkflowItemsByEntity')
            ->with($checkout)
            ->willReturn([$workflowItem]);
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);
        $workflowData->expects($this->once())
            ->method('has')
            ->with('state_token')
            ->willReturn(true);
        $workflowData->expects($this->once())
            ->method('get')
            ->with('state_token')
            ->willReturn($token);

        return $workflowItem;
    }
}
