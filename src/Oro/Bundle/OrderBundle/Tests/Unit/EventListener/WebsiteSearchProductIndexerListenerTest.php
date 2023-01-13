<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\EventListener\WebsiteSearchProductIndexerListener;
use Oro\Bundle\OrderBundle\Provider\LatestOrderedProductsInfoProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Component\Testing\Unit\EntityTrait;

class WebsiteSearchProductIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const WEBSITE_ID = 1;

    /** @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteContextManager;

    /** @var LatestOrderedProductsInfoProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $infoProvider;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var IndexEntityEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var Website */
    private $website;

    /** @var WebsiteSearchProductIndexerListener */
    private $listener;

    protected function setUp(): void
    {
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $this->website = $this->getEntity(Website::class, [ 'id' => self::WEBSITE_ID ]);
        $this->infoProvider = $this->createMock(LatestOrderedProductsInfoProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->event = $this->createMock(IndexEntityEvent::class);

        $this->listener = new WebsiteSearchProductIndexerListener($this->websiteContextManager, $this->infoProvider);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('previously_purchased_products');
    }

    public function testWebsiteNotFound()
    {
        $this->event->expects($this->any())
            ->method('getContext')
            ->willReturn([]);
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website);
        $this->websiteContextManager->expects($this->once())
            ->method('getWebsite')
            ->willReturn(null);

        $this->event->expects($this->never())
            ->method('getEntities');

        $this->listener->onWebsiteSearchIndex($this->event);
    }

    public function testOnWebsiteSearchIndexForUnsupportedContext()
    {
        $this->event->expects($this->once())
            ->method('getContext')
            ->willReturn([AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]);
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled');
        $this->websiteContextManager->expects($this->never())
            ->method('getWebsite');

        $this->event->expects($this->never())
            ->method('getEntities');

        $this->listener->onWebsiteSearchIndex($this->event);
    }

    public function testFeatureDisabled()
    {
        $this->event->expects($this->any())
            ->method('getContext')
            ->willReturn([]);
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);
        $this->websiteContextManager->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->website);

        $this->event->expects($this->never())
            ->method('getEntities');

        $this->listener->onWebsiteSearchIndex($this->event);
    }

    /**
     * @dataProvider productsInfo
     */
    public function testWebsiteSearchIndex(
        array $products,
        array $orderInfo,
        callable $assertPlaceholderFieldCallback,
        array $context
    ) {
        $this->event->expects($this->any())
            ->method('getContext')
            ->willReturn($context);
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);
        $this->websiteContextManager->expects($this->once())
            ->method('getWebsite')
            ->willReturn($this->website);

        $this->event->expects($this->once())
            ->method('getEntities')
            ->willReturn($products);

        $this->infoProvider->expects($this->once())
            ->method('getLatestOrderedProductsInfo')
            ->with(array_keys($orderInfo), $this->website->getId())
            ->willReturn($orderInfo);

        $assertPlaceholderFieldCallback($this->event);

        $this->listener->onWebsiteSearchIndex($this->event);
    }

    public function productsInfo(): array
    {
        return [
            'reindex two products' => [
                'products' => [
                    0 => $this->getEntity(Product::class, ['id' => 1]),
                    1 => $this->getEntity(Product::class, ['id' => 2])
                ],
                'orderInfo' => [
                    1 => [
                        ['customer_user_id' => 1, 'created_at' => 20171],
                        ['customer_user_id' => 2, 'created_at' => 20172]
                    ],
                    2 => [
                        ['customer_user_id' => 1, 'created_at' => 20173]
                    ]
                ],
                'assertPlaceholderFieldCallback' => function (\PHPUnit\Framework\MockObject\MockObject $event) {
                    $event->expects($this->exactly(3))
                        ->method('addPlaceholderField')
                        ->withConsecutive(
                            [1, 'ordered_at_by.CUSTOMER_USER_ID', 20171, ['CUSTOMER_USER_ID' => 1]],
                            [1, 'ordered_at_by.CUSTOMER_USER_ID', 20172, ['CUSTOMER_USER_ID' => 2]],
                            [2, 'ordered_at_by.CUSTOMER_USER_ID', 20173, ['CUSTOMER_USER_ID' => 1]]
                        );
                },
                []
            ],
            'no products' => [
                'products' => [],
                'orderInfo' => [],
                'assertPlaceholderFieldCallback' => function (\PHPUnit\Framework\MockObject\MockObject $event) {
                    $event->expects($this->never())
                        ->method('addPlaceholderField');
                },
                []
            ],
            'no products with order fields group' => [
                'products' => [],
                'orderInfo' => [],
                'assertPlaceholderFieldCallback' => function (\PHPUnit\Framework\MockObject\MockObject $event) {
                    $event->expects($this->never())
                        ->method('addPlaceholderField');
                },
                [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['order']]
            ]
        ];
    }
}
