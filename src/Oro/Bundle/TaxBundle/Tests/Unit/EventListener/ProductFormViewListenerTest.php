<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\EventListener\ProductFormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductFormViewListenerTest extends AbstractFormViewListenerTest
{
    /**
     * @var ProductFormViewListener
     */
    protected $listener;

    /**
     * @return ProductFormViewListener
     */
    public function getListener()
    {
        return new ProductFormViewListener(
            $this->doctrineHelper,
            $this->requestStack,
            'Oro\Bundle\TaxBundle\Entity\ProductTaxCode',
            'Oro\Bundle\ProductBundle\Entity\Product'
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
            ->with('OroTaxBundle:Product:tax_code_update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        $this->getListener()->onEdit($event);
    }

    public function testOnProductView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByProduct'])
            ->getMock();
        $taxCode = new ProductTaxCode();
        $repository
            ->expects($this->once())
            ->method('findOneByProduct')
            ->willReturn($taxCode);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(new Product());
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
            ->with('OroTaxBundle:Product:tax_code_view.html.twig', ['entity' => $taxCode])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->getListener()->onView($event);
    }
}
