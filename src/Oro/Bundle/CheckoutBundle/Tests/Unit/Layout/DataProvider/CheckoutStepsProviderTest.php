<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\CheckoutStepsProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutStepsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

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
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new CheckoutStepsProvider($this->workflowManager);
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature('consents');
    }

    /**
     * @dataProvider getDataDataProvider
     * @param bool $displayOrdered
     * @param array $expected
     */
    public function testGetSteps($displayOrdered, array $expected)
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
                ->willReturn($expected);
            $workflow->expects($this->once())
                ->method('getStepManager')
                ->willReturn($stepManager);
        } else {
            $workflow->expects($this->once())
                ->method('getPassedStepsByWorkflowItem')
                ->with($workflowItem)
                ->willReturn($expected);
        }

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowItem)
            ->willReturn($workflow);

        $result = $this->provider->getSteps($workflowItem);
        $this->assertEquals($expected, $result);
    }

    public function testGetExcludedSteps()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->assertEquals([], $this->provider->getExcludedSteps());
    }

    public function testGetExcludedStepsWithPredefinedSteps()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->assertEquals(['another_step'], $this->provider->getExcludedSteps(['another_step']));
    }

    public function testGetExcludedStepsFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->assertEquals(
            ['another_step', 'customer_consents'],
            $this->provider->getExcludedSteps(['another_step'])
        );
    }

    public function testGetStepOrder()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);

        $this->assertEquals(2, $this->provider->getStepOrder(2));
    }

    public function testGetStepOrderFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        $this->assertEquals(1, $this->provider->getStepOrder(2));
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        $step1 = $this->getEntity('Oro\Bundle\WorkflowBundle\Model\Step', ['order' => 100]);
        $step2 = $this->getEntity('Oro\Bundle\WorkflowBundle\Model\Step', ['order' => 200]);
        $steps = [$step1, $step2];
        return [
            'displayOrdered' => [
                'displayOrdered' => true,
                'expected' => $steps
            ],
            'displayUnOrdered' => [
                'displayOrdered' => false,
                'expected' => $steps
            ],
        ];
    }
}
