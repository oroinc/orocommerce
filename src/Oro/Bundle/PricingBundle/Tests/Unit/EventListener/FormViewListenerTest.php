<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository as PriceAttrPriceListRep;
use Oro\Bundle\PricingBundle\EventListener\FormViewListener;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class FormViewListenerTest extends FormViewListenerTestCase
{
    /** @var FormViewListener */
    private $listener;

    /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $authorizationChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment */
    private $environment;

    /** @var PriceAttributePricesProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceAttributePricesProvider;

    protected function setUp()
    {
        $this->priceAttributePricesProvider = $this->createMock(PriceAttributePricesProvider::class);

        parent::setUp();

        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->listener = new FormViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->priceAttributePricesProvider
        );

        $this->environment = $this->createMock(\Twig_Environment::class);

        $this->listener->setAuthorizationChecker($this->authorizationChecker);
    }

    public function testOnProductView()
    {
        $product = new Product();
        $templateHtmlProductAttributePrice = 'template_html_product_attribute_price';
        $templateHtmlProductPrice = 'template_html_product_price';

        $this->assertBlocksViewRendered($product, $templateHtmlProductAttributePrice, $templateHtmlProductPrice);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', 'entity:Oro\Bundle\PricingBundle\Entity\ProductPrice')
            ->willReturn(true);

        $event = $this->createEvent($this->environment, $product);
        $this->listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            'oro.pricing.pricelist.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::TITLE]
        );

        $this->assertEquals(
            ['productPriceAttributesPrices' => $templateHtmlProductPrice],
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );

        $this->assertEquals(
            'oro.pricing.priceattributepricelist.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::TITLE]
        );

        $this->assertEquals(
            $templateHtmlProductAttributePrice,
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][0]
        );
    }

    public function testOnProductViewForbiddenToViewPrice()
    {
        $product = new Product();
        $templateHtmlProductAttributePrice = 'template_html_product_attribute_price';
        $templateHtmlProductPrice = 'template_html_product_price';

        $this->assertBlocksViewRendered($product, $templateHtmlProductAttributePrice, $templateHtmlProductPrice);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('VIEW', 'entity:Oro\Bundle\PricingBundle\Entity\ProductPrice')
            ->willReturn(false);

        $event = $this->createEvent($this->environment, $product);
        $this->listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            'oro.pricing.pricelist.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::TITLE]
        );

        $this->assertEquals(
            ['productPriceAttributesPrices' => ''],
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );

        $this->assertEquals(
            'oro.pricing.priceattributepricelist.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::TITLE]
        );

        $this->assertEquals(
            $templateHtmlProductAttributePrice,
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA][0]
        );
    }

    public function testOnProductEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        $this->environment
            ->expects($this->once())
            ->method('render')
            ->with('OroPricingBundle:Product:prices_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $entity = new Product();
        $event = $this->createEvent($this->environment, $entity, $formView);

        $this->listener->onProductEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $expectedTitle = 'oro.pricing.productprice.entity_plural_label.trans';

        $this->assertEquals(
            $expectedTitle,
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::TITLE]
        );

        $this->assertEquals(
            ['productPriceAttributesPrices' => $templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param object $entity
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(\Twig_Environment $environment, $entity, FormView $formView = null)
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

        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), $entity, $formView);
    }

    /**
     * @param Product $product
     * @param string $templateHtmlProductAttributePrice
     * @param string $templateHtmlPrice
     */
    private function assertBlocksViewRendered(
        Product $product,
        $templateHtmlProductAttributePrice,
        $templateHtmlPrice
    ) {
        $priceList = new PriceAttributePriceList();

        /** @var \PHPUnit_Framework_MockObject_MockObject|PriceAttrPriceListRep $priceAttributePriceListRepository */
        $priceAttributePriceListRepository = $this->createMock(PriceAttrPriceListRep::class);

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

        $this->environment->expects($this->atLeastOnce())
            ->method('render')
            ->willReturnMap([
                [
                    'OroPricingBundle:Product:price_attribute_prices_view.html.twig',
                    [
                        'product' => $product,
                        'priceList' => $priceList,
                        'priceAttributePrices' => ['Test' => ['item' => ['USD' => 100]]],
                    ],
                    $templateHtmlProductAttributePrice
                ],
                [
                    'OroPricingBundle:Product:prices_view.html.twig',
                    [
                        'entity' => $product,
                    ],
                    $templateHtmlPrice
                ]
            ]);
    }
}
