<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapProductSlugByLocaleListener;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\RestrictSitemapCmsPageByWebCatalogListener as FixtureDir;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\RestrictSitemapCmsPageByWebCatalogListener\LoadWebsiteData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\AbstractWebsiteLocalizationProvider;

/**
 * @dbIsolationPerTest
 */
class RestrictSitemapProductSlugByLocaleListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?array $initialProductVisibility;
    private ?string $initialCanonicalUrlType;
    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductData::class,
            LoadWebsiteData::class,
            LoadLocalizationData::class
        ]);

        $website = $this->getReference(FixtureDir\LoadWebsiteData::WEBSITE_DEFAULT);
        $configManager = self::getConfigManager();
        $this->initialProductVisibility = $configManager->get('oro_product.general_frontend_product_visibility');
        $this->initialCanonicalUrlType = $configManager->get('oro_redirect.canonical_url_type');
        $this->initialEnabledLocalizations = $configManager->get(
            'oro_locale.enabled_localizations',
            false,
            false,
            $website
        );

        $this->updateProductSlug();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $website = $this->getReference(FixtureDir\LoadWebsiteData::WEBSITE_DEFAULT);
        $configManager = self::getConfigManager();
        $configManager->set('oro_product.general_frontend_product_visibility', $this->initialProductVisibility);
        $configManager->set('oro_redirect.canonical_url_type', $this->initialCanonicalUrlType);
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations, $website);
        $configManager->flush();
    }

    public function testRestrictQueryBuilderMatchedLocalizedSlug(): void
    {
        $localizationId = $this->getReference('en_CA')->getId();
        $this->updateConfigs([$localizationId]);
        /** @var Website $website */
        $website = $this->getReference(FixtureDir\LoadWebsiteData::WEBSITE_DEFAULT);

        $queryBuilder = $this->createQueryBuilder();

        $urlGenerator = self::getCanonicalUrlGenerator();
        $localizationProvider = self::getWebsiteLocalizationProvider();
        $listener = new RestrictSitemapProductSlugByLocaleListener($urlGenerator, $localizationProvider);
        $listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($queryBuilder, time(), $website));

        $results = $queryBuilder->getQuery()->getResult();

        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        foreach ($results as $prodUrl) {
            if ($prodUrl['id'] === $product1->getId()) {
                $this->assertEquals('/product-1-slug-ca', $prodUrl['url']);
            }

            if ($prodUrl['id'] === $product2->getId()) {
                $this->assertEquals('/product-2', $prodUrl['url']);
            }
        }
    }

    public function testRestrictQueryBuilderNoMatchedLocalizedSlug(): void
    {
        $localizationIds = [
            $this->getReference('en_US')->getId(),
            $this->getReference('en_CA')->getId(),
            $this->getReference('es')->getId()
        ];
        $this->updateConfigs($localizationIds);
        /** @var Website $website */
        $website = $this->getReference(FixtureDir\LoadWebsiteData::WEBSITE_DEFAULT);
        $queryBuilder = $this->createQueryBuilder();

        $urlGenerator = self::getCanonicalUrlGenerator();
        $localizationProvider = self::getWebsiteLocalizationProvider();
        $listener = new RestrictSitemapProductSlugByLocaleListener($urlGenerator, $localizationProvider);
        $listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($queryBuilder, time(), $website));

        $results = $queryBuilder->getQuery()->getResult();

        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        $prodSlugs = [];
        foreach ($results as $prodUrl) {
            if ($prodUrl['id'] === $product1->getId()) {
                $this->assertContains($prodUrl['url'], ['/product-1-slug-ca', '/product-1']);
                $prodSlugs[] = $prodUrl;
            }

            if ($prodUrl['id'] === $product2->getId()) {
                $this->assertEquals('/product-2', $prodUrl['url']);
                $prodSlugs[] = $prodUrl;
            }
        }
        $this->assertCount(3, $prodSlugs);
        unset($prodSlugs);
    }

    private function updateProductSlug(): void
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $slugDefault = new Slug();
        $slugDefault->setUrl('/product-1')
            ->setRouteName('oro_product_frontend_product_view')
            ->setRouteParameters(['id' => $product1->getId()]);
        $slugCA = new Slug();
        /** @var Localization $localizationCA */
        $localizationCA = $this->getReference('en_CA');
        $slugCA->setUrl('/product-1-slug-ca')->setLocalization($localizationCA)
            ->setRouteName('oro_product_frontend_product_view')
            ->setRouteParameters(['id' => $product1->getId()]);
        $product1->addSlug($slugDefault)->addSlug($slugCA);

        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        $slug2Default = new Slug();
        $slug2Default->setUrl('/product-2')
            ->setRouteName('oro_product_frontend_product_view')
            ->setRouteParameters(['id' => $product2->getId()]);
        $product2->addSlug($slug2Default);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $entityManager->persist($product1);
        $entityManager->persist($product2);
        $entityManager->flush();
    }

    private function updateConfigs(array $localizationIds): void
    {
        $website = $this->getReference(FixtureDir\LoadWebsiteData::WEBSITE_DEFAULT);
        $configManager = self::getConfigManager();
        $configManager->set('oro_product.general_frontend_product_visibility', []);
        $configManager->set('oro_redirect.canonical_url_type', Configuration::DIRECT_URL);
        $configManager->set('oro_locale.enabled_localizations', $localizationIds, $website);
        $configManager->flush();
    }

    private function createQueryBuilder(): QueryBuilder
    {
        /** @var QueryBuilder $qb */
        $qb = self::getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->createQueryBuilder(UrlItemsProvider::ENTITY_ALIAS);

        $qb->select(UrlItemsProvider::ENTITY_ALIAS . '.id');
        $qb->leftJoin(UrlItemsProvider::ENTITY_ALIAS . '.slugs', 'slugs');
        $qb->addSelect('slugs.url');

        return $qb;
    }

    private static function getCanonicalUrlGenerator(): CanonicalUrlGenerator
    {
        return self::getContainer()->get('oro_redirect.generator.canonical_url');
    }

    private static function getWebsiteLocalizationProvider(): AbstractWebsiteLocalizationProvider
    {
        return self::getContainer()->get('oro_website.provider.website_localization');
    }
}
