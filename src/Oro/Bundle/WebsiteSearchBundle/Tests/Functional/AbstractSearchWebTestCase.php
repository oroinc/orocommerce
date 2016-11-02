<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectDependentClassesEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Resolver\EntityDependenciesResolver;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadEmployeesToIndex;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadOtherWebsite;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractSearchWebTestCase extends WebTestCase
{
    use DefaultLocalizationIdTestTrait;
    use DefaultWebsiteIdTestTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var WebsiteSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mappingProviderMock;

    /**
     * @var EntityAliasResolver
     */
    protected $entityAliasResolver;

    protected $listener;

    /** @var AbstractIndexer */
    protected $indexer;


    /** @var array */
    private $mappingConfig = [
        TestProduct::class => [
            'alias' => 'oro_product_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'name_LOCALIZATION_ID',
                    'type' => 'text',
                ],
            ],

        ],
        TestEmployee::class => [
            'alias' => 'oro_employee_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'name_LOCALIZATION_ID',
                    'type' => 'text',
                ],
            ],
        ],
    ];

    /**
     * @return AbstractIndexer
     */
    abstract protected function getIndexer(
        DoctrineHelper $doctrineHelper,
        WebsiteSearchMappingProvider $mappingProvider,
        EntityDependenciesResolver $entityDependenciesResolver,
        IndexDataProvider $indexDataProvider,
        PlaceholderInterface $placeholder
    );

    /**
     * @param array $options
     * @return Item[]
     */
    abstract protected function getResultItems(array $options);

    /**
     * @param int $expectedCount
     */
    abstract protected function assertItemsCount($expectedCount);

    /** Check for engine before initialization but after init client */
    abstract protected function preSetUp();

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->preSetUp();

        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $this->mappingProviderMock = $this->getMockBuilder(WebsiteSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityAliasResolver = $this->getContainer()->get('oro_entity.entity_alias_resolver');

        $this->indexer = $this->getIndexer(
            $this->doctrineHelper,
            $this->mappingProviderMock,
            $this->getContainer()->get('oro_website_search.engine.entity_dependencies_resolver'),
            $this->getContainer()->get('oro_website_search.engine.index_data'),
            $this->getContainer()->get('oro_website_search.placeholder_decorator')
        );

        $this->clearRestrictListeners($this->getRestrictEntityEventName());
    }

    /**
     * @return string
     */
    protected function getRestrictEntityEventName()
    {
        return sprintf('%s.%s', RestrictIndexEntityEvent::NAME, 'testproduct');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        //Remove listener to not interact with other tests
        $this->dispatcher->removeListener(IndexEntityEvent::NAME, $this->listener);
    }

    protected function setListener()
    {
        $listener = function (IndexEntityEvent $event) {
            /** @var TestProduct[] $entities */
            $entities = $event->getEntities();
            foreach ($entities as $entity) {
                $event->addPlaceholderField(
                    $entity->getId(),
                    'name_LOCALIZATION_ID',
                    sprintf('Reindexed product %s', $entity->getId()),
                    [LocalizationIdPlaceholder::NAME => $this->getDefaultLocalizationId()]
                );
            }
        };

        $this->dispatcher->addListener(
            IndexEntityEvent::NAME,
            $listener,
            -255
        );

        $this->listener = $listener;
    }

    /**
     * @param string $eventName
     */
    protected function clearRestrictListeners($eventName)
    {
        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            $this->dispatcher->removeListener($eventName, $listener);
        }
    }

    /**
     * @param string $class
     * @param bool $return
     */
    protected function setClassSupportedExpectation($class, $return)
    {
        $this->mappingProviderMock
            ->expects($this->any())
            ->method('isClassSupported')
            ->with($class)
            ->willReturn($return);
    }

    protected function setEntityAliasExpectation()
    {
        $this->mappingProviderMock
            ->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnCallback(function ($class) {
                return $this->mappingConfig[$class]['alias'];
            });
    }

    protected function setGetEntityConfigExpectation()
    {
        $this->mappingProviderMock
            ->expects($this->any())
            ->method('getEntityConfig')
            ->willReturnCallback(function ($class) {
                return $this->mappingConfig[$class];
            });
    }

    public function testReindexForSpecificWebsite()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();
        $this->setListener();

        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());
    }

    public function testReindexForContextEntityIds()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();
        $this->setListener();
        $productId = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2)->getId();

        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [$productId],
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 1]);

        $this->assertCount(1, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
    }

    public function testReindexForSpecificWebsiteWithDependentEntities()
    {
        $this->loadFixtures([LoadEmployeesToIndex::class]);
        $this->loadFixtures([LoadProductsToIndex::class]);

        $collectDependentClassesListener = function (CollectDependentClassesEvent $event) {
            $event->addClassDependencies(TestEmployee::class, [TestProduct::class]);
        };

        $this->dispatcher->addListener(CollectDependentClassesEvent::NAME, $collectDependentClassesListener, -255);

        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();

        $this->setGetEntityConfigExpectation();

        $this->setListener();

        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());

        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_employee_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());
    }

    public function testReindexForSpecificWebsiteWithCustomBatchSize()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setEntityAliasExpectation();
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setGetEntityConfigExpectation();
        $this->indexer->setBatchSize(2);
        $this->setListener();
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());
    }

    public function testReindexWithRestriction()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();

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

        $this->setListener();
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 1]);

        $this->assertCount(1, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
    }

    public function testReindexWithAllRestricted()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setGetEntityConfigExpectation();

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

        $this->setListener();
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId()]);

        $this->assertCount(0, $items);
    }

    public function testResetIndexForSpecificClass()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();

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

        $this->setListener();
        $this->indexer->reindex(
            TestProduct::class,
            [

                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 1]);

        $this->assertCount(1, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
    }

    public function testReindexOfAllWebsites()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn([TestProduct::class]);

        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();

        $this->setListener();
        $this->indexer->reindex();

        $otherWebsite = $this->getReference(LoadOtherWebsite::REFERENCE_OTHER_WEBSITE);
        /** @var Item[] $items */
        $items = $this->getResultItems(['alias' => 'oro_product_' . $otherWebsite->getId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());

        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        $this->assertContains('Reindexed product', $items[0]->getTitle());
        $this->assertContains('Reindexed product', $items[1]->getTitle());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage There is no such entity in mapping config.
     */
    public function testWrongMappingException()
    {
        $this->setClassSupportedExpectation('stdClass', false);
        $this->indexer->reindex(\stdClass::class, []);
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

        $this->setListener();
        $this->setClassSupportedExpectation(TestProduct::class, false);
        $this->indexer->save([$product1, $product2]);
    }

    public function testGetClassesForReindex()
    {
        $listener = function (CollectDependentClassesEvent $event) {
            $event->addClassDependencies('Product', ['Category', 'User']);
        };

        $this->dispatcher->addListener(
            CollectDependentClassesEvent::NAME,
            $listener,
            -255
        );

        $this->assertEquals(['Category', 'Product'], $this->indexer->getClassesForReindex('Category'));
        $this->assertEquals(['User', 'Product'], $this->indexer->getClassesForReindex('User'));
        $this->assertEquals(['Product'], $this->indexer->getClassesForReindex('Product'));
    }

    public function testSaveForSingleEntityAndSingleWebsite()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();
        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $this->setListener();

        $this->indexer->save(
            $product1,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        $this->assertItemsCount(1);
    }

    public function testSaveForSingleEntityAndAllWebsites()
    {
        $this->loadFixtures([LoadOtherWebsite::class]);
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $this->setListener();

        $this->indexer->save($product1);

        $this->assertItemsCount(2);
    }

    public function testSaveForSeveralEntitiesAndSingleWebsite()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);
        $this->setListener();
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setGetEntityConfigExpectation();
        $this->setEntityAliasExpectation();

        $this->indexer->save(
            [$product1, $product2],
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );
        $this->assertItemsCount(2);
    }

    public function testSaveForSeveralEntitiesAndAllWebsites()
    {
        $this->loadFixtures([LoadOtherWebsite::class]);
        $this->loadFixtures([LoadProductsToIndex::class]);

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->setListener();
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();

        $this->indexer->save([$product1, $product2]);

        $this->assertItemsCount(4);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Entity ids passed into context. Please provide single class of entity
     */
    public function testReindexException()
    {
        $this->indexer->reindex(
            ['class1', 'class2'],
            [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2]
            ]
        );
    }
}
