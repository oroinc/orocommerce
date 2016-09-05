<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;

/**
 * @dbIsolation
 */
class OrmIndexerTest extends WebTestCase
{
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
        TestProduct::class => [
            'alias' => 'oro_product_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'title_LOCALIZATION_ID',
                    'type' => 'text'
                ]
            ]
        ]
    ];

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    protected function setUp()
    {
        $this->initClient();

        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->mappingProviderMock = $this->getMockBuilder(AbstractSearchMappingProvider::class)->getMock();
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');
        $this->entityAliasResolver = $this->getContainer()->get('oro_entity.entity_alias_resolver');

        $this->indexer = new OrmIndexer(
            $this->dispatcher,
            $this->doctrineHelper,
            $this->mappingProviderMock,
            $this->entityAliasResolver
        );

        $this->loadFixtures([LoadProductsToIndex::class]);
    }

    /**
     * @param array $productNames
     * @return array
     */
    protected function getProductIdsByNames(array $productNames)
    {
        $qb = $this->doctrineHelper->getEntityRepository(TestProduct::class)->createQueryBuilder('product');
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
        $this->indexer->reindex(TestProduct::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 777]);
        $recordIds = $this->getItemRecordIds('oro_product_777');
        $this->assertEquals($productIds, $recordIds);
    }

    protected function tearDown()
    {
        //Remove listener to not to interract with other tests
        $this->dispatcher->removeListener(IndexEntityEvent::NAME, $this->listener);

        unset($this->doctrineHelper);
        unset($this->mappingProviderMock);
        unset($this->dispatcher);
        unset($this->indexer);
    }

    public function testIndexWithoutArguments()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);

        $website = $this->doctrineHelper->getEntityRepository(Website::class)->findOneBy([]);
        $this->dispatcher->addListener(
            RestrictIndexEntityEvent::NAME,
            function (RestrictIndexEntityEvent $event) {
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
        $recordIds = $this->getItemRecordIds('oro_product_' . $website->getId());
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
        $indexedNum = $this->indexer->reindex(TestProduct::class, []);
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
