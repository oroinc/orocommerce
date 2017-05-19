<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\PricingBundle\EventListener\FormViewListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var PriceAttributePricesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = $this->createMock(PriceAttributePricesProvider::class);
        return parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator);
    }

    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = $this->getListener($requestStack);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->createMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $listener->onProductView($event);
    }

    public function testOnProductView()
    {
        $productId = 1;
        $product = new Product();
        $templateHtml = 'template_html';

        $request = new Request(['id' => $productId]);
        $requestStack = $this->getRequestStack($request);

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(Product::class, $productId)
            ->willReturn($product);

        $priceList = new PriceAttributePriceList();

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $priceAttributePriceListRepository */
        $priceAttributePriceListRepository = $this->createMock('Doctrine\ORM\EntityRepository');

        $priceAttributePriceListRepository->expects($this->once())
            ->method('findAll')->willReturn([$priceList]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap([
                ['OroPricingBundle:PriceAttributePriceList', $priceAttributePriceListRepository]]);

        $this->provider->expects($this->once())->method('getPrices')->with($priceList, $product)
            ->willReturn(['Test' => ['item' => ['USD' => 100]]]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->createMock('\Twig_Environment');
        $environment->expects($this->at(0))
            ->method('render')
            ->with(
                'OroPricingBundle:Product:price_attribute_prices.html.twig',
                [
                    'product' => $product,
                    'priceList' => $priceList,
                    'priceAttributePrices' => ['Test' => ['item' => ['USD' => 100]]]
                ]
            )
            ->willReturn($templateHtml);

        $environment->expects($this->at(1))
            ->method('render')
            ->with(
                'OroPricingBundle:Product:prices_view.html.twig',
                [
                    'entity' => $product,
                ]
            )
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $expectedTitle = 'oro.pricing.pricelist.entity_plural_label.trans';
        $this->assertScrollDataPriceBlock($scrollData, $templateHtml, $expectedTitle);
    }

    public function testOnProductEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->createMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroPricingBundle:Product:prices_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var FormViewListener $listener */
        $listener = $this->getListener($requestStack);
        $listener->onProductEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $expectedTitle = 'oro.pricing.productprice.entity_plural_label.trans';
        $this->assertScrollDataPriceBlock($scrollData, $templateHtml, $expectedTitle);
    }

    /**
     * @param array $scrollData
     * @param string $html
     */
    protected function assertScrollDataPriceBlock(array $scrollData, $html, $expectedTitle)
    {
        $this->assertEquals(
            $expectedTitle,
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::TITLE]
        );
        $this->assertEquals(
            ['productPriceAttributesPrices' => $html],
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(\Twig_Environment $environment, FormView $formView = null)
    {
        $defaultData = [
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => [],
                        ]
                    ]
                ]
            ]
        ];

        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), new \stdClass(), $formView);
    }

    /**
     * @param string $class
     * @param int $id
     * @return object
     */
    protected function getEntity($class, $id)
    {
        $entity = new $class();
        $reflection = new \ReflectionProperty(get_class($entity), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);

        return $entity;
    }

    /**
     * @param RequestStack $requestStack
     * @return FormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new FormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->provider
        );
    }

    /**
     * @param $request
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected function getRequestStack($request)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        return $requestStack;
    }
}
