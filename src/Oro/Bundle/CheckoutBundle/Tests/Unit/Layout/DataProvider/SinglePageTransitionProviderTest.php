<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\SinglePageTransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;

class SinglePageTransitionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SinglePageTransitionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->provider = new SinglePageTransitionProvider($this->workflowManager);
    }

    /**
     * @dataProvider continueTransitionDataProvider
     * @param bool $isAllowed
     */
    public function testGetContinueTransition(bool $isAllowed)
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transitionName = 'testName';

        /** @var TransitionOptionsResolver|\PHPUnit_Framework_MockObject_MockObject $optionsResolver */
        $optionsResolver = $this->createMock(TransitionOptionsResolver::class);
        $transition = new Transition($optionsResolver);
        $transition->setName($transitionName)
            ->setFrontendOptions(['is_checkout_continue' => true])
            ->setFormType('transition_type');

        $errors = new ArrayCollection();

        $this->workflowManager->expects($this->any())
            ->method('isTransitionAvailable')
            ->willReturn(true);

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue(new ArrayCollection([$transition])));

        $expectedTransitionData = new TransitionData($transition, true, $errors);

        $this->assertEquals(
            $expectedTransitionData,
            $this->provider->getContinueTransition($workflowItem, $transitionName)
        );
    }

    public function testGetContinueTransitionWhenNullReturned()
    {
        $workflowItem = new WorkflowItem();
        $step = new WorkflowStep();
        $workflowItem->setCurrentStep($step);

        $transitionName = 'testName';

        /** @var TransitionOptionsResolver|\PHPUnit_Framework_MockObject_MockObject $optionsResolver */
        $optionsResolver = $this->createMock(TransitionOptionsResolver::class);
        $transition = new Transition($optionsResolver);
        $transition->setName($transitionName);

        $this->workflowManager->expects($this->any())
            ->method('isTransitionAvailable')
            ->willReturn(false);

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue(new ArrayCollection([$transition])));

        $this->assertNull($this->provider->getContinueTransition($workflowItem, $transitionName));
    }

    /**
     * @return array
     */
    public function continueTransitionDataProvider()
    {
        return [
            'transition is allowed' => [
                'isAllowed' => true
            ],
            'transition is not allowed' => [
                'isAllowed' => true
            ]
        ];
    }
}
