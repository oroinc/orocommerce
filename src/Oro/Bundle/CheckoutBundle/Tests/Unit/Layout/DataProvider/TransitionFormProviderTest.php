<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;

class TransitionFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\stdClass
     */
    protected $transitionProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var TransitionFormProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->transitionProvider = $this->getMock(\stdClass::class, ['getContinueTransition']);
        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->provider = new TransitionFormProvider($this->formFactory);
        $this->provider->setTransitionProvider($this->transitionProvider);
    }

    public function testGetTransitionForm()
    {
        $workflowData = new WorkflowData();
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($workflowData);

        $continueTransition = new Transition();
        $continueTransition->setName('transition3');
        $continueTransition->setFormOptions(['attribute_fields' => ['test' => null]]);
        $continueTransition->setFormType('transition_type');

        $transitionData = new TransitionData($continueTransition, true, new ArrayCollection());
        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->with($workflowItem)
            ->will($this->returnValue($transitionData));

        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('createView')
            ->will($this->returnValue($formView));
        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                'transition_type',
                $workflowData,
                [
                    'workflow_item' => $workflowItem,
                    'transition_name' => 'transition3',
                    'attribute_fields' => ['test' => null],
                    'disabled' => false
                ]
            )
            ->will($this->returnValue($form));

        $this->assertSame($formView, $this->provider->getTransitionFormView($workflowItem));
    }
}
