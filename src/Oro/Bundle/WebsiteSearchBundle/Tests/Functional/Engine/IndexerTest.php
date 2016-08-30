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
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntitiesEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;

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

        $this->loadFixtures([LoadProductsToIndex::class]);
    }

    /**
     * @param array $productNames
     * @return array
     */
    protected function getProductIdsByNames(array $productNames)
    {
        $qb = $this->doctrineHelper->getEntityRepository(Product::class)->createQueryBuilder('product');
        $products = $qb->select('product.id')
            ->where($qb->expr()->in('product.name', ':names'))
            ->setParameter('names', $productNames)
            ->getQuery()->getScalarResult();

        return array_column($products, 'id');
    }

    /**
     * @param $productIds
     * @return callable
     */
    protected function setListener($productIds)
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
     * @param $alias
     * @return array
     */
    protected function getItemRecordIds($alias)
    {
        $itemRepo = $this->doctrineHelper->getEntityRepository(Item::class);
        $qb = $itemRepo->createQueryBuilder('item');
        $items = $qb->select('item.recordId')
            ->where($qb->expr()->eq('item.alias', ':alias'))
            ->setParameter('alias', $alias)
            ->getQuery()->getScalarResult();

        return array_column($items, 'recordId');
    }

    public function testReindex()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);
        $productIds = $this->getProductIdsByNames(
            [
                LoadProductsToIndex::PRODUCT1,
                LoadProductsToIndex::PRODUCT2,
                LoadProductsToIndex::RESTRCTED_PRODUCT
            ]
        );
        $listener = $this->setListener($productIds);
        $this->indexer->reindex(Product::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 777]);
        $recordIds = $this->getItemRecordIds('oro_product_website_777');
        $this->assertEquals($productIds, $recordIds);
        //Remove listener to not to interract with other tests
        $this->dispatcher->removeListener(IndexEntityEvent::NAME, $listener);
    }

    public function testIndexWithoutArguments()
    {
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
        $listener = $this->setListener($productIds);
        $this->indexer->reindex();
        $this->dispatcher->removeListener(IndexEntityEvent::NAME, $listener);

        $recordIds = $this->getItemRecordIds('oro_product_website_' . $website->getId());
        $this->assertCount(2, $recordIds);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Mapping config is empty.
     */
    public function testEmptyMappingConfigException()
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
