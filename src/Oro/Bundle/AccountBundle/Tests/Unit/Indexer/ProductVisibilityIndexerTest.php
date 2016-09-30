<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Indexer;

use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\AccountBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\AccountBundle\Visibility\Provider\ProductVisibilityProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\ValueWithPlaceholders;
use Oro\Bundle\WebsiteSearchBundle\Provider\IndexDataProvider;

class ProductVisibilityIndexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductVisibilityIndexer
     */
    private $indexer;

    /**
     * @var ProductVisibilityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $visibilityProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->visibilityProvider = $this->getMockBuilder(ProductVisibilityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexer = new ProductVisibilityIndexer($this->visibilityProvider);
    }

    public function testAddIndexInfo()
    {
        $entityIds = [1, 2, 3];
        $websiteId = 1;
        $event = new IndexEntityEvent(
            Product::class,
            $entityIds,
            [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $websiteId]
        );

        $this->visibilityProvider
            ->expects($this->once())
            ->method('getAccountVisibilitiesForProducts')
            ->with($entityIds, $websiteId)
            ->willReturn([
                [
                    'productId' => 1,
                    'accountId' => 1,
                ],
                [
                    'productId' => 2,
                    'accountId' => 3,
                ],
                [
                    'productId' => 3,
                    'accountId' => 2,
                ]
            ]);

        $this->visibilityProvider
            ->expects($this->once())
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
                IndexDataProvider::STANDARD_VALUES_KEY => [
                    'visibility_anonymous' => BaseVisibilityResolved::VISIBILITY_HIDDEN,
                    'visibility_new' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                    'is_visible_by_default' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                IndexDataProvider::PLACEHOLDER_VALUES_KEY => [
                    'visibility_account' => [
                        new ValueWithPlaceholders(1, ['ACCOUNT_ID' => 1]),
                    ]
                ]
            ],
            2 => [
                IndexDataProvider::STANDARD_VALUES_KEY => [
                    'visibility_anonymous' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                    'visibility_new' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                    'is_visible_by_default' => BaseVisibilityResolved::VISIBILITY_HIDDEN,
                ],
                IndexDataProvider::PLACEHOLDER_VALUES_KEY => [
                    'visibility_account' => [
                        new ValueWithPlaceholders(1, ['ACCOUNT_ID' => 3])
                    ]
                ]
            ],
            3 => [
                IndexDataProvider::STANDARD_VALUES_KEY => [
                    'visibility_anonymous' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                    'visibility_new' => BaseVisibilityResolved::VISIBILITY_HIDDEN,
                    'is_visible_by_default' => BaseVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                IndexDataProvider::PLACEHOLDER_VALUES_KEY => [
                    'visibility_account' => [
                        new ValueWithPlaceholders(1, ['ACCOUNT_ID' => 2]),
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedEntitiesData, $event->getEntitiesData());
    }
}

