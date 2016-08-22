<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAwareManager;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\WorkflowStepMapper;

class WorkflowStepMapperTest extends AbstractCheckoutDiffMapperTest
{
    /** @var WorkflowAwareManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowAwareManager;

    protected function setUp()
    {
        $this->workflowAwareManager = $this->getMockBuilder(WorkflowAwareManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->workflowAwareManager);
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
