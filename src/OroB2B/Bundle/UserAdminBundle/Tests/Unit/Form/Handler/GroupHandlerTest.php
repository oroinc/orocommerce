<?php

namespace OroB2B\Bundle\UserAdminBundle\Tests\Unit\Form\Handler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

use OroB2B\Bundle\UserAdminBundle\Entity\Group;
use OroB2B\Bundle\UserAdminBundle\Entity\User;
use OroB2B\Bundle\UserAdminBundle\Form\Handler\GroupHandler;

class GroupHandlerTest extends FormHandlerTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entity = new Group('');
        $this->handler = new GroupHandler($this->form, $this->request, $this->manager);
    }

    /**
     * @dataProvider supportedMethods
     * @param string $method
     * @param boolean $isValid
     * @param boolean $isProcessed
     */
    public function testProcessSupportedRequest($method, $isValid, $isProcessed)
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn($isValid);

        if ($isValid) {
            $this->assertAppendRemoveUsers();
        }

        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->assertEquals($isProcessed, $this->handler->process($this->entity));
    }

    /**
     * {@inheritDoc}
     */
    public function testProcessValidData()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->assertAppendRemoveUsers();

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    protected function assertAppendRemoveUsers()
    {
        $appendUsers = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $appendUsers->expects($this->once())
            ->method('getData')
            ->willReturn([new User()]);

        $this->form->expects($this->at(3))
            ->method('get')
            ->with('appendUsers')
            ->willReturn($appendUsers);

        $removeUsers = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $removeUsers->expects($this->once())
            ->method('getData')
            ->willReturn([new User()]);

        $this->form->expects($this->at(4))
            ->method('get')
            ->with('removeUsers')
            ->willReturn($removeUsers);
    }
}
