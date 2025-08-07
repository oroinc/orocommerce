<?php

declare(strict_types=1);

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductAttributesData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;

/**
 * @dbIsolationPerTest
 */
class IndexDataProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?array $initialEnabledLocalizations;
    private IndexDataProvider $provider;
    private SearchMappingProvider $websiteSearchMappingProvider;
    private LocalizationManager $localizationManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadFrontendProductAttributesData::class]);

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $configManager->set(
            'oro_locale.enabled_localizations',
            LoadLocalizationData::getLocalizationIds(self::getContainer())
        );
        $configManager->flush();

        $container = self::getContainer();
        $this->provider = $container->get('oro_website_search.tests.engine.index_data');
        $this->websiteSearchMappingProvider = $container->get('oro_website_search.tests.provider.search_mapping');
        $this->localizationManager = $container->get('oro_locale.manager.localization');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->flush();
    }

    public function testCollectContextForWebsite(): void
    {
        $context = $this->provider->collectContextForWebsite(1, []);
        self::assertEquals([AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => 1], $context);
    }

    public function testGetEntitiesDataCheckConflictingEnums(): void
    {
        $entityClass = Product::class;
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $contact = new LocalizedFallbackValue();
        $contact->setString('Contact');
        $product->setContact([$contact]);

        $data = $this->provider->getEntitiesData(
            $entityClass,
            [$product],
            $this->provider->collectContextForWebsite(1, []),
            $this->websiteSearchMappingProvider->getEntityConfig($entityClass)
        );

        foreach ($this->localizationManager->getLocalizations() as $localization) {
            $key = sprintf('contact_%d', $localization->getId());
            self::assertArrayHasKey($key, $data[$product->getId()]['text']);
            self::assertEquals('Contact', $data[$product->getId()]['text'][$key]);
        }

        $enumFieldNames = [
            'type_contact_enum.enum_third_option',
            'type_contact_priority',
            'contact_type_enum.enum_second_option',
            'contact_type_priority',
        ];

        foreach ($enumFieldNames as $enumFieldName) {
            self::assertArrayNotHasKey($enumFieldName, $data[$product->getId()]['text']);
            self::assertArrayHasKey($enumFieldName, $data[$product->getId()]['integer']);
        }
    }
}
