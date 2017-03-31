<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapProductByWebCatalogListener;
use Oro\Bundle\SEOBundle\Tests\Functional\DataFixtures\LoadWebCatalogProductLimitationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class RestrictSitemapProductByWebCatalogListenerTest extends WebTestCase
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var FeatureChecker
     */
    private $featureChecker;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadWebCatalogProductLimitationData::class,
            LoadWebCatalogData::class
        ]);
        $this->configManager = $this->getContainer()->get('oro_config.manager');
        $this->featureChecker = $this->getContainer()->get('oro_featuretoggle.checker.feature_checker');
    }

    public function testRestrictQueryBuilderDisabled()
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->createQueryBuilder('product');

        $qb->select('product.id');

        $this->configManager->set('oro_web_catalog.web_catalog', null);
        $this->configManager->flush();

        $listener = new RestrictSitemapProductByWebCatalogListener($this->configManager);
        $listener->addFeature('frontend_master_catalog');
        $listener->setFeatureChecker($this->featureChecker);

        $event = new RestrictSitemapEntitiesEvent($qb, LoadWebCatalogProductLimitationData::VERSION);

        $listener->restrictQueryBuilder($event);

        $actual = array_map('current', $qb->getQuery()->getResult());
        $expected = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_2,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_4,
            LoadProductData::PRODUCT_5,
            LoadProductData::PRODUCT_6,
            LoadProductData::PRODUCT_7,
            LoadProductData::PRODUCT_8,
        ];

        $this->assertCount(8, $actual);
        foreach ($expected as $product) {
            /** @var Product $product */
            $product = $this->getReference($product);
            $this->assertContains($product->getId(), $actual);
        }
    }

    public function testRestrictQueryBuilder()
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->createQueryBuilder('product');

        $qb->select('product.id');

        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $this->configManager->set('oro_web_catalog.web_catalog', $webCatalog->getId());
        $this->configManager->flush();

        $listener = new RestrictSitemapProductByWebCatalogListener($this->configManager);
        $listener->addFeature('frontend_master_catalog');
        $listener->setFeatureChecker($this->featureChecker);

        $event = new RestrictSitemapEntitiesEvent($qb, LoadWebCatalogProductLimitationData::VERSION);

        $listener->restrictQueryBuilder($event);

        $actual = array_map('current', $qb->getQuery()->getResult());
        $expected = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_5,
            LoadProductData::PRODUCT_7,
        ];

        $this->assertCount(4, $actual);
        foreach ($expected as $product) {
            /** @var Product $product */
            $product = $this->getReference($product);
            $this->assertContains($product->getId(), $actual);
        }
    }
}
