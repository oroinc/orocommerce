<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Oro\Bundle\CheckoutBundle\Condition\IsWorkflowStartFromShoppingListAllowed;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\StartFromQuickOrderFormTransition;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Processor\AbstractShoppingListQuickAddProcessor;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartFromQuickOrderFormTransitionTest extends TestCase
{
    private AbstractShoppingListQuickAddProcessor|MockObject $quickAddCheckoutProcessor;
    private ShoppingListLimitManager|MockObject $shoppingListLimitManager;
    private IsWorkflowStartFromShoppingListAllowed|MockObject $shoppingListAllowed;
    private CurrentShoppingListManager|MockObject $currentShoppingListManager;

    private StartFromQuickOrderFormTransition $transition;

    protected function setUp(): void
    {
        $this->quickAddCheckoutProcessor = $this->createMock(AbstractShoppingListQuickAddProcessor::class);
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->shoppingListAllowed = $this->createMock(IsWorkflowStartFromShoppingListAllowed::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);

        $this->transition = new StartFromQuickOrderFormTransition(
            $this->quickAddCheckoutProcessor,
            $this->shoppingListLimitManager,
            $this->shoppingListAllowed,
            $this->currentShoppingListManager
        );
    }

    /**
     * @dataProvider allowanceDataProvider
     */
    public function testIsPreConditionAllowed(bool $isAllowed, bool $isAllowedForAny, bool $expected): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowResult = new WorkflowResult();

        $workflowItem->expects($this->once())
            ->method('getResult')
            ->willReturn($workflowResult);

        $this->quickAddCheckoutProcessor->expects($this->any())
            ->method('isAllowed')
            ->willReturn($isAllowed);

        $this->shoppingListAllowed->expects($this->any())
            ->method('isAllowedForAny')
            ->willReturn($isAllowedForAny);

        $this->shoppingListLimitManager->expects($this->once())
            ->method('isReachedLimit')
            ->willReturn(true);

        $this->shoppingListLimitManager->expects($this->once())
            ->method('getShoppingListLimitForUser')
            ->willReturn(5);

        $this->currentShoppingListManager->expects($this->once())
            ->method('isCurrentShoppingListEmpty')
            ->willReturn(false);

        $this->assertSame($expected, $this->transition->isPreConditionAllowed($workflowItem));

        $this->assertSame($isAllowed, $workflowResult->offsetGet('isAllowed'));
        $this->assertSame($isAllowedForAny, $workflowResult->offsetGet('isCheckoutAllowed'));

        $this->assertTrue($workflowResult->offsetGet('isReachedLimit'));
        $this->assertEquals(5, $workflowResult->offsetGet('shoppingListLimit'));
        $this->assertFalse($workflowResult->offsetGet('isCurrentShoppingListEmpty'));
        $this->assertTrue($workflowResult->offsetGet('doShowConfirmation'));
    }

    public static function allowanceDataProvider(): array
    {
        return [
            [true, true, true],
            [false, false, false],
            [false, true, false],
            [true, false, false],
        ];
    }
}
