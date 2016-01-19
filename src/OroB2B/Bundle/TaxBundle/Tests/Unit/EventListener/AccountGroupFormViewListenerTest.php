<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormView;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\EventListener\AccountGroupFormViewListener;

class AccountGroupFormViewListenerTest extends AbstractFormViewListenerTest
{
    /**
     * @var AccountGroupFormViewListener
     */
    protected $listener;

    /**
     * @return AccountGroupFormViewListener
     */
    public function getListener()
    {
        return new AccountGroupFormViewListener(
            $this->doctrineHelper,
            $this->requestStack,
            'OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode',
            'OroB2B\Bundle\AccountBundle\Entity\AccountGroup'
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
            ->with('OroB2BTaxBundle:AccountGroup:tax_code_update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        $this->getListener()->onEdit($event);
    }

    public function testOnAccountGroupView()
    {
        $this->request->expects($this->any())->method('get')->with('id')->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByAccountGroup'])
            ->getMock();
        $taxCode = new AccountTaxCode();
        $repository
            ->expects($this->once())
            ->method('findOneByAccountGroup')
            ->willReturn($taxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(new AccountGroup());
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
            ->with('OroB2BTaxBundle:AccountGroup:tax_code_view.html.twig', ['entity' => $taxCode])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }
}
