<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\EventListener;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class RestrictContentVariantByCategoryEventListenerTest extends WebTestCase
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
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE2);

        $entity = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $variant = $this->getReference(LoadContentVariantData::VARIANT);

        $configManager = self::getConfigManager();
        $initialWebCatalogId = $configManager->get('oro_web_catalog.web_catalog');
        $configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $configManager->flush();
        try {
            $actualVariant = $this->contentNodeProvider->getFirstMatchingVariantForEntity($entity, $website);
        } finally {
            $configManager->set('oro_web_catalog.web_catalog', $initialWebCatalogId);
            $configManager->flush();
        }

        $this->assertInstanceOf(ContentVariant::class, $actualVariant);
        $this->assertEquals($variant->getId(), $actualVariant->getId());
    }
}
