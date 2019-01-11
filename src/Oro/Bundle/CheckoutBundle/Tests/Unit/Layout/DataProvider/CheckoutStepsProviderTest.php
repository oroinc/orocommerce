<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutStepsProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutStepsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutStepsProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|WorkflowManager
     */
    protected $workflowManager;


    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->provider = new CheckoutStepsProvider($this->workflowManager);
    }

    /**
     * @dataProvider getStepsDataProvider
     *
     * @param bool $displayOrdered
     * @param array $excludedStepNames
     * @param array $steps
     * @param array $expectedSteps
     */
    public function testGetSteps($displayOrdered, $excludedStepNames, $steps, $expectedSteps)
    {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem  = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowDefinition->expects($this->once())
            ->method('isStepsDisplayOrdered')
            ->willReturn($displayOrdered);

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        if ($displayOrdered) {
            $stepManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\StepManager')
                ->disableOriginalConstructor()
                ->getMock();
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
     *
     * @param bool $displayOrdered
     * @param array $excludedStepNames
     * @param array $steps
     * @param array $expectedSteps
     * @param string $currentStepName
     * @param int $expectedStepOrder
     */
    public function testGetStepOrder(
        $displayOrdered,
        $excludedStepNames,
        $steps,
        $expectedSteps,
        $currentStepName,
        $expectedStepOrder
    ) {
        /** @var WorkflowItem|\PHPUnit\Framework\MockObject\MockObject $workflowItem */
        $workflowItem  = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowDefinition->expects($this->once())
            ->method('isStepsDisplayOrdered')
            ->willReturn($displayOrdered);

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);

        if ($displayOrdered) {
            $stepManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\StepManager')
                ->disableOriginalConstructor()
                ->getMock();
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

    /**
     * @return array
     */
    public function getStepsDataProvider()
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
