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
use Oro\Bundle\SearchBundle\Formatter\DecimalFlatValueFormatter;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchProductPriceIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var WebsiteSearchProductPriceIndexerListener */
    private $listener;

    /** @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteContextManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

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

    public function testOnWebsiteSearchIndexFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn([]);

        $this->websiteContextManager->expects($this->never())
            ->method('getWebsiteId');

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndexUnsupportedFieldGroup()
    {
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');

        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn([AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]);

        $this->websiteContextManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndexWithoutWebsite()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn([]);
        $this->websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(null);

        $event->expects($this->once())
            ->method('stopPropagation');
        $this->listener->onWebsiteSearchIndex($event);
    }

    /**
     * @dataProvider contextDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnWebsiteSearchIndex(array $context)
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);
        $products = [new Product()];
        $event = $this->createMock(IndexEntityEvent::class);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn($context);
        $event->expects($this->any())
            ->method('getEntities')
            ->willReturn($products);
        $this->websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(1);
        $this->configManager->expects($this->once())
            ->method('get')
            ->willReturn(2);

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $em->expects($this->once())
            ->method('getReference')
            ->with(CombinedPriceList::class, 2)
            ->willReturn($cpl);

        $repo = $this->createMock(CombinedProductPriceRepository::class);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(CombinedProductPrice::class)
            ->willReturn($repo);
        $repo->expects($this->any())
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
        $repo->expects($this->any())
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

        $event->expects($this->exactly(4))
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

        $this->featureChecker->expects($this->once())
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
