<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @dbIsolationPerTest
 */
class OrmIndexerTest extends WebTestCase
{
    use SearchTestTrait;

    /** @var AbstractSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $mappingProviderMock;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var OrmIndexer */
    protected $indexer;

    /** @var callable */
    protected $listener;

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

        $this->indexer = new OrmIndexer($this->dispatcher, $this->doctrineHelper, $this->mappingProviderMock);

    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();
        //Remove listener to not to interract with other tests
        $this->dispatcher->removeListener(IndexEntityEvent::NAME, $this->listener);
    }

    /**
     * @param array $productNames
     * @return array
     */
    protected function getProductIdsByNames(array $productNames)
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $qb = $this->doctrineHelper->getEntityRepository(Product::class)->createQueryBuilder('product');
        $products = $qb->select('product.id')
            ->where($qb->expr()->in('product.name', ':names'))
            ->setParameter('names', $productNames)
            ->getQuery()
            ->getScalarResult();

        return array_column($products, 'id');
    }

    /**
     * @param array $productIds
     * @return callable
     */
    protected function setListener(array $productIds)
    {
        $listener = function (IndexEntityEvent $event) use ($productIds) {
            array_map(function ($id) use ($event) {
                $event->addField(
                    $id,
                    Query::TYPE_TEXT,
                    'name',
                    "Some product name $id"
                );
            }, $productIds);
        };

        $this->dispatcher->addListener(
            IndexEntityEvent::NAME,
            $listener,
            -255
        );

        return $listener;
    }

    /**
     * @param string $alias
     * @return array
     */
    protected function getItemRecordIds($alias)
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $itemRepo = $this->doctrineHelper->getEntityRepository(Item::class);
        $qb = $itemRepo->createQueryBuilder('item');
        $items = $qb->select('item.recordId')
            ->where($qb->expr()->eq('item.alias', ':alias'))
            ->setParameter('alias', $alias)
            ->orderBy('item.id')
            ->getQuery()
            ->getScalarResult();

        return array_column($items, 'recordId');
    }

    public function testReindex()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);
        $productIds = $this->getProductIdsByNames(
            [
                LoadProductsToIndex::PRODUCT1,
                LoadProductsToIndex::PRODUCT2,
                LoadProductsToIndex::RESTRCTED_PRODUCT
            ]
        );
        $this->listener = $this->setListener($productIds);
        $this->indexer->reindex(Product::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 777]);
        $recordIds = $this->getItemRecordIds('oro_product_website_777');
        $this->assertEquals($productIds, $recordIds);
    }

    public function testReindexOfAllEntityClasses()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);

        $website = $this->doctrineHelper->getEntityRepository(Website::class)->findOneBy([]);
        $this->dispatcher->addListener(
            RestrictIndexEntitiesEvent::NAME,
            function (RestrictIndexEntitiesEvent $event) {
                $qb = $event->getQueryBuilder();
                list($rootAlias) = $qb->getRootAliases();
                $qb->where($qb->expr()->neq($rootAlias . '.name', ':name'))
                    ->setParameter('name', LoadProductsToIndex::RESTRCTED_PRODUCT);
            },
            -255
        );
        $productIds = $this->getProductIdsByNames(
            [
                LoadProductsToIndex::PRODUCT1,
                LoadProductsToIndex::PRODUCT2
            ]
        );
        $this->listener = $this->setListener($productIds);
        $this->indexer->reindex();
        $recordIds = $this->getItemRecordIds('oro_product_website_' . $website->getId());
        $this->assertCount(2, $recordIds);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Mapping config is empty.
     */
    public function testEmptyMappingConfigException()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

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
        $this->loadFixtures([LoadProductsToIndex::class]);

        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);
        $this->indexer->reindex(\stdClass::class, []);
        $this->indexer = new OrmIndexer($this->dispatcher, $this->doctrineHelper, $this->mappingProviderMock);

        $this->loadFixtures([LoadItemData::class]);
    }

    public function testCount()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenNonExistentEntityRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->indexer->delete([$this->getProductEntity(123456)], ['website_id' => 1]);

        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenEntityIdsArrayIsEmpty()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->indexer->delete([], ['website_id' => 1]);

        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenProductEntitiesForSpecificWebsiteRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityAlias')
            ->willReturn('orob2b_product_WEBSITE_ID');

        $this->indexer->delete(
            [
                $this->getProductEntity(1),
                $this->getProductEntity(2)
            ],
            ['website_id' => 1]
        );

        $this->assertEntityCount(3, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(1, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenProductEntitiesForAllWebsitesRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->indexer->delete(
            [
                $this->getProductEntity(1),
                $this->getProductEntity(2)
            ],
            []
        );

        $this->assertEntityCount(1, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    /**
     * @param int $id
     * @return Product
     */
    private function getProductEntity($id)
    {
        $product = new Product();
        $product->setId($id);

        return $product;
    }
}
