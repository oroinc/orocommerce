<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUserManager;
use OroB2B\Bundle\CustomerBundle\Form\Handler\FrontendAccountUserHandler;

class FrontendAccountUserHandlerTest extends FormHandlerTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccountUserManager
     */
    protected $userManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity = $this->getMockBuilder('OroB2B\Bundle\CustomerBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userManager = $this->getMockBuilder('OroB2B\Bundle\CustomerBundle\Entity\AccountUserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new FrontendAccountUserHandler(
            $this->form,
            $this->request,
            $this->userManager
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * {@inheritdoc}
     * @dataProvider supportedMethods
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $this->form->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue($isValid));

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessValidData()
    {
        $this->entity->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(null));
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->userManager->expects($this->once())
            ->method('register')
            ->with($this->entity);

        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($this->entity);

        $this->assertTrue($this->handler->process($this->entity));
    }

    /**
     * {@inheritdoc}
     */
    public function testProcessValidDataExistingUser()
    {
        $this->entity->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(42));
        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->userManager->expects($this->never())
            ->method('register')
            ->with($this->entity);

        $this->userManager->expects($this->once())
            ->method('updateUser')
            ->with($this->entity);

        $this->assertTrue($this->handler->process($this->entity));
    }
}
