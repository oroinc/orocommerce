<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Indexer;

use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\VisibilityBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderValue;

class ProductVisibilityIndexerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVisibilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $visibilityProvider;

    /** @var ProductVisibilityIndexer */
    private $indexer;

    protected function setUp(): void
    {
        $this->visibilityProvider = $this->createMock(ProductVisibilityProvider::class);

        $this->indexer = new ProductVisibilityIndexer($this->visibilityProvider);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddIndexInfo()
    {
        $entityIds = [1, 2, 3];
        $websiteId = 1;
        $event = new IndexEntityEvent(
            \stdClass::class,
            $entityIds,
            [
                AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $websiteId
            ]
        );

        $this->visibilityProvider->expects($this->once())
            ->method('getCustomerVisibilitiesForProducts')
            ->with($entityIds, $websiteId)
            ->willReturn([
                [
                    'productId' => 1,
                    'customerId' => 1,
                ],
                [
                    'productId' => 2,
                    'customerId' => 3,
                ],
                [
                    'productId' => 3,
                    'customerId' => 2,
                ]
            ]);

        $this->visibilityProvider->expects($this->once())
            ->method('getNewUserAndAnonymousVisibilitiesForProducts')
            ->with($entityIds, $websiteId)
            ->willReturn([
                [
                    'productId' => 1,
                    'visibility_anonymous' => BaseVisibilityResolved::VISIBILITY_HIDDEN,
                    'visibility_new' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                    'is_visible_by_default' => BaseVisibilityResolved::VISIBILITY_VISIBLE
                ],
                [
                    'productId' => 2,
                    'visibility_anonymous' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                    'visibility_new' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                    'is_visible_by_default' => BaseVisibilityResolved::VISIBILITY_HIDDEN
                ],
                [
                    'productId' => 3,
                    'visibility_anonymous' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                    'visibility_new' => BaseVisibilityResolved::VISIBILITY_HIDDEN,
                    'is_visible_by_default' => BaseVisibilityResolved::VISIBILITY_VISIBLE
                ]
            ]);

        $this->indexer->addIndexInfo($event, $websiteId);

        $expectedEntitiesData = [
            1 => [
                'visibility_anonymous' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_HIDDEN, 'all_text' => false],
                ],
                'visibility_new' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_VISIBLE, 'all_text' => false],
                ],
                'is_visible_by_default' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_VISIBLE, 'all_text' => false],
                ],
                'visibility_customer.CUSTOMER_ID' => [
                    ['value' => new PlaceholderValue(1, ['CUSTOMER_ID' => 1]), 'all_text' => false],
                ]
            ],
            2 => [
                'visibility_anonymous' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_VISIBLE, 'all_text' => false],
                ],
                'visibility_new' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_VISIBLE, 'all_text' => false],
                ],
                'is_visible_by_default' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_HIDDEN, 'all_text' => false],
                ],
                'visibility_customer.CUSTOMER_ID' => [
                    ['value' => new PlaceholderValue(1, ['CUSTOMER_ID' => 3]), 'all_text' => false]
                ]
            ],
            3 => [
                'visibility_anonymous' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_VISIBLE, 'all_text' => false],
                ],
                'visibility_new' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_HIDDEN, 'all_text' => false],
                ],
                'is_visible_by_default' => [
                    ['value' => BaseVisibilityResolved::VISIBILITY_VISIBLE, 'all_text' => false],
                ],
                'visibility_customer.CUSTOMER_ID' => [
                    ['value' => new PlaceholderValue(1, ['CUSTOMER_ID' => 2]), 'all_text' => false],
                ]
            ]
        ];

        $this->assertEquals($expectedEntitiesData, $event->getEntitiesData());
    }
}
