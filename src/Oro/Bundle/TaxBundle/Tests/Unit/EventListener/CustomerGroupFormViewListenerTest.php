<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormView;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\EventListener\CustomerGroupFormViewListener;

class CustomerGroupFormViewListenerTest extends AbstractFormViewListenerTest
{
    /**
     * @var CustomerGroupFormViewListener
     */
    protected $listener;

    /**
     * @return CustomerGroupFormViewListener
     */
    public function getListener()
    {
        return new CustomerGroupFormViewListener(
            $this->doctrineHelper,
            $this->requestStack,
            'Oro\Bundle\TaxBundle\Entity\CustomerTaxCode',
            'Oro\Bundle\CustomerBundle\Entity\CustomerGroup'
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
            ->with('OroTaxBundle:CustomerGroup:tax_code_update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        $this->getListener()->onEdit($event);
    }

    public function testOnCustomerGroupView()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByCustomerGroup'])
            ->getMock();
        $taxCode = new CustomerTaxCode();
        $repository
            ->expects($this->once())
            ->method('findOneByCustomerGroup')
            ->willReturn($taxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(new CustomerGroup());
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
            ->with('OroTaxBundle:CustomerGroup:tax_code_view.html.twig', ['entity' => $taxCode])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }
}
