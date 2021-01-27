<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\EventListener\WebsiteSearchProductPriceIndexerListener;
use Oro\Bundle\PricingBundle\Tests\Unit\Entity\Repository\Stub\CombinedProductPriceRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class WebsiteSearchProductPriceIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebsiteSearchProductPriceIndexerListener
     */
    private $listener;

    /**
     * @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteContextManager;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrine;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $manager;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->websiteContextManager = $this->getMockBuilder(WebsiteContextManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->method('getManagerForClass')->willReturn($this->manager);

        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new WebsiteSearchProductPriceIndexerListener(
            $this->websiteContextManager,
            $this->doctrine,
            $this->configManager
        );
    }

    public function testOnWebsiteSearchIndexFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(false);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');

        $event = $this->createMock(IndexEntityEvent::class);

        $this->websiteContextManager->expects($this->never())->method('getWebsiteId');

        $this->listener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndexWithoutWebsite()
    {
        $event = $this->getMockBuilder(IndexEntityEvent::class)->disableOriginalConstructor()->getMock();
        $event->method('getContext')->willReturn([]);
        $this->websiteContextManager->expects($this->once())->method('getWebsiteId')->willReturn(null);

        $event->expects($this->once())->method('stopPropagation');
        $this->listener->onWebsiteSearchIndex($event);
    }

    public function testOnWebsiteSearchIndex()
    {
        $products = [new Product()];
        $event = $this->getMockBuilder(IndexEntityEvent::class)->disableOriginalConstructor()->getMock();
        $event->method('getContext')->willReturn([]);
        $event->method('getEntities')->willReturn($products);
        $this->websiteContextManager->expects($this->once())->method('getWebsiteId')->willReturn(1);
        $this->configManager->expects($this->once())->method('get')->willReturn(2);

        $repo = $this->getMockBuilder(CombinedProductPriceRepository::class)->disableOriginalConstructor()->getMock();
        $this->manager->method('getRepository')->with(CombinedProductPrice::class)->willReturn($repo);
        $repo->method('findMinByWebsiteForFilter')
            ->with(1, $products, 2)
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
        $repo->method('findMinByWebsiteForSort')
            ->with(1, $products, 2)
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

        $event->expects($this->exactly(4))->method('addPlaceholderField')->withConsecutive(
            [
                1,
                'minimal_price_CPL_ID_CURRENCY_UNIT',
                '10.0000',
                ['CPL_ID' => 1, 'CURRENCY' => 'USD', 'UNIT' => 'liter']
            ],
            [
                2,
                'minimal_price_CPL_ID_CURRENCY_UNIT',
                '11.0000',
                ['CPL_ID' => 1, 'CURRENCY' => 'EUR', 'UNIT' => 'box']
            ],
            [
                1,
                'minimal_price_CPL_ID_CURRENCY',
                '10.0000',
                ['CPL_ID' => 1, 'CURRENCY' => 'USD']
            ],
            [
                2,
                'minimal_price_CPL_ID_CURRENCY',
                '11.0000',
                ['CPL_ID' => 1, 'CURRENCY' => 'EUR']
            ]
        );

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('feature1', null)
            ->willReturn(true);

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('feature1');
        $this->listener->onWebsiteSearchIndex($event);
    }
}
