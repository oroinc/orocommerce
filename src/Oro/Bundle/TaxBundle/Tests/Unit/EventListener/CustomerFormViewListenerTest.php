<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TaxBundle\EventListener\CustomerFormViewListener;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class CustomerFormViewListenerTest extends AbstractFormViewListenerTest
{
    /**
     * @var CustomerFormViewListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function getListener()
    {
        return new CustomerFormViewListener(
            $this->doctrineHelper,
            $this->requestStack,
            'Oro\Bundle\TaxBundle\Entity\CustomerTaxCode',
            'Oro\Bundle\CustomerBundle\Entity\Customer'
        );
    }

    public function testOnEdit()
    {
        $event = $this->getBeforeListRenderEvent();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->once())
            ->method('render')
            ->with('OroTaxBundle:Customer:tax_code_update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        $this->getListener()->onEdit($event);
    }

    public function testOnCustomerView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByCustomer'])
            ->getMock();
        $taxCode = new CustomerTaxCode();
        $repository
            ->expects($this->once())
            ->method('findOneByCustomer')
            ->willReturn($taxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(new Customer());
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with(
                'OroTaxBundle:Customer:tax_code_view.html.twig',
                [
                    'entity' => $taxCode,
                    'groupCustomerTaxCode' => null,
                ]
            )
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }

    public function testOnCustomerViewWithCustomerGroupTaxCode()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneByCustomer')
            ->willReturn(null);

        $customerTaxCode = new CustomerTaxCode();

        $repository
            ->expects($this->once())
            ->method('findOneByCustomerGroup')
            ->willReturn($customerTaxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with(
                'OroTaxBundle:Customer:tax_code_view.html.twig',
                [
                    'entity' => null,
                    'groupCustomerTaxCode' => $customerTaxCode,
                ]
            )
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }

    public function testOnCustomerViewAllEmpty()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this
            ->getMockBuilder('Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneByCustomer')
            ->willReturn(null);

        $repository
            ->expects($this->once())
            ->method('findOneByCustomerGroup')
            ->willReturn(null);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn((new Customer())->setGroup(new CustomerGroup()));

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with(
                'OroTaxBundle:Customer:tax_code_view.html.twig',
                [
                    'entity' => null,
                    'groupCustomerTaxCode' => null,
                ]
            )
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }
}
