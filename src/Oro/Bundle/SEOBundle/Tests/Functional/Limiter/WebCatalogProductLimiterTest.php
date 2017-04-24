<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SEOBundle\Entity\WebCatalogProductLimitation;
use Oro\Bundle\SEOBundle\Limiter\WebCatalogProductLimiter;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadWebCatalogProductLimitationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogCategoryData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures\LoadWebCatalogProductData;

/**
 * @dbIsolationPerTest
 * @nestTransactionsWithSavepoints
 */
class WebCatalogProductLimiterTest extends WebTestCase
{
    /**
     * @var WebCatalogProductLimiter
     */
    private $webCatalogProductLimiter;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->webCatalogProductLimiter = $this->getContainer()->get('oro_seo.limiter.web_catalog_product_limiter');
    }

    public function testProductLimitationEntriesPrepared()
    {
        $this->loadFixtures([
            LoadWebCatalogProductData::class,
            LoadWebCatalogCategoryData::class,
            LoadCategoryProductData::class
        ]);
        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set('oro_web_catalog.web_catalog', $this->getReference(LoadWebCatalogData::CATALOG_1)->getId());
        $configManager->flush();

        $this->webCatalogProductLimiter->prepareLimitation(LoadWebCatalogProductLimitationData::VERSION);

        $actual = $this->getContainer()->get('doctrine')
           ->getRepository(WebCatalogProductLimitation::class)
           ->findAll();

        $actualProductIds = array_map(function (WebCatalogProductLimitation $item) {
            return $item->getProductId();
        }, $actual);

        $expected = [
            // Direct web catalog products
            LoadProductData::PRODUCT_1,
            // Web catalog categories with subcategories products
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_2,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_4,
            LoadProductData::PRODUCT_5,
            LoadProductData::PRODUCT_6,
            LoadProductData::PRODUCT_7,
            LoadProductData::PRODUCT_8,
        ];

        foreach ($expected as $productReference) {
            $product = $this->getReference($productReference);
            $this->assertContains($product->getId(), $actualProductIds);
        }
    }

    public function testProductLimitationEntriesErased()
    {
        $this->loadFixtures([
            LoadWebCatalogProductLimitationData::class
        ]);

        $this->webCatalogProductLimiter->erase(LoadWebCatalogProductLimitationData::VERSION);

        $actual = $this->getContainer()->get('doctrine')
            ->getRepository(WebCatalogProductLimitation::class)
            ->findBy(['version' => LoadWebCatalogProductLimitationData::VERSION]);

        $this->assertEmpty($actual);
    }

    public function testProductLimitationEntriesErasedWithTruncate()
    {
        $this->markTestSkipped(
            'This skip should be removed, and test should be executed after fix of BAP-14180'
        );
        $this->loadFixtures([
            LoadWebCatalogProductLimitationData::class
        ]);

        $this->webCatalogProductLimiter->erase(LoadWebCatalogProductLimitationData::VERSION);
        $this->webCatalogProductLimiter->erase(LoadWebCatalogProductLimitationData::ALT_VERSION);

        $actual = $this->getContainer()->get('doctrine')
            ->getRepository(WebCatalogProductLimitation::class)
            ->findAll();

        $this->assertEmpty($actual);
    }
}
