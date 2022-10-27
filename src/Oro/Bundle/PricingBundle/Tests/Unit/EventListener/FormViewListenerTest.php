<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\EventListener\FormViewListener;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class FormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    private const TEMPLATE_HTML_PRODUCT_PRICE = 'template_html_product_price';
    private const TEMPLATE_HTML_PRODUCT_ATTRIBUTE_PRICE = 'template_html_product_attribute_price';

    private DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper;

    private AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker;

    private PriceAttributePricesProvider|\PHPUnit\Framework\MockObject\MockObject $priceAttributePricesProvider;

    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    private AclHelper|\PHPUnit\Framework\MockObject\MockObject $aclHelper;

    private Environment|\PHPUnit\Framework\MockObject\MockObject $env;

    private FormViewListener $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceAttributePricesProvider = $this->createMock(PriceAttributePricesProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->env = $this->createMock(Environment::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . '.trans';
            });

        $this->listener = new FormViewListener(
            $translator,
            $this->doctrineHelper,
            $this->priceAttributePricesProvider,
            $this->authorizationChecker,
            $this->aclHelper
        );
    }

    public function testOnProductEditFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $this->env->expects(self::never())
            ->method('render');

        $event = $this->createEvent(new Product());
        $this->listener->onProductEdit($event);
    }

    public function testOnProductViewFeatureDisabled(): void
    {
        $priceAttributePriceListRepository = $this->createMock(PriceAttributePriceListRepository::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $priceAttributePriceListRepository->expects(self::once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->willReturn($priceAttributePriceListRepository);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        $event = $this->createEvent(new Product());
        $this->listener->onProductView($event);
    }

    public function testOnProductViewException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $event = $this->createMock(BeforeListRenderEvent::class);
        $event->expects(self::once())
            ->method('getEntity')
            ->willReturn(new ProductPrice());

        $this->listener->onProductView($event);
    }

    /**
     * @dataProvider getTestOnProductViewDataProvider
     */
    public function testOnProductView(array $priceLists, array $expectedScrollData): void
    {
        $product = new Product();

        $expectedPricesViewRenderCalls = [
            [
                '@OroPricing/Product/prices_view.html.twig',
                ['entity' => $product],
                self::TEMPLATE_HTML_PRODUCT_PRICE
            ]
        ];
        $this->expectsProductAttributesPriceLists($priceLists);
        $this->expectsPriceAttributeViewRender($product, $priceLists, $expectedPricesViewRenderCalls);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->willReturn(true);

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $event = $this->createEvent($product);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        self::assertEquals($expectedScrollData, $scrollData);
    }

    public function getTestOnProductViewDataProvider(): array
    {
        return [
            'one block for one attribute' => [
                'priceLists' => [
                    new PriceAttributePriceList(),
                ],
                'expectedScrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'prices' => [
                            ScrollData::TITLE => 'oro.pricing.pricelist.entity_plural_label.trans',
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => ['prices' => self::TEMPLATE_HTML_PRODUCT_PRICE],
                                ],
                            ],
                            ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                            ScrollData::PRIORITY => FormViewListener::PRICING_BLOCK_PRIORITY,
                        ],
                        'price_attributes' => [
                            ScrollData::TITLE => 'oro.pricing.priceattributepricelist.entity_plural_label.trans',
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'productPriceAttributesPrices' => self::TEMPLATE_HTML_PRODUCT_ATTRIBUTE_PRICE
                                    ],
                                ],
                            ],
                            ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                            ScrollData::PRIORITY => FormViewListener::PRICE_ATTRIBUTES_BLOCK_PRIORITY,
                        ],
                        [
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'two blocks for more than one attribute' => [
                'priceLists' => [
                    new PriceAttributePriceList(),
                    new PriceAttributePriceList(),
                    new PriceAttributePriceList(),
                ],
                'expectedScrollData' => [
                    ScrollData::DATA_BLOCKS => [
                        'prices' => [
                            ScrollData::TITLE => 'oro.pricing.pricelist.entity_plural_label.trans',
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => ['prices' => self::TEMPLATE_HTML_PRODUCT_PRICE],
                                ],
                            ],
                            ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                            ScrollData::PRIORITY => FormViewListener::PRICING_BLOCK_PRIORITY,
                        ],
                        'price_attributes' => [
                            ScrollData::TITLE => 'oro.pricing.priceattributepricelist.entity_plural_label.trans',
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [
                                        'productPriceAttributesPrices' => self::TEMPLATE_HTML_PRODUCT_ATTRIBUTE_PRICE .
                                            self::TEMPLATE_HTML_PRODUCT_ATTRIBUTE_PRICE
                                    ],
                                ],
                                [
                                    ScrollData::DATA => [
                                        'productPriceAttributesPrices' => self::TEMPLATE_HTML_PRODUCT_ATTRIBUTE_PRICE
                                    ],
                                ],
                            ],
                            ScrollData::USE_SUB_BLOCK_DIVIDER => true,
                            ScrollData::PRIORITY => FormViewListener::PRICE_ATTRIBUTES_BLOCK_PRIORITY,
                        ],
                        [
                            ScrollData::SUB_BLOCKS => [
                                [
                                    ScrollData::DATA => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testOnProductViewForbiddenToViewPrice(): void
    {
        $product = new Product();

        $priceLists = [new PriceAttributePriceList()];
        $this->expectsProductAttributesPriceLists($priceLists);
        $this->expectsPriceAttributeViewRender($product, $priceLists);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->willReturn(false);

        $event = $this->createEvent($product);
        $this->listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        self::assertTrue(empty($scrollData[ScrollData::DATA_BLOCKS]['prices']));

        self::assertEquals(
            'oro.pricing.priceattributepricelist.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::TITLE]
        );

        self::assertEquals(
            ['productPriceAttributesPrices' => self::TEMPLATE_HTML_PRODUCT_ATTRIBUTE_PRICE],
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductEdit(): void
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        $this->env->expects(self::once())
            ->method('render')
            ->with('@OroPricing/Product/prices_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent(new Product(), $formView);

        $this->listener->onProductEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $expectedTitle = 'oro.pricing.productprice.entity_plural_label.trans';

        self::assertEquals(
            $expectedTitle,
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::TITLE]
        );

        self::assertEquals(
            ['productPriceAttributesPrices' => $templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    private function createEvent(Product $entity, FormView $formView = null): BeforeListRenderEvent
    {
        $defaultData = [
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => []
                        ]
                    ]
                ]
            ]
        ];

        return new BeforeListRenderEvent($this->env, new ScrollData($defaultData), $entity, $formView);
    }

    private function expectsProductAttributesPriceLists(array $priceLists): void
    {
        $priceAttributePriceListRepository = $this->createMock(PriceAttributePriceListRepository::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($priceLists);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $priceAttributePriceListRepository->expects(self::once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->willReturnMap([
                [PriceAttributePriceList::class, $priceAttributePriceListRepository],
            ]);
    }

    private function expectsPriceAttributeViewRender(
        Product $product,
        array $priceLists,
        array $additionalRenderCalls = []
    ): void {
        $expectedGetPricesWithUnitAndCurrenciesCalls = $expectedViewRenderCalls = [];
        foreach ($priceLists as $priceList) {
            $expectedGetPricesWithUnitAndCurrenciesCalls[] = [
                $priceList,
                $product,
                ['Test' => ['item' => ['USD' => 100]]]
            ];
            $expectedViewRenderCalls[] = [
                '@OroPricing/Product/price_attribute_prices_view.html.twig',
                [
                    'product' => $product,
                    'priceList' => $priceList,
                    'priceAttributePrices' => ['Test' => ['item' => ['USD' => 100]]]
                ],
                self::TEMPLATE_HTML_PRODUCT_ATTRIBUTE_PRICE
            ];
        }

        $this->priceAttributePricesProvider->expects(self::exactly(count($expectedGetPricesWithUnitAndCurrenciesCalls)))
            ->method('getPricesWithUnitAndCurrencies')
            ->willReturnMap($expectedGetPricesWithUnitAndCurrenciesCalls);

        $expectedViewRenderCalls = array_merge($expectedViewRenderCalls, $additionalRenderCalls);

        $this->env->expects(self::exactly(count($expectedViewRenderCalls)))
            ->method('render')
            ->willReturnMap($expectedViewRenderCalls);
    }
}
