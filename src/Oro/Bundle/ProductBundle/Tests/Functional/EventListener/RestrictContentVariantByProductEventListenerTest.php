<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadContentVariantData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class RestrictContentVariantByProductEventListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ContentNodeProvider $contentNodeProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadContentVariantData::class,
            LoadWebsiteData::class
        ]);

        $this->contentNodeProvider = self::getContainer()->get('oro_web_catalog.content_node_provider');
    }

    public function testApplyRestriction(): void
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);

        $entity = $this->getReference(LoadProductData::PRODUCT_1);
        $variant = $this->getReference(LoadContentVariantData::VARIANT);

        $configManager = self::getConfigManager();
        $initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $configManager->flush();
        try {
            $actualVariant = $this->contentNodeProvider->getFirstMatchingVariantForEntity($entity);
        } finally {
            $configManager->set('oro_web_catalog.web_catalog', $initialWebCatalogId);
            $configManager->flush();
        }

        self::assertInstanceOf(ContentVariant::class, $actualVariant);
        self::assertEquals($variant->getId(), $actualVariant->getId());
    }
}
