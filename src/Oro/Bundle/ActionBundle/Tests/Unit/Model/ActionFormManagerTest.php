<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Bundle\ActionBundle\Model\ActionFormManager;

class ActionFormManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Action */
    protected $action;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface */
    protected $formFactory;

    /** @var ActionFormManager */
    protected $manager;

    protected function setUp()
    {
        $this->action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->manager = new ActionFormManager($this->formFactory);
    }

    protected function tearDown()
    {
        unset($this->manager, $this->formFactory, $this->action);
    }

    public function testGetActionForm()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinition $definition */
        $definition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $definition->expects($this->once())
            ->method('getFormType')
            ->willReturn('form_type');

        $this->action->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $this->action->expects($this->once())
            ->method('getFormOptions')
            ->willReturn(['some_option' => 'option_value']);

        $context = new ActionContext(['data' => ['param']]);
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(
                'form_type',
                $context,
                [
                    'some_option' => 'option_value',
                    'action_context' => $context,
                    'action' => $this->action
                ]
            )
            ->willReturn($form);

        $this->assertSame($form, $this->manager->getActionForm($this->action, $context));
    }
}
