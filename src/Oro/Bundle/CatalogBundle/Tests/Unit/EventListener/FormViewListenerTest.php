<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Doctrine\ORM\EntityRepository;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\FormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var FormViewListener
     */
    protected $listener;

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->request = $this->getRequest();
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);
        $this->listener = new FormViewListener($this->translator, $this->doctrineHelper, $requestStack);
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
            ->with('OroCatalogBundle:Product:category_update.html.twig', ['form' => new FormView()])
            ->willReturn('');

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        $event->expects($this->once())
            ->method('getFormView')
            ->willReturn(new FormView());

        $this->listener->onProductEdit($event);
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
        $category = new Category();
        $repository
            ->expects($this->once())
            ->method('findOneByProduct')
            ->willReturn($category);

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
            ->with('OroCatalogBundle:Product:category_view.html.twig', ['entity' => $category])
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

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn('string');

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

        $this->request
            ->expects($this->once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $this->doctrineHelper
            ->expects($this->never())
            ->method('getEntityRepository')
            ->willReturn(null);

        $this->listener->onProductView($event);
    }
}
