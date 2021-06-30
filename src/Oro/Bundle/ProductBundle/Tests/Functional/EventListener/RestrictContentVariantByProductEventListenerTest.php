<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadContentVariantData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class RestrictContentVariantByProductEventListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ConfigManager $configManager;

    private ContentNodeProvider $contentNodeProvider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadContentVariantData::class,
            LoadWebsiteData::class
        ]);

        $this->configManager = self::getConfigManager('global');
        $this->contentNodeProvider = self::getContainer()->get('oro_web_catalog.content_node_provider');
    }

    public function testApplyRestriction(): void
    {
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $this->configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $this->configManager->flush();

        $entity = $this->getReference(LoadProductData::PRODUCT_1);
        $variant = $this->getReference(LoadContentVariantData::VARIANT);

        $actualVariant = $this->contentNodeProvider->getFirstMatchingVariantForEntity($entity);
        self::assertInstanceOf(ContentVariant::class, $actualVariant);
        self::assertEquals($variant->getId(), $actualVariant->getId());
    }
}
