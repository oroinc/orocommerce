<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectDependentClassesEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadEmployeesToIndex;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadOtherWebsite;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractSearchWebTestCase extends WebTestCase
{
    use DefaultLocalizationIdTestTrait;
    use DefaultWebsiteIdTestTrait;
    use SearchExtensionTrait;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AbstractSearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $mappingProvider;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var callable */
    protected $listener;

    /** @var AbstractIndexer */
    protected $indexer;

    /** @var array */
    private $mappingConfig = [
        TestProduct::class => [
            'alias' => 'oro_product_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'names_LOCALIZATION_ID',
                    'type' => 'text',
                ],
            ],

        ],
        TestEmployee::class => [
            'alias' => 'oro_employee_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'names_LOCALIZATION_ID',
                    'type' => 'text',
                ],
            ],
        ],
    ];

    /**
     * @param array $options
     *
     * @return Item[]
     */
    abstract protected function getResultItems(array $options): array;

    abstract protected function assertItemsCount(int $expectedCount): void;

    /** Check for engine before initialization but after init client */
    abstract protected function preSetUp(): void;

    /** Check for engine after test execution */
    abstract protected function preTearDown(): void;

    protected function setUp(): void
    {
        $this->initClient();

        $this->preSetUp();

        $this->ensureSessionIsAvailable();

        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');

        $this->entityAliasResolver = $this->getContainer()->get('oro_entity.entity_alias_resolver');

        $this->clearRestrictListeners($this->getRestrictEntityEventName());
    }

    protected function getRestrictEntityEventName(): string
    {
        return sprintf('%s.%s', RestrictIndexEntityEvent::NAME, 'testproduct');
    }

    protected function tearDown(): void
    {
        $this->preTearDown();

        // Remove listener to not interact with other tests
        $this->dispatcher->removeListener(IndexEntityEvent::NAME, $this->listener);

        $this->clearIndexTextTable(IndexText::class);
    }

    protected function setListener(): void
    {
        $listener = function (IndexEntityEvent $event) {
            /** @var TestProduct[] $entities */
            $entities = $event->getEntities();
            foreach ($entities as $entity) {
                $event->addPlaceholderField(
                    $entity->getId(),
                    'names_LOCALIZATION_ID',
                    sprintf('Reindexed product %s', $entity->getId()),
                    [LocalizationIdPlaceholder::NAME => $this->getDefaultLocalizationId()]
                );
            }
        };

        $this->dispatcher->addListener(IndexEntityEvent::NAME, $listener, -255);

        $this->listener = $listener;
    }

    protected function clearRestrictListeners(string $eventName): void
    {
        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            $this->dispatcher->removeListener($eventName, $listener);
        }
    }

    protected function setClassSupportedExpectation(string $class, bool $return): void
    {
        $this->mappingProvider->expects($this->any())
            ->method('isClassSupported')
            ->with($class)
            ->willReturn($return);
    }

    protected function setEntityAliasExpectation(): void
    {
        $this->mappingProvider->expects($this->any())
            ->method('getEntityAlias')
            ->willReturnCallback(function ($class) {
                return $this->mappingConfig[$class]['alias'];
            });
    }

    protected function setGetEntityConfigExpectation(): void
    {
        $this->mappingProvider->expects($this->any())
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

        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[0]));
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[1]));
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

        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 1]);

        $this->assertCount(1, $items);
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[0]));
    }

    public function testReindexForSpecificWebsiteWithDependentEntities()
    {
        $this->loadFixtures([LoadEmployeesToIndex::class, LoadProductsToIndex::class]);

        $collectDependentClassesListener = function (CollectDependentClassesEvent $event) {
            $event->addClassDependencies(TestEmployee::class, [TestProduct::class]);
        };

        $this->dispatcher->addListener(CollectDependentClassesEvent::NAME, $collectDependentClassesListener, -255);

        $this->mappingProvider->expects($this->any())
            ->method('isClassSupported')
            ->willReturn(true);

        $this->setEntityAliasExpectation();

        $this->setGetEntityConfigExpectation();

        $this->setListener();

        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[0]));
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[1]));

        $items = $this->getResultItems(['alias' => 'oro_employee_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[0]));
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[1]));
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

        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[0]));
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[1]));
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
                [$rootAlias] = $qb->getRootAliases();
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

        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 1]);

        $this->assertCount(1, $items);
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[0]));
    }

    public function testReindexWithAllRestricted()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);

        $this->setEntityAliasExpectation();
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setGetEntityConfigExpectation();

        $restrictedProduct1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $restrictedProduct2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $restrictedIds = [$restrictedProduct1->getId(), $restrictedProduct2->getId()];

        $this->dispatcher->addListener(
            $this->getRestrictEntityEventName(),
            function (RestrictIndexEntityEvent $event) use ($restrictedIds) {
                $qb = $event->getQueryBuilder();
                [$rootAlias] = $qb->getRootAliases();
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

        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId()]);

        $this->assertCount(0, $items);
    }

    public function testReindexOfAllWebsites()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProvider->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn([TestProduct::class]);

        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();

        $this->setListener();
        $this->indexer->reindex();

        $otherWebsite = $this->getReference(LoadOtherWebsite::REFERENCE_OTHER_WEBSITE);
        $items = $this->getResultItems(['alias' => 'oro_product_' . $otherWebsite->getId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[0]));
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[1]));

        $items = $this->getResultItems(['alias' => 'oro_product_' . $this->getDefaultWebsiteId(), 'items_count' => 2]);

        $this->assertCount(2, $items);
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[0]));
        static::assertStringContainsString('Reindexed product', $this->getItemName($items[1]));
    }

    public function testWrongMappingException()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->setClassSupportedExpectation(TestActivity::class, false);
        $this->indexer->reindex(TestActivity::class, []);
    }

    public function testSaveForNotSupportedEntity()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->loadFixtures([LoadOtherWebsite::class, LoadProductsToIndex::class]);

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
        $this->loadFixtures([LoadOtherWebsite::class, LoadProductsToIndex::class]);
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
        $this->loadFixtures([LoadOtherWebsite::class, LoadProductsToIndex::class]);

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->setListener();
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();

        $this->indexer->save([$product1, $product2]);

        $this->assertItemsCount(4);
    }

    public function testReindexException()
    {
        $this->expectException(\LogicException::class);

        $this->indexer->reindex(
            ['class1', 'class2'],
            [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2]
            ]
        );
    }

    protected function getItemName(Item $item) : string
    {
        $fieldName = 'names_' . $this->getDefaultLocalizationId();

        foreach ($item->getTextFields() as $textField) {
            if ($textField->getField() === $fieldName) {
                return $textField->getValue();
            }
        }

        return '';
    }
}
