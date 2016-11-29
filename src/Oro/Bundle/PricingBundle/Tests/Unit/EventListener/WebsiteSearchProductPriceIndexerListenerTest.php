<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\MinimalProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\MinimalProductPriceRepository;
use Oro\Bundle\PricingBundle\EventListener\WebsiteSearchProductPriceIndexerListener;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Symfony\Bridge\Doctrine\RegistryInterface;

class WebsiteSearchProductPriceIndexerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchProductPriceIndexerListener
     */
    private $listener;

    /**
     * @var WebsiteContextManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteContextManager;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrine;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    public function setUp()
    {
        $this->websiteContextManager = $this->getMockBuilder(WebsiteContextManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->getMock(RegistryInterface::class);

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchProductPriceIndexerListener(
            $this->websiteContextManager,
            $this->doctrine,
            $this->configManager
        );
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

        $repo = $this->getMockBuilder(MinimalProductPriceRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrine->method('getRepository')->with(MinimalProductPrice::class)->willReturn($repo);
        $repo->method('findByWebsite')
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

        $event->expects($this->exactly(2))->method('addPlaceholderField')->withConsecutive(
            [
                1,
                'minimum_price_CPL_ID_CURRENCY_UNIT',
                '10.0000',
                [
                    'CPL_ID' => 1,
                    'CURRENCY' => 'USD',
                    'UNIT' => 'liter'
                ],
            ],
            [
                2,
                'minimum_price_CPL_ID_CURRENCY_UNIT',
                '11.0000',
                [
                    'CPL_ID' => 1,
                    'CURRENCY' => 'EUR',
                    'UNIT' => 'box'
                ],
            ]
        );

        $this->listener->onWebsiteSearchIndex($event);
    }
}
