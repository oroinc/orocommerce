<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionContentVariantWithManuallyAddedData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogEntityIndexerListener;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM\OrmIndexerTest;

class ManuallyAddedProductCollectionIndexerListenerTest extends FrontendWebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        OrmIndexerTest::checkSearchEngine($this);
        $this->setCurrentWebsite();
        $this->loadFixtures([LoadProductCollectionContentVariantWithManuallyAddedData::class]);
    }

    public function testOnWebsiteSearchIndex()
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $webCatalog = $this->getReference(LoadProductCollectionContentVariantWithManuallyAddedData::WEB_CATALOG);
        // set WebCatalog for current Website
        $configManager = self::getContainer()->get('oro_config.manager');
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $webCatalog->getId(),
            self::getContainer()->get('oro_website.manager')->getCurrentWebsite()
        );
        $configManager->flush();

        self::getContainer()->get('event_dispatcher')->dispatch(
            ReindexationRequestEvent::EVENT_NAME,
            new ReindexationRequestEvent([Product::class], [], [$product1->getId(), $product2->getId()], false)
        );

        $contentVariantWithFiltersId = $this
            ->getReference(LoadProductCollectionContentVariantWithManuallyAddedData::CONTENT_VARIANT_WITH_FILTERS)
            ->getId();
        $query = self::getContainer()->get('oro_product.website_search.repository.product')
            ->getSearchQuery('', 0, 10)
            ->addWhere(Criteria::expr()->eq(
                sprintf(
                    'integer.manually_added_to_%s_%s',
                    WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                    $contentVariantWithFiltersId
                ),
                1
            ));
        $results = $query->getResult();
        self::assertEquals(0, $results->getRecordsCount());

        $contentVariantWithManuallyAddedId = $this
            ->getReference(
                LoadProductCollectionContentVariantWithManuallyAddedData::CONTENT_VARIANT_WITH_MANUALLY_ADDED
            )
            ->getId();
        $query = self::getContainer()->get('oro_product.website_search.repository.product')
            ->getSearchQuery('', 0, 10)
            ->addWhere(Criteria::expr()->eq(
                sprintf(
                    'integer.manually_added_to_%s_%s',
                    WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                    $contentVariantWithManuallyAddedId
                ),
                1
            ));
        $results = $query->getResult();
        self::assertEquals(2, $results->getRecordsCount());
        $items = $results->getElements();
        self::assertEquals($product1->getId(), $items[0]->getRecordId());
        self::assertEquals($product2->getId(), $items[1]->getRecordId());

        $contentVariantWithMixedId = $this
            ->getReference(LoadProductCollectionContentVariantWithManuallyAddedData::CONTENT_VARIANT_WITH_MIXED)
            ->getId();
        $query = self::getContainer()->get('oro_product.website_search.repository.product')
            ->getSearchQuery('', 0, 10)
            ->addWhere(Criteria::expr()->eq(
                sprintf(
                    'integer.manually_added_to_%s_%s',
                    WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                    $contentVariantWithMixedId
                ),
                1
            ));
        $results = $query->getResult();
        self::assertEquals(1, $results->getRecordsCount());
        $items = $results->getElements();
        self::assertEquals($product2->getId(), $items[0]->getRecordId());
    }
}
