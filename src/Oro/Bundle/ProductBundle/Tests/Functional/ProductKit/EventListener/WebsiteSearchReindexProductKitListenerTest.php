<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ProductKit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\ProductKit\EventListener\WebsiteSearchReindexProductKitListener;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @dbIsolationPerTest
 */
class WebsiteSearchReindexProductKitListenerTest extends WebTestCase
{
    use WebsiteSearchExtensionTrait;
    use MessageQueueExtension;

    private ?EngineInterface $engine = null;
    private ?ManagerRegistry $registry = null;
    private ?WebsiteSearchReindexProductKitListener $listener = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->engine = self::getSearchEngine();
        $this->registry = self::getContainer()->get(ManagerRegistry::class);

        $serviceId = 'oro_product.event_listener.website_search_reindex_product_kit';
        $this->listener = self::getContainer()->get($serviceId);
        $this->getOptionalListenerManager()->enableListener($serviceId);
        $this->getOptionalListenerManager()->disableListener('oro_product.event_listener.search_product_kit');

        $this->loadFixtures([LoadProductKitData::class]);
        self::reindexProductData();
    }

    #[\Override]
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::clearIndex(Product::class, [WebsiteIdPlaceholder::NAME => self::getDefaultWebsiteId()]);
        self::clearTestData(Product::class);
    }

    public function testChangedProductKitItemLabel(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $previouslyLabel = 'PKSKU1 - Unit of Quantity Taken from';

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($previouslyLabel, $result[0]->getSelectedData()['all_text_1']);

        $kitItem = $kit1->getKitItems()->first();
        $label = $kitItem->getLabels()->first();
        $label->setString('Touch Screen Credit Cart');
        $this->registry->getManager()->flush();

        self::assertMessageReindexSent([$kit1->getId()]);
        self::reindexProductData();

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringNotContainsString($previouslyLabel, $result[0]->getSelectedData()['all_text_1']);
        self::assertStringContainsString('Touch Screen Credit Cart', $result[0]->getSelectedData()['all_text_1']);
    }

    public function testChangedProductKitItemProduct(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kitItem = $kit1->getKitItems()->first();
        $kitItemProduct = $kitItem->getKitItemProducts()->first();
        $sku = $kitItemProduct->getProduct()->getSku();

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($sku, $result[0]->getSelectedData()['all_text_1']);

        $productSimple = $this->getReference(LoadProductData::PRODUCT_3);
        $kitItemProduct->setProduct($productSimple);
        $this->registry->getManager()->flush();

        self::assertMessageReindexSent([$kit1->getId()]);
        self::reindexProductData();

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringNotContainsString($sku, $result[0]->getSelectedData()['all_text_1']);
        self::assertStringContainsString($productSimple->getSku(), $result[0]->getSelectedData()['all_text_1']);
    }

    public function testChangedProductKitItem(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kitItem = $kit1->getKitItems()->first();
        $kitItem->setSortOrder(10);

        $this->registry->getManager()->flush();

        self::assertMessageReindexSent([$kit1->getId()]);
    }

    public function testAddNewEntity(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringNotContainsString($product2->getSku(), $result[0]->getSelectedData()['all_text_1']);

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

        self::assertMessageReindexSent([$kit1->getId()]);
        self::reindexProductData();

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($product2->getSku(), $result[0]->getSelectedData()['all_text_1']);
    }

    public function testRemoveEntity(): void
    {
        $kit2 = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $result = $this->executeProductQuery($kit2->getSku());
        self::assertStringContainsString('PKSKU2 - 1', $result[0]->getSelectedData()['all_text_1']);

        $kit2->getKitItems()->remove(0);
        $this->registry->getManager()->flush();

        self::assertMessageReindexSent([$kit2->getId()]);
        self::reindexProductData();

        $result = $this->executeProductQuery($kit2->getSku());
        self::assertStringNotContainsString('PKSKU2 - 1', $result[0]->getSelectedData()['all_text_1']);
    }

    public function testChangedProductSimpleNotRelatedToProductKit(): void
    {
        $product6 = $this->getReference(LoadProductData::PRODUCT_6);
        $product6->setSku('Test_Simple_Sku_Reindex');

        $this->registry->getManager()->flush();

        self::assertMessageReindexSent([$product6->getId()]);
    }

    public function testDisabledReindexListener(): void
    {
        $this->listener->setEnabled(false);

        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kitItem = $kit1->getKitItems()->first();
        $kitItem->setSortOrder(10);

        $this->registry->getManager()->flush();

        self::assertMessagesEmpty(WebsiteSearchReindexTopic::getName());
    }

    public function testCachedReindexListener(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $oldSku = $product1->getSku();
        $product1->setSku('Test_Cached_Sku_Reindex');

        ReflectionUtil::setPropertyValue($this->listener, 'cachedIds', [$product1->getId()]);

        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [$product1->getId()], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($oldSku, $result[0]->getSelectedData()['all_text_1']);
        self::assertStringNotContainsString($product1->getSku(), $result[0]->getSelectedData()['all_text_1']);
    }

    public function testNotProductEntityReindex(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $oldSku = $product1->getSku();
        $product1->setSku('Test_Not_Product_Sku_Reindex');

        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Brand::class], [], [1], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        $result = $this->executeProductQuery($kit1->getSku());
        self::assertStringContainsString($oldSku, $result[0]->getSelectedData()['all_text_1']);
        self::assertStringNotContainsString($product1->getSku(), $result[0]->getSelectedData()['all_text_1']);
    }

    private static function assertMessageReindexSent(array $entityIds): void
    {
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            [
                'class' => [Product::class],
                'granulize' => true,
                'context' => [
                    'websiteIds' => [self::getDefaultWebsiteId()],
                    'entityIds' => $entityIds
                ]
            ]
        );
    }

    private function executeProductQuery(string $sku): ?array
    {
        $alias = self::getIndexAlias(Product::class, [WebsiteIdPlaceholder::NAME => self::getDefaultWebsiteId()]);

        $query = new Query();
        $query->from($alias);
        $query->addSelect('all_text_LOCALIZATION_ID');
        $query->getCriteria()->andWhere(Criteria::expr()->eq('sku', $sku));

        $result = $this->engine->search($query);
        return $result->getElements();
    }
}
