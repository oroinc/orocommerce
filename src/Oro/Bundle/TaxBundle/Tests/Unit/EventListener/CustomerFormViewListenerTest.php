<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\EventListener\CustomerFormViewListener;
use Symfony\Component\Form\FormView;

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

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Twig_Environment $env */
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

        $taxCode = new CustomerTaxCode();
        $customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['getTaxCode'])
            ->getMock();
        $customer->expects($this->once())->method('getTaxCode')->willReturn($taxCode);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customer);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Twig_Environment $env */
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

        $customerTaxCode = new CustomerTaxCode();

        $customerGroup = $this->getMockBuilder(CustomerGroup::class)
            ->setMethods(['getTaxCode'])
            ->getMock();
        $customerGroup->method('getTaxCode')->willReturn($customerTaxCode);
        $customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['getTaxCode', 'getGroup'])
            ->getMock();
        $customer->method('getGroup')->willReturn($customerGroup);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customer);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Twig_Environment $env */
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

        $customerGroup = $this->getMockBuilder(CustomerGroup::class)
            ->setMethods(['getTaxCode'])
            ->getMock();
        $customer = $this->getMockBuilder(Customer::class)
            ->setMethods(['getTaxCode', 'getGroup'])
            ->getMock();
        $customer->method('getGroup')->willReturn($customerGroup);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customer);

        /** @var \PHPUnit\Framework\MockObject\MockObject|\Twig_Environment $env */
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
