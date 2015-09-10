<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormView;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\EventListener\FormViewListener;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var FormViewListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper);
    }

    public function testOnProductEdit()
    {
        $event = $this->getBeforeListRenderEvent();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $env->expects($this->once())
            ->method('render')
            ->with('OroB2BCatalogBundle:Product:category_update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        $this->listener->setRequest($this->getRequest());
        $this->listener->onProductEdit($event);
    }

    public function testOnProductView()
    {
        $request = $this->getRequest();
        $request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->listener->setRequest($request);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByProduct'])
            ->getMock();
        $repository
            ->expects($this->once())
            ->method('findOneByProduct')
            ->willReturn(new Category());

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
            ->with('OroB2BCatalogBundle:Product:category_view.html.twig', ['entity' => new Category()])
            ->willReturn('');

        $event = $this->getBeforeListRenderEvent();
        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $this->listener->onProductView($event);
    }

    public function testOnProductViewInvalidId()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityReference');

        $this->listener->onProductView($event);

        $request = $this->getRequest();
        $request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('string');

        $this->listener->setRequest($request);
        $this->listener->onProductView($event);
    }

    public function testOnProductViewEmptyProduct()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|BeforeListRenderEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeListRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn(null);

        $request = $this->getRequest();
        $request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepository')
            ->willReturn(null);

        $this->listener->setRequest($request);
        $this->listener->onProductView($event);
    }
}
