<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\Form\FormFactory;

use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserPasswordRequestType;
use OroB2B\Bundle\AccountBundle\Layout\DataProvider\FrontendAccountUserForgotPasswordFormProvider;

class FrontendAccountUserForgotPasswordFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactory;

    /**
     * @var FrontendAccountUserForgotPasswordFormProvider
     */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this
            ->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->provider = new FrontendAccountUserForgotPasswordFormProvider($this->formFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->provider, $this->formFactory);
    }

    public function testGetData()
    {
        /** @var ContextInterface $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $expectedForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(AccountUserPasswordRequestType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing data in locale cache
        $data = $this->provider->getData($context);
        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $data);

        // Get form with existing data in locale cache
        $data = $this->provider->getData($context);
        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $data);
    }

    public function testGetForm()
    {
        $expectedForm = $this->getMock('Symfony\Component\Form\Test\FormInterface');

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(AccountUserPasswordRequestType::NAME)
            ->willReturn($expectedForm);

        // Get form without existing form in locale cache
        $form = $this->provider->getForm();
        $this->assertEquals($expectedForm, $form);

        // Get form with existing form in locale cache
        $form = $this->provider->getForm();
        $this->assertEquals($expectedForm, $form);
    }
}
