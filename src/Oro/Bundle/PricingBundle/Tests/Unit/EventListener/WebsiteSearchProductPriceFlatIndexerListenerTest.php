<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\WebsiteSearchProductPriceFlatIndexerListener;
use Oro\Bundle\PricingBundle\Model\AbstractPriceListTreeHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SearchBundle\Formatter\DecimalFlatValueFormatter;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsiteSearchProductPriceFlatIndexerListenerTest extends TestCase
{
    use EntityTrait;

    private WebsiteContextManager|MockObject $websiteContextManager;

    private ManagerRegistry|MockObject $doctrine;

    private ConfigManager|MockObject $configManager;

    private AbstractPriceListTreeHandler|MockObject $priceListTreeHandler;

    private FeatureChecker|MockObject $featureChecker;

    private WebsiteSearchProductPriceFlatIndexerListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->priceListTreeHandler = $this->createMock(AbstractPriceListTreeHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new WebsiteSearchProductPriceFlatIndexerListener(
            $this->websiteContextManager,
            $this->doctrine,
            $this->configManager,
            $this->priceListTreeHandler,
            new DecimalFlatValueFormatter()
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
    }

    public function testOnWebsiteSearchIndexFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects(self::any())
            ->method('getContext')
            ->willReturn([]);

        $this->websiteContextManager->expects(self::never())
            ->method($this->anything());

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndexUnsupportedFieldGroup(): void
    {
        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled');

        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects(self::any())
            ->method('getContext')
            ->willReturn([AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]);

        $this->websiteContextManager->expects(self::never())
            ->method($this->anything());

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndexWithoutWebsite(): void
    {
        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects(self::any())
            ->method('getContext')
            ->willReturn([]);
        $this->websiteContextManager->expects(self::once())
            ->method('getWebsite')
            ->willReturn(null);
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $event->expects(self::once())
            ->method('stopPropagation');

        $this->listener->onWebsiteSearchIndex($event);
    }

    /**
     * @dataProvider contextDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnWebsiteSearchIndex(array $context): void
    {
        $this->listener->setNotAllowedProductTypes([
            Product::TYPE_KIT,
        ]);

        $basePriceList = $this->getEntity(PriceList::class, ['id' => 2]);
        $accuracy = 'customer';
        $products = [
            (new ProductStub())->setId(1)->setType(Product::TYPE_SIMPLE),
            (new ProductStub())->setId(2)->setType(Product::TYPE_SIMPLE),
        ];
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects(self::any())
            ->method('getContext')
            ->willReturn($context);
        $event->expects(self::any())
            ->method('getEntities')
            ->willReturn(array_merge(
                $products,
                [(new ProductStub())->setId(3)->setType(Product::TYPE_KIT),] // Add unsupported product
            ));

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);
        $this->websiteContextManager->expects(self::once())
            ->method('getWebsite')
            ->willReturn($website);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_pricing.price_indexation_accuracy')
            ->willReturn($accuracy);
        $this->priceListTreeHandler->expects(self::once())
            ->method('getPriceList')
            ->with(null, $website)
            ->willReturn($basePriceList);

        $repo = $this->createMock(ProductPriceRepository::class);
        $repo->expects(self::once())
            ->method('findMinByWebsiteForFilter')
            ->with($website, $products, $basePriceList, $accuracy)
            ->willReturn([
                [
                    'product_id' => 1,
                    'value' => '10.0000',
                    'currency' => 'USD',
                    'unit' => 'liter',
                    'price_list_id' => 1,
                ],
                [
                    'product_id' => 2,
                    'value' => '11.0000',
                    'currency' => 'EUR',
                    'unit' => 'box',
                    'price_list_id' => 1,
                ],
            ]);
        $repo->expects(self::once())
            ->method('findMinByWebsiteForSort')
            ->with($website, $products, $basePriceList, $accuracy)
            ->willReturn([
                [
                    'product_id' => 1,
                    'value' => '10.0000',
                    'currency' => 'USD',
                    'price_list_id' => 1,
                ],
                [
                    'product_id' => 2,
                    'value' => '11.0000',
                    'currency' => 'EUR',
                    'price_list_id' => 1,
                ],
            ]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getRepository')
            ->with(ProductPrice::class)
            ->willReturn($repo);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ProductPrice::class)
            ->willReturn($em);

        $event->expects(self::exactly(4))
            ->method('addPlaceholderField')
            ->withConsecutive(
                [
                    1,
                    'minimal_price.PRICE_LIST_ID_CURRENCY_UNIT',
                    '10.0000',
                    ['PRICE_LIST_ID' => 1, 'CURRENCY' => 'USD', 'UNIT' => 'liter'],
                ],
                [
                    2,
                    'minimal_price.PRICE_LIST_ID_CURRENCY_UNIT',
                    '11.0000',
                    ['PRICE_LIST_ID' => 1, 'CURRENCY' => 'EUR', 'UNIT' => 'box'],
                ],
                [
                    1,
                    'minimal_price.PRICE_LIST_ID_CURRENCY',
                    '10.0000',
                    ['PRICE_LIST_ID' => 1, 'CURRENCY' => 'USD'],
                ],
                [
                    2,
                    'minimal_price.PRICE_LIST_ID_CURRENCY',
                    '11.0000',
                    ['PRICE_LIST_ID' => 1, 'CURRENCY' => 'EUR'],
                ]
            );

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function contextDataProvider(): array
    {
        return [
            [[]],
            [[AbstractIndexer::CONTEXT_FIELD_GROUPS => ['pricing']]],
        ];
    }
}
