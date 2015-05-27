<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Form\Handler\PriceListHandler;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class PriceListHandlerTest extends \PHPUnit_Framework_TestCase
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
     * @var PriceListHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var PriceList
     */
    protected $entity;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = new Request();

        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new PriceList();
        $this->handler = new PriceListHandler($this->form, $this->request, $this->manager);
    }

    public function testProcessValidData()
    {
        $appendedCustomer = new Customer();
        $removedCustomer = (new Customer())->setPriceList($this->entity);

        $appendedCustomerGroup = new CustomerGroup();
        $removedCustomerGroup = (new CustomerGroup())->setPriceList($this->entity);

        $appendedWebsite = new Website();
        $removedWebsite = (new Website())->setPriceList($this->entity);

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        // Customers
        $this->form->expects($this->at(3))
            ->method('get')
            ->with('appendCustomers')
            ->willReturn($this->getFormForEntity($appendedCustomer));

        $this->form->expects($this->at(4))
            ->method('get')
            ->with('removeCustomers')
            ->willReturn($this->getFormForEntity($removedCustomer));

        // Customer Groups
        $this->form->expects($this->at(5))
            ->method('get')
            ->with('appendCustomerGroups')
            ->willReturn($this->getFormForEntity($appendedCustomerGroup));

        $this->form->expects($this->at(6))
            ->method('get')
            ->with('removeCustomerGroups')
            ->willReturn($this->getFormForEntity($removedCustomerGroup));

        //Websites
        $this->form->expects($this->at(7))
            ->method('get')
            ->with('appendWebsites')
            ->willReturn($this->getFormForEntity($appendedWebsite));

        $this->form->expects($this->at(8))
            ->method('get')
            ->with('removeWebsites')
            ->willReturn($this->getFormForEntity($removedWebsite));

        //Object Manager
        $this->manager->expects($this->at(0))
            ->method('persist')
            ->with($this->entity);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));

        $this->assertEquals($this->entity, $appendedCustomer->getPriceList());
        $this->assertEquals($this->entity, $appendedCustomerGroup->getPriceList());
        $this->assertEquals($this->entity, $appendedWebsite->getPriceList());
        $this->assertNull($removedCustomer->getPriceList());
        $this->assertNull($removedCustomerGroup->getPriceList());
        $this->assertNull($removedWebsite->getPriceList());
    }

    /**
     * @param object $entity
     * @return \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Form\Form
     */
    protected function getFormForEntity($entity)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->once())
            ->method('getData')
            ->willReturn([$entity]);

        return $form;
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
