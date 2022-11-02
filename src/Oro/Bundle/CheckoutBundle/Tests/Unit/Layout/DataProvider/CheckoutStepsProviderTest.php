<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutStepsProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutStepsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|WorkflowManager */
    private $workflowManager;

    /** @var CheckoutStepsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->provider = new CheckoutStepsProvider($this->workflowManager);
    }

    /**
     * @dataProvider getStepsDataProvider
     */
    public function testGetSteps(
        bool $displayOrdered,
        array $excludedStepNames,
        ArrayCollection $steps,
        ArrayCollection $expectedSteps
    ) {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->once())
            ->method('isStepsDisplayOrdered')
            ->willReturn($displayOrdered);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        if ($displayOrdered) {
            $stepManager = $this->createMock(StepManager::class);
            $stepManager->expects($this->once())
                ->method('getOrderedSteps')
                ->willReturn($steps);
            $workflow->expects($this->once())
                ->method('getStepManager')
                ->willReturn($stepManager);
        } else {
            $workflow->expects($this->once())
                ->method('getPassedStepsByWorkflowItem')
                ->with($workflowItem)
                ->willReturn($steps);
        }

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $result = $this->provider->getSteps($workflowItem, $excludedStepNames);
        $this->assertEquals($expectedSteps, $result);
    }

    /**
     * @dataProvider getStepsDataProvider
     */
    public function testGetStepOrder(
        bool $displayOrdered,
        array $excludedStepNames,
        ArrayCollection $steps,
        ArrayCollection $expectedSteps,
        string $currentStepName,
        int $expectedStepOrder
    ) {
        $workflowItem  = $this->createMock(WorkflowItem::class);

        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->once())
            ->method('isStepsDisplayOrdered')
            ->willReturn($displayOrdered);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        if ($displayOrdered) {
            $stepManager = $this->createMock(StepManager::class);
            $stepManager->expects($this->once())
                ->method('getOrderedSteps')
                ->willReturn($steps);
            $workflow->expects($this->once())
                ->method('getStepManager')
                ->willReturn($stepManager);
        } else {
            $workflow->expects($this->once())
                ->method('getPassedStepsByWorkflowItem')
                ->with($workflowItem)
                ->willReturn($steps);
        }

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $this->assertEquals(
            $expectedStepOrder,
            $this->provider->getStepOrder($workflowItem, $currentStepName, $excludedStepNames)
        );
    }

    public function getStepsDataProvider(): array
    {
        $step1 = (new Step())->setOrder(100)->setName('first_step');
        $step2 = (new Step())->setOrder(100)->setName('second_step');
        $step3 = (new Step())->setOrder(100)->setName('third_step');

        $excludedResult = new ArrayCollection([$step1, $step2, $step3]);
        $excludedResult->removeElement($step2);

        return [
            'displayOrdered' => [
                'displayOrdered' => true,
                'excludedStepNames' => [],
                'steps' => new ArrayCollection([$step1, $step2, $step3]),
                'expectedSteps' => new ArrayCollection([$step1, $step2, $step3]),
                'currentStepName' => 'third_step',
                'expectedStepOrder' => 3
            ],
            'displayUnOrdered' => [
                'displayOrdered' => false,
                'excludedStepNames' => [],
                'steps' => new ArrayCollection([$step1, $step2, $step3]),
                'expectedSteps' => new ArrayCollection([$step1, $step2, $step3]),
                'currentStepName' => 'third_step',
                'expectedStepOrder' => 3
            ],
            'with excluded step names' => [
                'displayOrdered' => false,
                'excludedStepNames' => ['second_step'],
                'steps' => new ArrayCollection([$step1, $step2, $step3]),
                'expectedSteps' => $excludedResult,
                'currentStepName' => 'third_step',
                'expectedStepOrder' => 2
            ],
        ];
    }
}
