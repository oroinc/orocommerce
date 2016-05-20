<?php


namespace OroB2B\Bundle\SEOBundle\Tests\Unit\EventListener;


use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SEOBundle\EventListener\FormViewListener;
use Symfony\Component\Form\FormView;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var FormViewListener
     */
    protected $listener;

    /** @var  Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator);
    }

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

        $this->listener = new FormViewListener($requestStack ,$this->translator, $this->doctrineHelper);
    }

    public function testOnProductView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $product = new Product();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getEnvironment($product);
        $event = $this->getEvent($env, false);

        $this->listener->onProductView($event);
    }

    public function testOnProductEdit()
    {
        $env = $this->getEnvironment();
        $event = $this->getEvent($env);

        $this->listener->onProductEdit($event);
    }

    public function testOnProductViewInvalidId()
    {
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

        $this->listener->onProductView($event);
    }

    public function testOnLandingPageView()
    {
        $this->request
            ->expects($this->any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $page = new Page();
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($page);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getEnvironment($page);
        $event = $this->getEvent($env, false);

        $this->listener->onProductView($event);
    }

    public function testOnLandingPageEdit()
    {
        $env = $this->getEnvironment();
        $event = $this->getEvent($env);

        $this->listener->onLandingPageEdit($event);
    }

    public function testOnCategoryEdit()
    {
        $env = $this->getEnvironment();
        $event = $this->getEvent($env);

        $this->listener->onCategoryEdit($event);
    }

    protected function getEnvironment($entityObject = null)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        if (null === $entityObject) {
            $env->expects($this->once())
                ->method('render')
                ->with('OroB2BSEOBundle:SEO:update.html.twig', ['form' => new FormView()])
                ->willReturn('');
        } else {
            $env->expects($this->once())
                ->method('render')
                ->with('OroB2BSEOBundle:SEO:view.html.twig', ['entity' => $entityObject])
                ->willReturn('');
        }

        return $env;
    }

    protected function getEvent($env, $forEdit = true)
    {
        $event = $this->getBeforeListRenderEvent();

        $event->expects($this->once())
            ->method('getEnvironment')
            ->willReturn($env);

        if ($forEdit) {
            $event->expects($this->once())
                ->method('getFormView')
                ->willReturn(new FormView());
        }

        return $event;
    }
}
