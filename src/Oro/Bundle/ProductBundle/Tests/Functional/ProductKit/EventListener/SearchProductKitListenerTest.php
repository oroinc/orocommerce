<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ProductKit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Assert\SentMessageConstraint;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\ProductKit\EventListener\SearchProductKitListener;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\SearchBundle\Engine\AbstractMapper;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class SearchProductKitListenerTest extends WebTestCase
{
    use SearchExtensionTrait;
    use MessageQueueExtension;

    private ?EngineInterface $engine = null;
    private ?AbstractMapper $mapper = null;
    private ?ManagerRegistry $registry = null;
    private ?SearchProductKitListener $listener = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->engine = self::getSearchEngine();
        $this->mapper = self::getSearchObjectMapper();
        $this->registry = self::getContainer()->get(ManagerRegistry::class);

        $serviceId = 'oro_product.event_listener.search_product_kit';
        $this->listener = self::getContainer()->get($serviceId);
        $this->getOptionalListenerManager()->enableListener($serviceId);
        $this->getOptionalListenerManager()
            ->disableListener('oro_product.event_listener.website_search_reindex_product_kit');

        $this->loadFixtures([LoadProductKitData::class]);
        self::reindex(Product::class, []);
    }

    #[\Override]
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::clearIndex(Product::class);
        self::clearTestData(Product::class);
    }

    public function testChangedProductKitItemLabel(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $previouslyLabel = 'PKSKU1 - Unit of Quantity Taken from';

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($previouslyLabel, $result[0]->getSelectedData()['all_text']);

        $kitItem = $kit1->getKitItems()->first();
        $label = $kitItem->getLabels()->first();
        $label->setString('Touch Screen Credit Cart');
        $this->registry->getManager()->flush();

        self::assertMessageIndexSent([$kit1->getId() => $kit1->getId()]);
        self::reindex(Product::class, []);

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringNotContainsString($previouslyLabel, $result[0]->getSelectedData()['all_text']);
        self::assertStringContainsString('Touch Screen Credit Cart', $result[0]->getSelectedData()['all_text']);
    }

    public function testChangedProductKitItemProduct(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kitItem = $kit1->getKitItems()->first();
        $kitItemProduct = $kitItem->getKitItemProducts()->first();
        $sku = $kitItemProduct->getProduct()->getSku();

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($sku, $result[0]->getSelectedData()['all_text']);

        $productSimple = $this->getReference(LoadProductData::PRODUCT_3);
        $kitItemProduct->setProduct($productSimple);
        $this->registry->getManager()->flush();

        self::assertMessageIndexSent([$kit1->getId() => $kit1->getId()]);
        self::reindex(Product::class, []);

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringNotContainsString($sku, $result[0]->getSelectedData()['all_text']);
        self::assertStringContainsString($productSimple->getSku(), $result[0]->getSelectedData()['all_text']);
    }

    public function testChangedProductKitItem(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kitItem = $kit1->getKitItems()->first();
        $kitItem->setSortOrder(10);

        $this->registry->getManager()->flush();

        self::assertMessageIndexSent([$kit1->getId() => $kit1->getId()]);
    }

    public function testAddNewEntity(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringNotContainsString($product2->getSku(), $result[0]->getSelectedData()['all_text']);

        $unit = $this->getReference(LoadProductUnits::MILLILITER);
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setUnit($unit);
        $productUnitPrecision->setPrecision(0);

        $kitItemProduct = new ProductKitItemProduct();
        $kitItemProduct->setProduct($product2);
        $kitItemProduct->setProductUnitPrecision($productUnitPrecision);

        $kitItem = new ProductKitItem();
        $kitItem->setDefaultLabel('Test label');
        $kitItem->setProductKit($kit1);
        $kitItem->setProductUnit($unit);
        $kitItem->addKitItemProduct($kitItemProduct);
        $kit1->addKitItem($kitItem);

        $this->registry->getManager()->persist($kitItemProduct);
        $this->registry->getManager()->persist($kitItem);
        $this->registry->getManager()->flush();

        self::assertMessageIndexSent([$kit1->getId() => $kit1->getId()]);
        self::reindex(Product::class, []);

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($product2->getSku(), $result[0]->getSelectedData()['all_text']);
    }

    public function testRemoveEntity(): void
    {
        $kit2 = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $result = $this->executeProductQuery($kit2->getSku());
        self::assertStringContainsString('PKSKU2 - Sort Order 1', $result[0]->getSelectedData()['all_text']);

        $kit2->getKitItems()->remove(0);
        $this->registry->getManager()->flush();

        self::assertMessageIndexSent([$kit2->getId() => $kit2->getId()]);
        self::reindex(Product::class, []);

        $result = $this->executeProductQuery($kit2->getSku());
        self::assertStringNotContainsString('PKSKU2 - Sort Order 1', $result[0]->getSelectedData()['all_text']);
    }

    public function testChangedProductSimpleNotRelatedToProductKit(): void
    {
        $product6 = $this->getReference(LoadProductData::PRODUCT_6);
        $product6->setSku('Test_Simple_Sku_Reindex');

        $this->registry->getManager()->flush();

        self::assertMessageIndexSent([$product6->getId() => $product6->getId()]);
    }

    public function testNotApplicablePrepareEntityMapEvent(): void
    {
        /** @var Product $product6 */
        $product6 = $this->getReference(LoadProductData::PRODUCT_6);
        $data = $this->mapper->mapObject($product6);
        $name = (string)$product6->getDefaultName();

        $expected = [
            'integer' => [
                'system_entity_id' => $product6->getId(),
                'organization' => $product6->getOrganization()->getId(),
                'oro_product_owner' =>  $product6->getOwner()->getId()
            ],
            'text' => [
                'system_entity_name' => $name,
                'sku' => $product6->getSku(),
                'defaultName' => $name,
                'names' => $name,
                'defaultDescription' => (string)$product6->getDefaultDescription(),
                'defaultShortDescription' => (string)$product6->getDefaultShortDescription(),
                'shortDescriptions' => (string)$product6->getDefaultShortDescription(),
                'all_text' => 'product-6.names.default product 6 names default product-6 product-6.descriptions.default'
                    .' descriptions product-6.shortDescriptions.default shortDescriptions'
            ]
        ];

        self::assertEquals($expected, $data);
    }

    public function testPrepareEntityMapEvent(): void
    {
        /** @var Product $kit1 */
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $data = $this->mapper->mapObject($kit1);
        $name = (string)$kit1->getDefaultName();

        $expected = [
            'integer' => [
                'system_entity_id' => $kit1->getId(),
                'organization' => $kit1->getOrganization()->getId(),
                'oro_product_owner' =>  $kit1->getOwner()->getId()
            ],
            'text' => [
                'system_entity_name' => $name,
                'sku' => $kit1->getSku(),
                'defaultName' => $name,
                'names' => $name,
                'all_text' => 'PKSKU1 - Unit of Quantity Taken from Product Kit product-1.names.default product 1 names'
                    .' default product-1 product-1.names.en_CA en CA product-1.descriptions.default descriptions'
                    .' product-1.shortDescriptions.default shortDescriptions product-1.shortDescriptions.en_CA with'
                    .' Single Item product-kit-1 kit'
            ]
        ];

        self::assertEquals($expected, $data);
    }

    public function testDisabledPrepareEntityMapEvent(): void
    {
        $this->listener->setEnabled(false);

        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $data = $this->mapper->mapObject($kit1);
        $name = (string)$kit1->getDefaultName();

        $expected = [
            'integer' => [
                'system_entity_id' => $kit1->getId(),
                'organization' => $kit1->getOrganization()->getId(),
                'oro_product_owner' =>  $kit1->getOwner()->getId()
            ],
            'text' => [
                'system_entity_name' => $name,
                'sku' => $kit1->getSku(),
                'defaultName' => $name,
                'names' => $name,
                'all_text' => 'Product Kit with Single Item product-kit-1 product kit 1'
            ]
        ];

        self::assertEquals($expected, $data);
    }

    public function testBeforeIndexEntities(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kit2 = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product1->setSku('Product_Simple_1');

        $ids = [
            $product1->getId() => $product1->getId(),
            $kit1->getId() => $kit1->getId(),
            $kit2->getId() => $kit2->getId(),
            $kit3->getId() => $kit3->getId(),
        ];

        $this->registry->getManager()->flush();
        self::assertMessageIndexSent($ids);
        self::reindex(Product::class, []);

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($product1->getSku(), $result[0]->getSelectedData()['all_text']);
    }

    public function testDisabledBeforeIndexEntities(): void
    {
        $this->listener->setEnabled(false);

        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product1->setSku('Product_Simple_1');

        $this->registry->getManager()->flush();

        self::assertMessageIndexSent([$product1->getId() => $product1->getId()]);
        self::reindex(Product::class, []);

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringNotContainsString($product1->getSku(), $result[0]->getSelectedData()['all_text']);
    }

    private static function assertMessageIndexSent(array $entityIds): void
    {
        $actual = self::getTopicSentMessages(IndexEntitiesByIdTopic::getName());

        $expected = [
            'topic' => IndexEntitiesByIdTopic::getName(),
            'message' => ['class' => Product::class, 'entityIds' => $entityIds]
        ];

        self::assertThat($actual, new SentMessageConstraint($expected, false, false));
    }

    private function executeProductQuery(string $sku): ?array
    {
        $alias = self::getIndexAlias(Product::class, []);

        $query = new Query();
        $query->from($alias);
        $query->addSelect('all_text');
        $query->getCriteria()->andWhere(Criteria::expr()->eq('sku', $sku));

        $result = $this->engine->search($query);
        return $result->getElements();
    }
}
