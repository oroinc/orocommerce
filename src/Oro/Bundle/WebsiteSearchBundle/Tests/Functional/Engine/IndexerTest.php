<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Indexer;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @dbIsolation
 */
class IndexerTest extends WebTestCase
{
    /** @var AbstractSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $mappingProviderMock;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var Indexer */
    protected $indexer;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $mappingConfig = [
        Product::class => [
            'alias' => 'oro_product_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'title_LOCALIZATION_ID',
                    'type' => 'text'
                ]
            ]
        ]
    ];

    protected function setUp()
    {
        $this->initClient();

        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');

        $this->mappingProviderMock = $this->getMockBuilder(AbstractSearchMappingProvider::class)->getMock();

        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->indexer = new Indexer($this->dispatcher, $this->doctrineHelper, $this->mappingProviderMock);

        $this->loadFixtures(
            [
                'Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex'
            ]
        );
    }

    public function testReindex()
    {
        $class = Product::class;
        $context = [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 777];
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);

        $products = $this->doctrineHelper->getEntityRepository($class)->createQueryBuilder('product')
            ->select('product.id')->getQuery()->getScalarResult();
        $productIds = array_column($products, 'id');

        $this->dispatcher->addListener(
            IndexEntityEvent::NAME,
            function (IndexEntityEvent $event) use ($productIds) {
                array_map(function ($id) use ($event) {
                    $event->addField(
                        $id,
                        Query::TYPE_TEXT,
                        'name',
                        'Some product name'
                    );
                }, $productIds);
            },
            -255
        );

        $indexedNum = $this->indexer->reindex($class, $context);
        $itemRepo = $this->doctrineHelper->getEntityRepository(Item::class);
        $items = $itemRepo->findAll();
        $this->assertEquals(3, $indexedNum);
        $this->assertCount(3, $items);
    }
}
