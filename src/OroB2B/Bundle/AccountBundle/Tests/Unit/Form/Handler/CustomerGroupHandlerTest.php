<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountGroupHandler;

class CustomerGroupHandlerTest extends \PHPUnit_Framework_TestCase
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
     * @var AccountGroupHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var AccountGroup
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

        $this->entity  = new AccountGroup();
        $this->handler = new AccountGroupHandler($this->form, $this->request, $this->manager);
    }

    public function testProcessValidData()
    {
        $appendedCustomer = new Account();

        $removedCustomer = new Account();
        $removedCustomer->setGroup($this->entity);

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
            ->will($this->returnValue([$appendedCustomer]));
        $this->form->expects($this->at(3))
            ->method('get')
            ->with('appendCustomers')
            ->will($this->returnValue($appendForm));

        $removeForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $removeForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue([$removedCustomer]));
        $this->form->expects($this->at(4))
            ->method('get')
            ->with('removeCustomers')
            ->will($this->returnValue($removeForm));

        $this->manager->expects($this->at(0))
            ->method('persist')
            ->with($appendedCustomer);

        $this->manager->expects($this->at(1))
            ->method('persist')
            ->with($removedCustomer);

        $this->manager->expects($this->at(2))
            ->method('persist')
            ->with($this->entity);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));

        $this->assertEquals($this->entity, $appendedCustomer->getGroup());
        $this->assertNull($removedCustomer->getGroup());
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
