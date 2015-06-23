<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use OroB2B\Bundle\CustomerBundle\Form\Handler\AccountUserRoleHandler;

class AccountUserRoleHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var AccountUserRoleHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var AccountUserRole
     */
    protected $entity;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new AccountUserRole();
        $this->handler = new AccountUserRoleHandler($this->form, $this->request, $this->manager);
    }

    public function testProcessValidData()
    {
        $appendedUser = new AccountUser();

        $removedUser = new AccountUser();
        $removedUser->addRole($this->entity);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $appendForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $appendForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([$appendedUser]));
        $this->form->expects($this->at(3))
            ->method('get')
            ->with('appendUsers')
            ->will($this->returnValue($appendForm));

        $removeForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $removeForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([$removedUser]));
        $this->form->expects($this->at(4))
            ->method('get')
            ->with('removeUsers')
            ->will($this->returnValue($removeForm));

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));

        $this->assertEquals([$this->entity], $appendedUser->getRoles());
        $this->assertEmpty($removedUser->getRoles());
    }

    public function testBadMethod()
    {
        $this->request->setMethod('GET');
        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testProcessInvalid()
    {
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $this->assertFalse($this->handler->process($this->entity));
    }
}
