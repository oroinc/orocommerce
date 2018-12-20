<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TransitionFormProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\stdClass|TransitionProvider
     */
    protected $transitionProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var TransitionFormProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->transitionProvider = $this->createMock(TransitionProvider::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->createMock(UrlGeneratorInterface::class);

        $this->provider = new TransitionFormProvider($this->formFactory, $router);
        $this->provider->setTransitionProvider($this->transitionProvider);
    }

    public function testGetTransitionForm()
    {
        $workflowData = new WorkflowData();
        $workflowItem = new WorkflowItem();
        $workflowItem->setData($workflowData);
        $optionsResolver = $this->createMock(TransitionOptionsResolver::class);

        $continueTransition = new Transition($optionsResolver);
        $continueTransition->setName('transition3');
        $continueTransition->setFormOptions(['attribute_fields' => ['test' => null]]);
        $continueTransition->setFormType('transition_type');

        $transitionData = new TransitionData($continueTransition, true, new ArrayCollection());
        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->with($workflowItem)
            ->will($this->returnValue($transitionData));

        $formView = $this->createMock('Symfony\Component\Form\FormView');
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
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
                    'allow_extra_fields' => true,
                ]
            )
            ->will($this->returnValue($form));

        $this->assertSame($formView, $this->provider->getTransitionFormView($workflowItem));
    }
}
