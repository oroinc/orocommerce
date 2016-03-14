<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\ContinueTransitionDataProvider;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

class ContinueTransitionDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var ContinueTransitionDataProvider
     */
    protected $dataProvider;

    protected function setUp()
    {
        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataProvider = new ContinueTransitionDataProvider($this->workflowManager);
    }

    public function testGetData()
    {
        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();
        $checkout->setWorkflowItem($workflowItem);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextInterface $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $data->expects($this->once())
            ->method('get')
            ->with('checkout')
            ->will($this->returnValue($checkout));
        $context->expects($this->once())
            ->method('data')
            ->will($this->returnValue($data));

        $transitionWithoutForm = new Transition();
        $transitionWithoutForm->setName('transition1');

        $continueTransition = new Transition();
        $continueTransition->setName('transition3');
        $continueTransition->setFrontendOptions(['is_checkout_continue' => true]);
        $continueTransition->setFormType('transition_type');

        $transitions = [
            $transitionWithoutForm,
            $continueTransition
        ];

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue($transitions));
        $this->workflowManager->expects($this->any())
            ->method('isTransitionAvailable')
            ->willReturn(true);

        $expected = new TransitionData($continueTransition, true, new ArrayCollection());
        $this->assertEquals($expected, $this->dataProvider->getData($context));
    }
}
