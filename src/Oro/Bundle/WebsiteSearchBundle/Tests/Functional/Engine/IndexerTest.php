<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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

    protected function setListenerData()
    {
        $products = $this->doctrineHelper->getEntityRepository(Product::class)->createQueryBuilder('product')
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
    }

    public function testReindex()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);
        $this->setListenerData();
        $indexedNum = $this->indexer->reindex(Product::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 777]);
        $itemRepo = $this->doctrineHelper->getEntityRepository(Item::class);
        $items = $itemRepo->findBy(['alias' => 'oro_product_website_777']);
        $this->assertEquals(3, $indexedNum);
        $this->assertCount(3, $items);
    }

    public function testIndexWithoutArguments()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);
        $this->setListenerData();
        $indexedNum = $this->indexer->reindex();
        $itemRepo = $this->doctrineHelper->getEntityRepository(Item::class);
        $website = $this->doctrineHelper->getEntityRepository(Website::class)->findOneBy([]);
        $items = $itemRepo->findBy(['alias' => 'oro_product_website_' . $website->getId()]);
        $this->assertEquals(3, $indexedNum);
        $this->assertCount(3, $items);
    }

    public function testEmptyMappingConfig()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn([]);
        $indexedNum = $this->indexer->reindex(Product::class, []);
        $this->assertEquals(0, $indexedNum);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage There is no such entity in mapping config.
     */
    public function testWrongMappingException()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);
        $this->indexer->reindex(\stdClass::class, []);
    }
}
