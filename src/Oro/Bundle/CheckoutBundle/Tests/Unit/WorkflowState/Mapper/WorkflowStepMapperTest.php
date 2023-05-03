<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\WorkflowStepMapper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAwareManager;

class WorkflowStepMapperTest extends AbstractCheckoutDiffMapperTest
{
    /** @var WorkflowAwareManager|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowAwareManager;

    protected function setUp(): void
    {
        $this->workflowAwareManager = $this->createMock(WorkflowAwareManager::class);

        parent::setUp();
    }

    public function testGetName()
    {
        $this->assertEquals('workflow_step', $this->mapper->getName());
    }

    public function testGetCurrentStateWithoutWorkflowItem()
    {
        $this->workflowAwareManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($this->checkout)
            ->willReturn(null);

        $this->assertNull($this->mapper->getCurrentState($this->checkout));
    }

    public function testGetCurrentStateItemWithoutStep()
    {
        $workflowItem = new WorkflowItem();

        $this->workflowAwareManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($this->checkout)
            ->willReturn($workflowItem);

        $this->assertNull($this->mapper->getCurrentState($this->checkout));
    }

    public function testGetCurrentState()
    {
        $workflowStep = new WorkflowStep();
        $workflowStep->setName('stepName');

        $workflowItem = new WorkflowItem();
        $workflowItem->setCurrentStep($workflowStep);

        $this->workflowAwareManager->expects($this->once())
            ->method('getWorkflowItem')
            ->with($this->checkout)
            ->willReturn($workflowItem);

        $this->assertEquals('stepName', $this->mapper->getCurrentState($this->checkout));
    }

    public function testIsStatesEqualTrue()
    {
        $this->assertTrue($this->mapper->isStatesEqual(
            $this->checkout,
            'step1',
            'step1'
        ));
    }

    public function testIsStatesEqualFalse()
    {
        $this->assertFalse($this->mapper->isStatesEqual(
            $this->checkout,
            'step1',
            'step2'
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new WorkflowStepMapper($this->workflowAwareManager);
    }
}
