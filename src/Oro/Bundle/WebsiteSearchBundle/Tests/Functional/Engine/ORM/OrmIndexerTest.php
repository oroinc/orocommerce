<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\AbstractSearchWebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadProductsToIndex;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrmIndexerTest extends AbstractSearchWebTestCase
{
    /** @var OrmIndexer */
    protected $indexer;

    public static function checkSearchEngine(WebTestCase $webTestCase)
    {
        $engine = $webTestCase->getContainer()
            ->get('oro_website_search.engine.parameters')
            ->getEngineName();
        if ($engine !== 'orm') {
            $webTestCase->markTestSkipped('Should be tested only with ORM engine');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function preSetUp(): void
    {
        $this->checkEngine();
    }

    /**
     * {@inheritDoc}
     */
    protected function preTearDown(): void
    {
        $this->checkEngine();
    }

    private function checkEngine()
    {
        self::checkSearchEngine($this);
    }

    /**
     * {@inheritDoc}
     */
    protected function getResultItems(array $options): array
    {
        return $this->getRepository(Item::class)->findBy(['alias' => $options['alias']]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mappingProvider = $this->createMock(SearchMappingProvider::class);

        $websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $websiteProvider->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturnCallback(function () {
                return $this->doctrineHelper->getEntityRepositoryForClass(Website::class)->getWebsiteIdentifiers();
            });

        $inputValidator = new IndexerInputValidator(
            $websiteProvider,
            $this->mappingProvider,
            self::getContainer()->get('doctrine'),
            $this->getContainer()->get('oro_website_search.reindexation_website_provider'),
            $this->getContainer()->get('oro_security.token_accessor'),
        );

        $this->indexer = new OrmIndexer(
            $this->doctrineHelper,
            $this->mappingProvider,
            $this->getContainer()->get('oro_website_search.engine.entity_dependencies_resolver'),
            $this->getContainer()->get('oro_website_search.engine.text_filtered_index_data'),
            $this->getContainer()->get('oro_website_search.placeholder_decorator'),
            $inputValidator,
            $this->getContainer()->get('event_dispatcher'),
            $this->getContainer()->get('oro_website_search.regex_placeholder_decorator')
        );

        $this->indexer->setDriver($this->getContainer()->get('oro_website_search.engine.orm.driver'));
    }

    protected function tearDown(): void
    {
        $this->clearIndexTextTable(IndexText::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function assertItemsCount(int $expectedCount): void
    {
        $this->assertEntityCount($expectedCount, Item::class);
    }

    private function assertEntityCount(int $expectedCount, string $class)
    {
        $repository = $this->getRepository($class);
        $actualCount = $this->makeCountQuery($repository);

        $this->assertEquals($expectedCount, $actualCount);
    }

    private function makeCountQuery(EntityRepository $repository): mixed
    {
        return $repository->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getRepository(string $entity): EntityRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository($entity, 'search');
    }

    public function testResetIndexOfCertainClass()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->indexer->resetIndex(TestProduct::class);

        $this->assertItemsCount(4);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(4, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    public function testDeleteWhenNonExistentEntityRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->setClassSupportedExpectation(TestDepartment::class, false);
        $testEntity = new TestDepartment();
        $testEntity->setId(123456);

        $this->indexer->delete($testEntity, [
            AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
        ]);

        $this->assertItemsCount(8);
        $this->assertEntityCount(6, IndexInteger::class);
        $this->assertEntityCount(6, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }

    public function testDeleteWhenEntityIdsArrayIsEmpty()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProvider->expects($this->never())
            ->method('getEntityAlias');

        $this->indexer->delete([], [AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]]);

        $this->assertItemsCount(8);
        $this->assertEntityCount(6, IndexInteger::class);
        $this->assertEntityCount(6, IndexText::class);
        $this->assertEntityCount(2, IndexDatetime::class);
        $this->assertEntityCount(2, IndexDecimal::class);
    }

    public function testDeleteWhenProductEntitiesForSpecificWebsiteRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProvider->expects($this->any())
            ->method('isClassSupported')
            ->with(TestProduct::class)
            ->willReturn(true);
        $this->setEntityAliasExpectation();

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);
        $this->assertItemsCount(8);

        $this->indexer->delete(
            [
                $product1,
                $product2,
            ],
            [AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]]
        );

        $this->assertItemsCount(6);
        $this->assertEntityCount(3, IndexInteger::class);
        $this->assertEntityCount(5, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testDeleteWhenProductEntitiesForSpecificWebsiteRemovedWithABatch()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProvider->expects($this->any())
            ->method('isClassSupported')
            ->with(TestProduct::class)
            ->willReturn(true);
        $this->setEntityAliasExpectation();

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);
        $this->assertItemsCount(8);
        $this->indexer->setBatchSize(1);
        $this->indexer->delete(
            [
                $product1,
                $product2,
            ],
            [AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]]
        );

        $this->assertItemsCount(6);
        $this->assertEntityCount(3, IndexInteger::class);
        $this->assertEntityCount(5, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testDeleteWhenProductEntitiesForAllWebsitesRemoved()
    {
        $this->loadFixtures([LoadItemData::class]);
        $this->mappingProvider->expects($this->any())
            ->method('isClassSupported')
            ->with(TestProduct::class)
            ->willReturn(true);

        $this->setEntityAliasExpectation();

        $product1 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $product2 = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2);

        $this->indexer->delete(
            [
                $product1,
                $product2,
            ],
            []
        );

        $this->assertItemsCount(4);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(4, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    public function testResetWholeIndex()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->indexer->resetIndex();

        $this->assertItemsCount(0);
        $this->assertEntityCount(0, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    public function testReindexForDeletedEntity()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->mappingProvider->expects($this->any())
            ->method('isClassSupported')
            ->with(TestProduct::class)
            ->willReturn(true);

        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();
        $this->setListener();
        $this->indexer->reindex(TestProduct::class);
        $removedProduct = $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT1);
        $removedProductId = $removedProduct->getId();
        $em = $this->doctrineHelper->getEntityManagerForClass(TestProduct::class);
        $em->remove($removedProduct);
        $em->flush();
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()],
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [
                    $removedProductId,
                    $this->getReference(LoadProductsToIndex::REFERENCE_PRODUCT2)->getId()
                ]
            ]
        );
        $this->assertItemsCount(1);
    }

    public function testResetIndexForCertainWebsite()
    {
        $this->loadFixtures([LoadItemData::class]);

        $this->mappingProvider->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn([TestProduct::class, TestEmployee::class]);

        $this->setEntityAliasExpectation();

        $context = [AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]];
        $this->assertItemsCount(8);
        $this->indexer->resetIndex(null, $context);
        $this->assertItemsCount(4);
    }

    public function testReindexForNotExistingWebsite()
    {
        $this->loadFixtures([LoadProductsToIndex::class]);
        $this->setClassSupportedExpectation(TestProduct::class, true);
        $this->setEntityAliasExpectation();
        $this->setGetEntityConfigExpectation();
        $this->setListener();

        $this->mappingProvider->expects($this->any())
            ->method('getEntityClasses')
            ->willReturn([TestProduct::class]);

        $notExistingId = 77777;
        $this->indexer->reindex(
            TestProduct::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$notExistingId]
            ]
        );

        $items = $this->getResultItems(['alias' => 'oro_product_'.  $notExistingId]);

        $this->assertCount(0, $items);
    }
}
