<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Search;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\WebsiteManagerTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item as ItemEntity;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ProductWebsiteSearchTest extends WebTestCase
{
    use WebsiteManagerTrait;

    private IndexerInterface $indexer;
    private EngineInterface $searchEngine;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->setCurrentWebsite('default');
        $this->indexer = $this->getContainer()->get('oro_website_search.indexer');
        $this->searchEngine = $this->getContainer()->get('oro_website_search.engine');

        $this->loadFixtures([
            LoadFrontendProductData::class,
        ]);
    }

    public function testPartialIndexationAfterFullIndexation()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $this->assertTrue($this->indexer->save($product));
        $this->assertResultData($product);
        $this->clearEM();

        $this->assertTrue($this->indexer->save($product, [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]));
        $this->assertResultData($product);
        $this->clearEM();

        $this->assertTrue($this->indexer->save($product, [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image']]));
        $this->assertResultData($product);
        $this->clearEM();
    }

    public function testPartialIndexationWithoutFullIndexation()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $this->assertTrue($this->indexer->save($product, [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image']]));
        $this->assertResultData($product);
    }

    private function assertResultData(Product $product): void
    {
        $query = new Query();

        $query->from(['oro_product_WEBSITE_ID'])
            ->addSelect('text.sku')
            ->addSelect('text.image_product_small')
            ->addSelect('integer.visibility_anonymous');
        $query->getCriteria()->andWhere(Criteria::expr()->eq('sku', $product->getSku()));

        $items = $this->searchEngine->search($query);
        $this->assertCount(1, $items);

        /** @var Item $result */
        $result = $items->first();
        $data = $result->getSelectedData();
        $this->assertEquals(1, $data['visibility_anonymous']);
        $this->assertEquals($product->getSku(), $data['sku']);
        $this->assertNotEmpty($data['image_product_small']);
    }

    /**
     * @return void
     */
    private function clearEM(): void
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ItemEntity::class);
        $em->clear();
    }

    /**
     * Need to make sure that partial indexation for all products does not remove them from the index
     */
    public function testPartialIndexationDoesNotRemoveTheData()
    {
        $productCount = $this->getProductsCount();

        $this->assertGreaterThan(0, $productCount);

        $this->getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [$this->getDefaultWebsiteId()], [], false, ['main']),
            ReindexationRequestEvent::EVENT_NAME
        );

        $this->assertEquals($productCount, $this->getProductsCount());
    }

    /**
     * @return int
     */
    private function getProductsCount()
    {
        $query = new Query();
        $query->from('oro_product_WEBSITE_ID');

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');
        return $searchEngine->search($query)->getRecordsCount();
    }
}
