<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\TaxBundle\EventListener\AccountFormViewListener;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountFormViewListenerTest extends AbstractFormViewListenerTest
{
    /**
     * @var AccountFormViewListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function getListener()
    {
        return new AccountFormViewListener(
            $this->doctrineHelper,
            $this->requestStack,
            'OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode',
            'OroB2B\Bundle\AccountBundle\Entity\Account'
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
            ->with('OroB2BTaxBundle:Account:tax_code_update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        $this->getListener()->onEdit($event);
    }

    public function testOnAccountView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByAccount'])
            ->getMock();
        $taxCode = new AccountTaxCode();
        $repository
            ->expects($this->once())
            ->method('findOneByAccount')
            ->willReturn($taxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(new Account());
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
                'OroB2BTaxBundle:Account:tax_code_view.html.twig',
                [
                    'entity' => $taxCode,
                    'groupAccountTaxCode' => null,
                ]
            )
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }

    public function testOnAccountViewWithAccountGroupTaxCode()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneByAccount')
            ->willReturn(null);

        $accountTaxCode = new AccountTaxCode();

        $repository
            ->expects($this->once())
            ->method('findOneByAccountGroup')
            ->willReturn($accountTaxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn((new Account())->setGroup(new AccountGroup()));

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BTaxBundle:Account:tax_code_view.html.twig',
                [
                    'entity' => null,
                    'groupAccountTaxCode' => $accountTaxCode,
                ]
            )
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }

    public function testOnAccountViewAllEmpty()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('findOneByAccount')
            ->willReturn(null);

        $repository
            ->expects($this->once())
            ->method('findOneByAccountGroup')
            ->willReturn(null);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn((new Account())->setGroup(new AccountGroup()));

        $this->doctrineHelper->expects($this->once())->method('getEntityRepository')->willReturn($repository);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $env->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BTaxBundle:Account:tax_code_view.html.twig',
                [
                    'entity' => null,
                    'groupAccountTaxCode' => null,
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
