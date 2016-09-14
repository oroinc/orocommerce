<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestInterface;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrmIndexerTest extends WebTestCase implements SearchTestInterface
{
    use SearchTestTrait;

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
        ]
    ];

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItemData::class]);

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

        $this->clearRestrictListeners();
    }

    /**
     * {@inheritdoc}
     */
    public function getRestrictEntityEventName()
    {
        $alias = $this->entityAliasResolver->getAlias(TestProduct::class);
        return sprintf('%s.%s', RestrictIndexEntityEvent::NAME, $alias);
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();

        //Remove listener to not to interract with other tests
        $this->dispatcher->removeListener(IndexEntityEvent::NAME, $this->listener);

        unset($this->doctrineHelper, $this->mappingProviderMock, $this->dispatcher, $this->indexer);
    }

    /**
     * @return Website
     */
    private function getDefaultWebsite()
    {
        return $this
            ->getDoctrine()
            ->getRepository('OroWebsiteBundle:Website')
            ->getDefaultWebsite();
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

    public function testReindexForSpecificWebsite()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);

        $this->listener = $this->setListener();
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsite()->getId()
            ]
        );

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_' . $this->getDefaultWebsite()->getId()]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());
    }

    public function testReindexWithRestriction()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);

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
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsite()->getId()
            ]
        );

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_' . $this->getDefaultWebsite()->getId()]);

        $this->assertCount(1, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
    }

    public function testReindexWithAllRestricted()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);

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
                AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsite()->getId()
            ]
        );

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_' . $this->getDefaultWebsite()->getId()]);

        $this->assertCount(0, $items);
    }

    public function testReindexOfAllWebsites()
    {
        $this->mappingProviderMock->expects($this->once())->method('getMappingConfig')
            ->willReturn($this->mappingConfig);

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
            'alias' => 'oro_product_' . $this->getDefaultWebsite()->getId()
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

    public function testDeleteWhenNonExistentEntityRemoved()
    {
        $this->mappingProviderMock
            ->expects($this->once())
            ->method('isClassSupported')
            ->willReturn(true);

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityAlias')
            ->willReturn('oro_product_WEBSITE_ID');

        $productMock = $this->getMockBuilder(TestProduct::class)
            ->getMock();

        $productMock
            ->method('getId')
            ->willReturn(123456);

        $this->indexer->delete($productMock, ['website_id' => $this->getDefaultWebsiteId()]);

        $this->assertEntityCount(4, Item::class);
        $this->assertEntityCount(2, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }

    public function testDeleteWhenEntityIdsArrayIsEmpty()
    {
        $this->mappingProviderMock
            ->expects($this->never())
            ->method('getEntityAlias');

        $this->indexer->delete([], ['website_id' => $this->getDefaultWebsiteId()]);

        $this->assertEntityCount(4, Item::class);
        $this->assertEntityCount(2, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }

    public function testDeleteWhenProductEntitiesForSpecificWebsiteRemoved()
    {
        $this->mappingProviderMock
            ->expects($this->any())
            ->method('isClassSupported')
            ->with(TestProduct::class)
            ->willReturn(true);

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityAlias')
            ->with(TestProduct::class)
            ->willReturn('oro_product_WEBSITE_ID');

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->indexer->delete(
            [
                $product1,
                $product2,
            ],
            ['website_id' => $this->getDefaultWebsiteId()]
        );

        $this->assertEntityCount(2, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(1, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testDeleteForSpecificWebsiteAndEntitiesWithoutMappingConfiguration()
    {
        $this->mappingProviderMock
            ->expects($this->exactly(4))
            ->method('isClassSupported')
            ->withConsecutive([TestProduct::class], [TestProduct::class], ['stdClass'], ['stdClass'])
            ->willReturnCallback(function ($class) {
                if ($class === TestProduct::class) {
                    return true;
                }

                return false;
            });

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityAlias')
            ->with(TestProduct::class)
            ->willReturn('oro_product_WEBSITE_ID');

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->indexer->delete(
            [
                $product1,
                $product2,
                new \stdClass(),
                new \stdClass()
            ],
            ['website_id' => $this->getDefaultWebsiteId()]
        );

        $this->assertEntityCount(2, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(1, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testDeleteWhenProductEntitiesForAllWebsitesRemoved()
    {
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

        $this->assertEntityCount(0, Item::class);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }
}
