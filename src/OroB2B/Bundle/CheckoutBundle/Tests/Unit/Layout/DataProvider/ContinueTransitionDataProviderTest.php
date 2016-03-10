<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\ContinueTransitionDataProvider;
use OroB2B\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormDataProvider;

class ContinueTransitionDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WorkflowManager
     */
    protected $workflowManager;

    /**
     * @var TransitionFormDataProvider
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

        $transitionWithFormNotContinue = new Transition();
        $transitionWithFormNotContinue->setName('transition2');
        $transitionWithFormNotContinue->setFormOptions(['attribute_fields' => ['test' => null]]);

        $continueTransition = new Transition();
        $continueTransition->setName('transition3');
        $continueTransition->setFormOptions(['attribute_fields' => ['test' => null]]);
        $continueTransition->setFrontendOptions(['is_checkout_continue' => true]);
        $continueTransition->setFormType('transition_type');
        $transitions = [
            $transitionWithoutForm,
            $transitionWithFormNotContinue,
            $continueTransition
        ];

        $this->workflowManager->expects($this->once())
            ->method('getTransitionsByWorkflowItem')
            ->with($workflowItem)
            ->will($this->returnValue($transitions));

        $this->assertSame($continueTransition, $this->dataProvider->getData($context));
    }
}
