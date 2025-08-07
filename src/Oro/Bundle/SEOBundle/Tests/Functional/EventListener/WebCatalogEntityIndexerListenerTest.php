<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadWebCatalogWithContentNodes;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogEntityIndexerListener;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM\OrmIndexerTest;
use Symfony\Component\HttpFoundation\Request;

class WebCatalogEntityIndexerListenerTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const QUERY = 'web_catalog_entity_indexer_listener_test_query_string';

    private ?int $initialWebCatalogId;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        OrmIndexerTest::checkSearchEngine($this);
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
        $localizedFallbackValueManager = self::getContainer()->get('doctrine')
            ->getManagerForClass(LocalizedFallbackValue::class);

        /** @var ContentNode $contentNode */
        $contentNode = $this->getReference(LoadWebCatalogWithContentNodes::CONTENT_NODE_1);
        /** @var ContentVariant $contentVariant */
        $notAssignedContentVariant = $this->getReference(LoadWebCatalogWithContentNodes::CONTENT_VARIANT_2);
        /** @var ContentVariant $contentVariant */
        $contentVariant = $this->getReference(LoadWebCatalogWithContentNodes::CONTENT_VARIANT_1);
        $product = $contentVariant->getProductPageProduct();

        /** @var LocalizedFallbackValue $metaDescription */
        $metaDescription = $contentNode->getMetaDescriptions()[0];
        $metaDescription->setString(self::QUERY);

        $localizedFallbackValueManager->persist($metaDescription);
        $localizedFallbackValueManager->flush();

        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [$product->getId()], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        $query = self::getContainer()->get('oro_product.website_search.repository.product')
            ->getSearchQuery(self::QUERY, 0, 1)
            ->addSelect(sprintf(
                'integer.assigned_to.%s_%s as assigned',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $contentVariant->getId()
            ))
            ->addSelect(sprintf(
                'integer.assigned_to.%s_%s as notAssigned',
                WebCatalogEntityIndexerListener::ASSIGN_TYPE_CONTENT_VARIANT,
                $notAssignedContentVariant->getId()
            ));

        $results = $query->getResult();

        $this->assertEquals(1, $results->getRecordsCount());
        $this->assertEquals($product->getId(), $results[0]->getRecordId());
        $this->assertEquals(1, $results[0]->getSelectedData()['assigned']);
        $this->assertEmpty($results[0]->getSelectedData()['notAssigned']);
    }
}
