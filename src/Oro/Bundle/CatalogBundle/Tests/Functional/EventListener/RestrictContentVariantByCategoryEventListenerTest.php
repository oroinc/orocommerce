<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\EventListener;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\ContentNodeProvider;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class RestrictContentVariantByCategoryEventListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var ContentNodeProvider
     */
    private $contentNodeProvider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadContentVariantData::class,
            LoadWebsiteData::class
        ]);

        $this->configManager = self::getConfigManager('global');
        $this->contentNodeProvider = $this->getContainer()->get('oro_web_catalog.content_node_provider');
    }

    public function testApplyRestriction()
    {
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE2);

        $this->configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $this->configManager->flush();

        $entity = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $variant = $this->getReference(LoadContentVariantData::VARIANT);

        $actualVariant = $this->contentNodeProvider->getFirstMatchingVariantForEntity($entity, $website);
        $this->assertInstanceOf(ContentVariant::class, $actualVariant);
        $this->assertEquals($variant->getId(), $actualVariant->getId());
    }
}
