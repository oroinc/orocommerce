<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\AccountBundle\EventListener\WebsiteSearchProductVisibilityIndexerListener;
use Oro\Bundle\AccountBundle\Visibility\Provider\AccountProductVisibilityProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductVisibilityIndexerListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSearchProductVisibilityIndexerListener
     */
    private $listener;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var AccountProductVisibilityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $visibilityProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->visibilityProvider = $this->getMockBuilder(AccountProductVisibilityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebsiteSearchProductVisibilityIndexerListener(
            $this->doctrineHelper,
            $this->visibilityProvider
        );
    }

    public function testOnWebsiteSearchIndex()
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
                    'is_visible_by_default' => 1
                ],
                [
                    'productId' => 2,
                    'accountId' => 3,
                    'is_visible_by_default' => -1
                ],
                [
                    'productId' => 3,
                    'accountId' => 2,
                    'is_visible_by_default' => 1
                ]
            ]);

        $this->visibilityProvider
            ->expects($this->once())
            ->method('getNewUserAndAnonymousVisibilitiesForProducts')
            ->with($entityIds, $websiteId)
            ->willReturn([
                [
                    'productId' => 1,
                    'visibility_anonymous' => 0,
                    'visibility_new' => 1
                ],
                [
                    'productId' => 2,
                    'visibility_anonymous' => 1,
                    'visibility_new' => 1
                ],
                [
                    'productId' => 3,
                    'visibility_anonymous' => 1,
                    'visibility_new' => 0
                ]
            ]);

        $this->listener->onWebsiteSearchIndex($event);

        $expectedEntitiesData = [
            1 => [
                Query::TYPE_INTEGER => [
                    'is_visible_by_default' => 1,
                    'visibility_account_1' => 1,
                    'visibility_anonymous' => 0,
                    'visibility_new' => 1
                ]
            ],
            2 => [
                Query::TYPE_INTEGER => [
                    'is_visible_by_default' => -1,
                    'visibility_account_3' => 1,
                    'visibility_anonymous' => 1,
                    'visibility_new' => 1
                ]
            ],
            3 => [
                Query::TYPE_INTEGER => [
                    'is_visible_by_default' => 1,
                    'visibility_account_2' => 1,
                    'visibility_anonymous' => 1,
                    'visibility_new' => 0
                ]
            ]
        ];

        $this->assertEquals($expectedEntitiesData, $event->getEntitiesData());
    }

    public function testOnWebsiteSearchIndexWhenWrongEntityClassIsGiven()
    {
        $event = new IndexEntityEvent(\stdClass::class, [], []);

        $this->visibilityProvider
            ->expects($this->never())
            ->method('getAccountVisibilitiesForProducts');

        $this->visibilityProvider
            ->expects($this->never())
            ->method('getNewUserAndAnonymousVisibilitiesForProducts');

        $this->listener->onWebsiteSearchIndex($event);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Website id is absent in context
     */
    public function testOnWebsiteSearchIndexWhenWebsiteIdIsNotInContext()
    {
        $event = new IndexEntityEvent(Product::class, [], []);

        $this->visibilityProvider
            ->expects($this->never())
            ->method('getAccountVisibilitiesForProducts');

        $this->visibilityProvider
            ->expects($this->never())
            ->method('getNewUserAndAnonymousVisibilitiesForProducts');

        $this->listener->onWebsiteSearchIndex($event);
    }
}
