<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadOtherWebsite;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\AbstractSearchWebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Stub\OrmIndexerStub;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrmIndexerTest extends AbstractSearchWebTestCase
{
    /** @var WebsiteSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $mappingProviderMock;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var OrmIndexer */
    private $indexer;

    /** @var callable */
    private $listener;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var array */
    private $mappingConfig = [
        TestProduct::class => [
            'alias' => 'oro_product_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'title_LOCALIZATION_ID',
                    'type' => 'text'
                ]
            ]
        ],
        TestEmployee::class => [
            'alias' => 'oro_employee_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'text'
                ]
            ]
        ]
    ];

    protected function setUp()
    {
        $this->initClient();
        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->mappingProviderMock = $this->getMockBuilder(WebsiteSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityAliasResolver = $this->getContainer()->get('oro_entity.entity_alias_resolver');

        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->indexer = new OrmIndexer(
            $this->dispatcher,
            $this->doctrineHelper,
            $this->mappingProviderMock,
            $this->entityAliasResolver
        );

        $this->clearRestrictListeners($this->getRestrictEntityEventName());
    }

    /**
     * {@inheritdoc}
     */
    public function getRestrictEntityEventName()
    {
        return sprintf('%s.%s', RestrictIndexEntityEvent::NAME, 'testproduct');
    }

    protected function tearDown()
    {
        $this->clearIndexTextTable();

        //Remove listener to not to interract with other tests
        $this->dispatcher->removeListener(IndexEntityEvent::NAME, $this->listener);

        unset($this->doctrineHelper, $this->mappingProviderMock, $this->dispatcher, $this->indexer);
    }

    /**
     * @return callable
     */
    private function setListener()
    {
        $listener = function (IndexEntityEvent $event) {
            foreach ($event->getEntityIds() as $entityId) {
                $event->addField(
                    $entityId,
                    Query::TYPE_TEXT,
                    'name',
                    sprintf('Reindexed product %s', $entityId)
                );
            }
        };

        $this->dispatcher->addListener(
            IndexEntityEvent::NAME,
            $listener,
            -255
        );

        return $listener;
    }

    /**
     * @param int $expectedCalls
     * @param string $class
     * @param bool $return
     */
    private function setClassSupportedExpectation($expectedCalls, $class, $return)
    {
        $this->mappingProviderMock
            ->expects($this->exactly($expectedCalls))
            ->method('isClassSupported')
            ->with($class)
            ->willReturn($return);
    }

    /**
     * @param int $expectedCalls
     * @param string $class
     * @param string $return
     */
    private function setEntityAliasExpectation($expectedCalls, $class, $return)
    {
        $this->mappingProviderMock
            ->expects($this->exactly($expectedCalls))
            ->method('getEntityAlias')
            ->with($class)
            ->willReturn($return);
    }
    public function testReindexForSpecificWebsite()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(1, TestProduct::class, true);
        $this->setEntityAliasExpectation(1, TestProduct::class, $this->mappingConfig[TestProduct::class]['alias']);
        $this->listener = $this->setListener();

        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsiteId()
            ]
        );

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_' . $this->getDefaultWebsiteId()]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());
    }

    public function testReindexForSpecificWebsiteWithCustomBatchSize()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setEntityAliasExpectation(1, TestProduct::class, $this->mappingConfig[TestProduct::class]['alias']);
        $this->setClassSupportedExpectation(1, TestProduct::class, true);

        $this->indexer = new OrmIndexerStub(
            $this->dispatcher,
            $this->doctrineHelper,
            $this->mappingProviderMock,
            $this->entityAliasResolver
        );

        $this->listener = $this->setListener();
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsiteId()
            ]
        );

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_' . $this->getDefaultWebsiteId()]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());
    }

    public function testReindexWithRestriction()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(1, TestProduct::class, true);
        $this->setEntityAliasExpectation(1, TestProduct::class, $this->mappingConfig[TestProduct::class]['alias']);

        $restrictedProduct = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);

        $this->dispatcher->addListener(
            $this->getRestrictEntityEventName(),
            function (RestrictIndexEntityEvent $event) use ($restrictedProduct) {
                $qb = $event->getQueryBuilder();
                list($rootAlias) = $qb->getRootAliases();
                $qb->where($qb->expr()->neq($rootAlias . '.id', ':id'))
                    ->setParameter('id', $restrictedProduct->getId());
            },
            -255
        );

        $this->listener = $this->setListener();
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsiteId()
            ]
        );

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_' . $this->getDefaultWebsiteId()]);

        $this->assertCount(1, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
    }

    public function testReindexWithAllRestricted()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $this->setClassSupportedExpectation(1, TestProduct::class, true);

        $restrictedProduct1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $restrictedProduct2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $restrictedIds = [$restrictedProduct1->getId(), $restrictedProduct2->getId()];

        $this->dispatcher->addListener(
            $this->getRestrictEntityEventName(),
            function (RestrictIndexEntityEvent $event) use ($restrictedIds) {
                $qb = $event->getQueryBuilder();
                list($rootAlias) = $qb->getRootAliases();
                $qb->where($qb->expr()->notIn($rootAlias . '.id', ':id'))
                    ->setParameter('id', $restrictedIds);
            },
            -255
        );

        $this->listener = $this->setListener();
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsiteId()
            ]
        );

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_' . $this->getDefaultWebsiteId()]);

        $this->assertCount(0, $items);
    }

    public function testReindexOfAllWebsites()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn([TestProduct::class]);

        $this->setEntityAliasExpectation(2, TestProduct::class, $this->mappingConfig[TestProduct::class]['alias']);

        $this->listener = $this->setListener();
        $this->indexer->reindex();

        $otherWebsite = $this->getReference(LoadOtherWebsite::REFERENCE_OTHER_WEBSITE);
        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy([
            'alias' => 'oro_product_' . $otherWebsite->getId()
        ]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());

        $items = $this->getItemRepository()->findBy([
            'alias' => 'oro_product_' . $this->getDefaultWebsiteId()
        ]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Mapping config is empty.
     */
    public function testEmptyMappingConfigException()
    {
        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn([]);

        $indexedNum = $this->indexer->reindex();
        $this->assertEquals(0, $indexedNum);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage There is no such entity in mapping config.
     */
    public function testWrongMappingException()
    {
        $this->setClassSupportedExpectation(1, 'stdClass', false);
        $this->indexer->reindex(\stdClass::class, []);
    }


    public function testDeleteWhenNonExistentEntityRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->setClassSupportedExpectation(1, TestDepartment::class, false);
        $testEntity = new TestDepartment();
        $testEntity->setId(123456);

        $this->indexer->delete($testEntity, ['website_id' => $this->getDefaultWebsiteId()]);

        $this->assertEntityCount(8, Item::class);
        $this->assertEntityCount(2, IndexInteger::class);
        $this->assertEntityCount(6, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }

    public function testDeleteWhenEntityIdsArrayIsEmpty()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProviderMock
            ->expects($this->never())
            ->method('getEntityAlias');

        $this->indexer->delete([], ['website_id' => $this->getDefaultWebsiteId()]);

        $this->assertEntityCount(8, Item::class);
        $this->assertEntityCount(2, IndexInteger::class);
        $this->assertEntityCount(6, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }

    public function testDeleteWhenProductEntitiesForSpecificWebsiteRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProviderMock
            ->expects($this->any())
            ->method('isClassSupported')
            ->with(TestProduct::class)
            ->willReturn(true);
        $this->setEntityAliasExpectation(1, TestProduct::class, 'oro_product_WEBSITE_ID');

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->indexer->delete(
            [
                $product1,
                $product2,
            ],
            ['website_id' => $this->getDefaultWebsiteId()]
        );

        $this->assertEntityCount(6, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(5, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testDeleteForSpecificWebsiteAndEntitiesWithoutMappingConfigurationOrNotManageable()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->mappingProviderMock
            ->expects($this->exactly(3))
            ->method('isClassSupported')
            ->withConsecutive([TestProduct::class], [TestProduct::class], [TestDepartment::class])
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->setEntityAliasExpectation(1, TestProduct::class, 'oro_product_WEBSITE_ID');

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->indexer->delete(
            [
                $product1,
                $product2,
                new \stdClass(),
                new \stdClass(),
                new TestDepartment()
            ],
            ['website_id' => $this->getDefaultWebsiteId()]
        );

        $this->assertEntityCount(6, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(5, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testDeleteWhenProductEntitiesForAllWebsitesRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProviderMock
            ->expects($this->any())
            ->method('isClassSupported')
            ->with(TestProduct::class)
            ->willReturn(true);

        $this->mappingProviderMock
            ->expects($this->never())
            ->method('getEntityAlias');

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->indexer->delete(
            [
                $product1,
                $product2,
            ],
            []
        );

        $this->assertEntityCount(4, Item::class);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(4, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    public function testResetIndexForAllWebsitesAndClasses()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->indexer->resetIndex();

        $this->assertEntityCount(0, Item::class);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    public function testSaveForSingleEntityAndSingleWebsite()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(2, TestProduct::class, true);
        $this->setEntityAliasExpectation(2, TestProduct::class, $this->mappingConfig[TestProduct::class]['alias']);

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $this->listener = $this->setListener();

        $this->indexer->save(
            $product1,
            [
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsiteId()
            ]
        );

        $this->assertEntityCount(1, Item::class);
    }

    public function testSaveForSingleEntityAndAllWebsites()
    {
        $this->loadFixtures([LoadOtherWebsite::class]);
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(2, TestProduct::class, true);
        $this->setEntityAliasExpectation(2, TestProduct::class, $this->mappingConfig[TestProduct::class]['alias']);

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $this->listener = $this->setListener();

        $this->indexer->save($product1);

        $this->assertEntityCount(2, Item::class);
    }

    public function testSaveForSeveralEntitiesAndSingleWebsite()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);
        $this->listener = $this->setListener();
        $this->setClassSupportedExpectation(3, TestProduct::class, true);

        $this->indexer->save(
            [$product1, $product2],
            [
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsiteId()
            ]
        );
        $this->assertEntityCount(2, Item::class);
    }

    public function testSaveForSeveralEntitiesAndAllWebsites()
    {
        $this->loadFixtures([LoadOtherWebsite::class]);
        $this->loadFixtures([LoadProductsToIndex::class]);

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->listener = $this->setListener();
        $this->setClassSupportedExpectation(3, TestProduct::class, true);
        $this->setEntityAliasExpectation(2, TestProduct::class, $this->mappingConfig[TestProduct::class]['alias']);

        $this->indexer->save([$product1, $product2]);

        $this->assertEntityCount(4, Item::class);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage There is no such entity in mapping config.
     */
    public function testSaveForNotSupportedEntity()
    {
        $this->loadFixtures([LoadOtherWebsite::class]);
        $this->loadFixtures([LoadProductsToIndex::class]);

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->listener = $this->setListener();
        $this->setClassSupportedExpectation(1, TestProduct::class, false);
        $this->indexer->save([$product1, $product2]);
    }

    public function testResetIndexForAllWebsitesAndSpecificClass()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->indexer->resetIndex(TestProduct::class);

        $this->assertEntityCount(4, Item::class);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(4, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    public function testResetIndexForSpecificWebsiteAndSpecificClass()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->setEntityAliasExpectation(1, TestProduct::class, $this->mappingConfig[TestProduct::class]['alias']);
        $this->indexer->resetIndex(TestProduct::class, ['website_id' => $this->getDefaultWebsiteId()]);

        $this->assertEntityCount(6, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(5, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testResetIndexForSpecificWebsiteAndAllClasses()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this
            ->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn(array_keys($this->mappingConfig));

        $this
            ->mappingProviderMock
            ->expects($this->exactly(2))
            ->method('getEntityAlias')
            ->withConsecutive([TestProduct::class], [TestEmployee::class])
            ->will($this->onConsecutiveCalls('oro_product_WEBSITE_ID', 'oro_employee_WEBSITE_ID'));

        $this->indexer->resetIndex(null, ['website_id' => $this->getDefaultWebsiteId()]);

        $this->assertEntityCount(4, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(3, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }
}
