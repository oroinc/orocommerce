<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\EventListener\WebsiteSearchProductPriceIndexerListener;
use Oro\Bundle\PricingBundle\Tests\Unit\Entity\Repository\Stub\CombinedProductPriceRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SearchBundle\Formatter\DecimalFlatValueFormatter;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebsiteSearchProductPriceIndexerListenerTest extends TestCase
{
    use EntityTrait;

    private WebsiteSearchProductPriceIndexerListener $listener;

    private WebsiteContextManager|MockObject $websiteContextManager;

    private ManagerRegistry|MockObject $doctrine;

    private ConfigManager|MockObject $configManager;

    private FeatureChecker|MockObject $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new WebsiteSearchProductPriceIndexerListener(
            $this->websiteContextManager,
            $this->doctrine,
            $this->configManager,
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
            ->method('getWebsiteId');

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
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects(self::any())
            ->method('getContext')
            ->willReturn([]);
        $this->websiteContextManager->expects(self::once())
            ->method('getWebsiteId')
            ->willReturn(null);

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

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $products = [
            (new ProductStub())->setId(1)->setType(Product::TYPE_SIMPLE),
            (new ProductStub())->setId(2)->setType(Product::TYPE_SIMPLE),
        ];
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
        $this->websiteContextManager->expects(self::once())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->configManager->expects(self::once())
            ->method('get')
            ->willReturn(2);

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $em->expects(self::once())
            ->method('getReference')
            ->with(CombinedPriceList::class, 2)
            ->willReturn($cpl);

        $repo = $this->createMock(CombinedProductPriceRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(CombinedProductPrice::class)
            ->willReturn($repo);
        $repo->expects(self::any())
            ->method('findMinByWebsiteForFilter')
            ->with(1, $products, $cpl)
            ->willReturn(
                [
                    [
                        'product' => 1,
                        'value' => '10.0000',
                        'currency' => 'USD',
                        'unit' => 'liter',
                        'cpl' => 1,
                    ],
                    [
                        'product' => 2,
                        'value' => '11.0000',
                        'currency' => 'EUR',
                        'unit' => 'box',
                        'cpl' => 1,
                    ],
                ]
            );
        $repo->expects(self::any())
            ->method('findMinByWebsiteForSort')
            ->with(1, $products, $cpl)
            ->willReturn(
                [
                    [
                        'product' => 1,
                        'value' => '10.0000',
                        'currency' => 'USD',
                        'cpl' => 1,
                    ],
                    [
                        'product' => 2,
                        'value' => '11.0000',
                        'currency' => 'EUR',
                        'cpl' => 1,
                    ],
                ]
            );

        $event->expects(self::exactly(4))
            ->method('addPlaceholderField')
            ->withConsecutive(
                [
                    1,
                    'minimal_price.CPL_ID_CURRENCY_UNIT',
                    '10.0000',
                    ['CPL_ID' => 1, 'CURRENCY' => 'USD', 'UNIT' => 'liter']
                ],
                [
                    2,
                    'minimal_price.CPL_ID_CURRENCY_UNIT',
                    '11.0000',
                    ['CPL_ID' => 1, 'CURRENCY' => 'EUR', 'UNIT' => 'box']
                ],
                [
                    1,
                    'minimal_price.CPL_ID_CURRENCY',
                    '10.0000',
                    ['CPL_ID' => 1, 'CURRENCY' => 'USD']
                ],
                [
                    2,
                    'minimal_price.CPL_ID_CURRENCY',
                    '11.0000',
                    ['CPL_ID' => 1, 'CURRENCY' => 'EUR']
                ]
            );

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function contextDataProvider(): array
    {
        return [
            [[]],
            [[AbstractIndexer::CONTEXT_FIELD_GROUPS => ['pricing']]]
        ];
    }
}
