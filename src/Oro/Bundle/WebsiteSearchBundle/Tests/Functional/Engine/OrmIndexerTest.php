<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
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
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;

/**
 * @dbIsolationPerTest
 */
class OrmIndexerTest extends WebTestCase
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

    /** @var array */
    private $mappingConfig = [
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
        $this->loadFixtures([LoadItemData::class]);

        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');

        $this->mappingProviderMock = $this->getMockBuilder(WebsiteSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->indexer = new OrmIndexer($this->dispatcher, $this->doctrineHelper, $this->mappingProviderMock);
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();

        //Remove listener to not to interract with other tests
        if (null !== $this->listener) {
            $this->dispatcher->removeListener(IndexEntityEvent::NAME, $this->listener);
        }

        unset($this->doctrineHelper);
        unset($this->mappingProviderMock);
        unset($this->dispatcher);
        unset($this->indexer);
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
        $this->indexer->reindex(Product::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 1]);

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_website_1']);

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
            RestrictIndexEntityEvent::NAME,
            function (RestrictIndexEntityEvent $event) use ($restrictedProduct) {
                $qb = $event->getQueryBuilder();
                list($rootAlias) = $qb->getRootAliases();
                $qb->where($qb->expr()->neq($rootAlias . '.id', ':id'))
                    ->setParameter('id', $restrictedProduct->getId());
            },
            -255
        );

        $this->listener = $this->setListener();
        $this->indexer->reindex(Product::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => 1]);

        /** @var Item[] $items */
        $items = $this->getItemRepository()->findBy(['alias' => 'oro_product_website_1']);

        $this->assertCount(1, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
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
            'alias' => 'oro_product_website_' . $otherWebsite->getId()
        ]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());

        $defaultWebsite =$this->getDoctrine()->getRepository('OroWebsiteBundle:Website')->getDefaultWebsite();
        $items = $this->getItemRepository()->findBy([
            'alias' => 'oro_product_website_' . $defaultWebsite->getId()
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
        $this->indexer = new OrmIndexer($this->dispatcher, $this->doctrineHelper, $this->mappingProviderMock);
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

        $productMock = $this->getMockBuilder(Product::class)
            ->getMock();

        $productMock
            ->method('getId')
            ->willReturn(123456);

        $this->indexer->delete($productMock, ['website_id' => 1]);

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

        $this->indexer->delete([], ['website_id' => 1]);

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
            ->with(Product::class)
            ->willReturn(true);

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityAlias')
            ->with(Product::class)
            ->willReturn('oro_product_WEBSITE_ID');

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->indexer->delete(
            [
                $product1,
                $product2,
            ],
            ['website_id' => 1]
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
            ->withConsecutive([Product::class], [Product::class], ['stdClass'], ['stdClass'])
            ->willReturnCallback(function ($class) {
                if ($class === Product::class) {
                    return true;
                }

                return false;
            });

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityAlias')
            ->with(Product::class)
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
            ['website_id' => 1]
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
            ->with(Product::class)
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
