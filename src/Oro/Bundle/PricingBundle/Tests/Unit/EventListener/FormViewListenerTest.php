<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PricingBundle\EventListener\FormViewListener;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var PriceAttributePricesProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceAttributePricesProvider;

    protected function setUp()
    {
        $this->priceAttributePricesProvider = $this->createMock(PriceAttributePricesProvider::class);

        return parent::setUp();
    }

    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getRequestStack(null);

        $listener = $this->getListener($requestStack);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->createMock(\Twig_Environment::class);
        $event = $this->createEvent($environment);
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
        $priceAttributePriceListRepository = $this->createMock(EntityRepository::class);

        $priceAttributePriceListRepository->expects($this->once())
            ->method('findAll')->willReturn([$priceList]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap([
                ['OroPricingBundle:PriceAttributePriceList', $priceAttributePriceListRepository],
            ]);

        $this->priceAttributePricesProvider->expects($this->once())->method('getPricesWithUnitAndCurrencies')
            ->with($priceList, $product)
            ->willReturn(['Test' => ['item' => ['USD' => 100]]]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->createMock(\Twig_Environment::class);
        $environment->expects($this->at(0))
            ->method('render')
            ->with(
                'OroPricingBundle:Product:price_attribute_prices_view.html.twig',
                [
                    'product' => $product,
                    'priceList' => $priceList,
                    'priceAttributePrices' => ['Test' => ['item' => ['USD' => 100]]],
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
        $environment = $this->createMock(\Twig_Environment::class);
        $environment->expects($this->once())
            ->method('render')
            ->with('OroPricingBundle:Product:prices_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);

        $requestStack = $this->getRequestStack(null);

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
     * @param string $expectedTitle
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
                        ],
                    ],
                ],
            ],
        ];

        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), new \stdClass(), $formView);
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
            $this->priceAttributePricesProvider
        );
    }

    /**
     * @param Request|null $request
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected function getRequestStack(Request $request = null)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($request);

        return $requestStack;
    }
}
