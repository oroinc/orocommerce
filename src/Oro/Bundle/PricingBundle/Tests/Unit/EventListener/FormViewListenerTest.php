<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\FormViewListener;
use Oro\Bundle\PricingBundle\Provider\PriceAttributePricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class FormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    protected $env;

    /** @var FormViewListener */
    protected $listener;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var PriceAttributePricesProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $priceAttributePricesProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $featureChecker;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id) {
                    return $id . '.trans';
                }
            );

        $this->env = $this->createMock(Environment::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->priceAttributePricesProvider = $this->createMock(PriceAttributePricesProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->listener = new FormViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->priceAttributePricesProvider,
            $this->authorizationChecker,
            $this->aclHelper
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->listener,
            $this->authorizationChecker,
            $this->env,
            $this->priceAttributePricesProvider,
            $this->featureChecker
        );

        parent::tearDown();
    }

    public function testOnProductEditFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $this->env->expects($this->never())->method('render');

        $event = $this->createEvent($this->env, new Product());
        $this->listener->onProductEdit($event);
    }

    public function testOnProductViewFeatureDisabled()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|PriceAttributeProductPriceRepository */
        $priceAttributePriceListRepository = $this->createMock(PriceAttributePriceListRepository::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $priceAttributePriceListRepository->expects($this->once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($priceAttributePriceListRepository);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $this->authorizationChecker->expects($this->never())->method('isGranted');

        $event = $this->createEvent($this->env, new Product());
        $this->listener->onProductView($event);
    }

    public function testOnProductViewException()
    {
        $this->expectException(\Oro\Component\Exception\UnexpectedTypeException::class);
        /** @var BeforeListRenderEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(BeforeListRenderEvent::class);
        $event->expects($this->once())
            ->method('getEntity')
            ->willReturn(new ProductPrice());

        $this->listener->onProductView($event);
    }

    public function testOnProductView()
    {
        $product = new Product();
        $templateHtmlProductAttributePrice = 'template_html_product_attribute_price';
        $templateHtmlProductPrice = 'template_html_product_price';

        $this->assertPriceAttributeViewRendered($product, $templateHtmlProductAttributePrice);

        $environment = $this->getEnvironment();
        $environment->expects($this->at(1))
            ->method('render')
            ->with(
                'OroPricingBundle:Product:prices_view.html.twig',
                [
                    'entity' => $product,
                ]
            )
            ->willReturn($templateHtmlProductPrice);

        $this->authorizationChecker
            ->expects($this->exactly(1))
            ->method('isGranted')
            ->willReturn(true);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $event = $this->createEvent($environment, $product);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            'oro.pricing.pricelist.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::TITLE]
        );

        $this->assertEquals(
            ['prices' => $templateHtmlProductPrice],
            $scrollData[ScrollData::DATA_BLOCKS]['prices'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );

        $this->assertEquals(
            'oro.pricing.priceattributepricelist.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::TITLE]
        );

        $this->assertEquals(
            ['productPriceAttributesPrices' => $templateHtmlProductAttributePrice],
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductViewForbiddenToViewPrice()
    {
        $product = new Product();
        $templateHtmlProductAttributePrice = 'template_html_product_attribute_price';

        $this->assertPriceAttributeViewRendered($product, $templateHtmlProductAttributePrice);

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $event = $this->createEvent($this->getEnvironment(), $product);
        $this->listener->onProductView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertTrue(empty($scrollData[ScrollData::DATA_BLOCKS]['prices']));

        $this->assertEquals(
            'oro.pricing.priceattributepricelist.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::TITLE]
        );

        $this->assertEquals(
            ['productPriceAttributesPrices' => $templateHtmlProductAttributePrice],
            $scrollData[ScrollData::DATA_BLOCKS]['price_attributes'][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnProductEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        /** @var \PHPUnit\Framework\MockObject\MockObject|Environment $environment */
        $environment = $this->createMock(Environment::class);
        $environment->expects($this->once())
            ->method('render')
            ->with('OroPricingBundle:Product:prices_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $entity = new Product();
        $event = $this->createEvent($environment, $entity, $formView);

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
     * @param Environment $environment
     * @param object $entity
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(Environment $environment, $entity, FormView $formView = null)
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

    private function assertPriceAttributeViewRendered($product, $templateHtmlProductAttributePrice)
    {
        $priceList = new PriceAttributePriceList();

        /** @var \PHPUnit\Framework\MockObject\MockObject|PriceAttributePriceListRepository */
        $priceAttributePriceListRepository = $this->createMock(PriceAttributePriceListRepository::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$priceList]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $priceAttributePriceListRepository->expects($this->once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap([
                [PriceAttributePriceList::class, $priceAttributePriceListRepository],
            ]);

        $this->priceAttributePricesProvider->expects($this->once())->method('getPricesWithUnitAndCurrencies')
            ->with($priceList, $product)
            ->willReturn(['Test' => ['item' => ['USD' => 100]]]);

        /** @var \PHPUnit\Framework\MockObject\MockObject|Environment $environment */
        $environment = $this->getEnvironment();
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
            ->willReturn($templateHtmlProductAttributePrice);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Environment
     */
    private function getEnvironment()
    {
        if (null === $this->env) {
            $this->env = $this->createMock(Environment::class);
        }

        return $this->env;
    }
}
