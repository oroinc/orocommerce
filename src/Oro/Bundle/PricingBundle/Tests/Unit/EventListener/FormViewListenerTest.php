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
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var PriceAttributePricesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $priceAttributePricesProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $env;

    /** @var FormViewListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->priceAttributePricesProvider = $this->createMock(PriceAttributePricesProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->env = $this->createMock(Environment::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
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

    public function testOnProductEditFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $this->env->expects($this->never())
            ->method('render');

        $event = $this->createEvent(new Product());
        $this->listener->onProductEdit($event);
    }

    public function testOnProductViewFeatureDisabled()
    {
        $priceAttributePriceListRepository = $this->createMock(PriceAttributePriceListRepository::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $priceAttributePriceListRepository->expects($this->once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($priceAttributePriceListRepository);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $event = $this->createEvent(new Product());
        $this->listener->onProductView($event);
    }

    public function testOnProductViewException()
    {
        $this->expectException(UnexpectedTypeException::class);

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

        $priceAttributeViewRenderExpectation = $this->expectsPriceAttributeViewRender($product);
        $this->env->expects($this->exactly(2))
            ->method('render')
            ->withConsecutive(
                $priceAttributeViewRenderExpectation,
                ['@OroPricing/Product/prices_view.html.twig', ['entity' => $product]]
            )
            ->willReturnOnConsecutiveCalls(
                $templateHtmlProductAttributePrice,
                $templateHtmlProductPrice
            );

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $event = $this->createEvent($product);
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

        $priceAttributeViewRenderExpectation = $this->expectsPriceAttributeViewRender($product);
        $this->env->expects($this->once())
            ->method('render')
            ->with(...$priceAttributeViewRenderExpectation)
            ->willReturn($templateHtmlProductAttributePrice);

        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $event = $this->createEvent($product);
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

        $this->env->expects($this->once())
            ->method('render')
            ->with('@OroPricing/Product/prices_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent(new Product(), $formView);

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

    private function expectsPriceAttributeViewRender(Product $product): array
    {
        $priceList = new PriceAttributePriceList();

        $priceAttributePriceListRepository = $this->createMock(PriceAttributePriceListRepository::class);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$priceList]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $priceAttributePriceListRepository->expects($this->once())
            ->method('getPriceAttributesQueryBuilder')
            ->willReturn($queryBuilder);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturnMap([
                [PriceAttributePriceList::class, $priceAttributePriceListRepository],
            ]);

        $this->priceAttributePricesProvider->expects($this->once())
            ->method('getPricesWithUnitAndCurrencies')
            ->with($priceList, $product)
            ->willReturn(['Test' => ['item' => ['USD' => 100]]]);

        return [
            '@OroPricing/Product/price_attribute_prices_view.html.twig',
            [
                'product' => $product,
                'priceList' => $priceList,
                'priceAttributePrices' => ['Test' => ['item' => ['USD' => 100]]]
            ]
        ];
    }
}
