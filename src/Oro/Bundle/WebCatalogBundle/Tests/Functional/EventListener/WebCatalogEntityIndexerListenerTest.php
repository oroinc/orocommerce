<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogEntityIndexerListener;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogWithContentNodes;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\HttpFoundation\Request;

class WebCatalogEntityIndexerListenerTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?int $initialWebCatalogId;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->setCurrentWebsite();
        $this->loadFixtures([LoadWebCatalogWithContentNodes::class]);
        self::getContainer()->get('request_stack')->push(Request::create(''));

        $configManager = self::getConfigManager();
        $this->initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $configManager->set(
            'oro_web_catalog.web_catalog',
            $this->getReference(LoadWebCatalogWithContentNodes::WEB_CATALOG_NAME)->getId()
        );
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', $this->initialWebCatalogId);
        $configManager->flush();

        parent::tearDown();
    }

    public function testOnWebsiteSearchIndex(): void
    {
        /** @var LocalizedFallbackValue $metaDescription */
        /** @var ContentVariant $contentVariant1 */
        $contentVariant1 = $this->getReference(LoadWebCatalogWithContentNodes::CONTENT_VARIANT_1);
        $product1 = $contentVariant1->getProductPageProduct();
        /** @var ContentVariant $contentVariant2 */
        $contentVariant2 = $this->getReference(LoadWebCatalogWithContentNodes::CONTENT_VARIANT_2);
        $product2 = $contentVariant2->getProductPageProduct();
        /** @var ContentVariant $contentVariant */
        $collectionContentVariant = $this->getReference(LoadWebCatalogWithContentNodes::CONTENT_VARIANT_3);

        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [$product1->getId(), $product2->getId()], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        $query1 = self::getContainer()->get('oro_product.website_search.repository.product')
            ->getSearchQueryBySkuOrName(LoadProductData::PRODUCT_1, 0, 1)
            ->addSelect(sprintf(
                'integer.assigned_to.%s_%s as assignedProductVariant1',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $contentVariant1->getId()
            ))
            ->addSelect(sprintf(
                'integer.assigned_to.%s_%s as assignedProductVariant2',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $contentVariant2->getId()
            ))
            ->addSelect(sprintf(
                'integer.assigned_to.%s_%s as assignedCollectionVariant',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $collectionContentVariant->getId()
            ))
            ->addSelect(sprintf(
                'decimal.assigned_to_sort_order.%s_%s as sortOrder',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $collectionContentVariant->getId()
            ));

        $results1 = $query1->getResult();

        $this->assertEquals(1, $results1->getRecordsCount());
        $this->assertEquals($product1->getId(), $results1[0]->getRecordId());
        $this->assertEquals(1, $results1[0]->getSelectedData()['assignedProductVariant1']);
        $this->assertEmpty($results1[0]->getSelectedData()['assignedProductVariant2']);
        $this->assertEquals(1, $results1[0]->getSelectedData()['assignedCollectionVariant']);
        $this->assertEquals(1, $results1[0]->getSelectedData()['sortOrder']);

        $query2 = self::getContainer()->get('oro_product.website_search.repository.product')
            ->getSearchQueryBySkuOrName(LoadProductData::PRODUCT_2, 0, 1)
            ->addSelect(sprintf(
                'integer.assigned_to.%s_%s as assignedProductVariant1',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $contentVariant1->getId()
            ))
            ->addSelect(sprintf(
                'integer.assigned_to.%s_%s as assignedProductVariant2',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $contentVariant2->getId()
            ))
            ->addSelect(sprintf(
                'integer.assigned_to.%s_%s as assignedCollectionVariant',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $collectionContentVariant->getId()
            ))
            ->addSelect(sprintf(
                'decimal.assigned_to_sort_order.%s_%s as sortOrder',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $collectionContentVariant->getId()
            ));

        $results2 = $query2->getResult();

        $this->assertEquals(1, $results2->getRecordsCount());
        $this->assertEquals($product2->getId(), $results2[0]->getRecordId());
        $this->assertEmpty($results2[0]->getSelectedData()['assignedProductVariant1']);
        $this->assertEquals(1, $results2[0]->getSelectedData()['assignedProductVariant2']);
        $this->assertEquals(1, $results2[0]->getSelectedData()['assignedCollectionVariant']);
        $this->assertEquals(0.2, $results2[0]->getSelectedData()['sortOrder']);
    }
}
