<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapProductByInventoryStatusListener;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestrictSitemapProductByInventoryStatusListenerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadProductData::class]);
    }

    public function testRestrictQueryBuilder(): void
    {
        /** @var QueryBuilder $qb */
        $qb = self::getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->createQueryBuilder(UrlItemsProvider::ENTITY_ALIAS);

        $qb->select(UrlItemsProvider::ENTITY_ALIAS. '.id');

        $configManager = self::getConfigManager(null);
        $this->assertEquals(
            [
                'prod_inventory_status.in_stock',
                'prod_inventory_status.out_of_stock'
            ],
            $configManager->get('oro_product.general_frontend_product_visibility')
        );
        $listener = new RestrictSitemapProductByInventoryStatusListener($configManager);
        $listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($qb, time()));

        $actual = array_map('current', $qb->getQuery()->getResult());
        $expected = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_2,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_5,
            LoadProductData::PRODUCT_6,
            LoadProductData::PRODUCT_7,
            LoadProductData::PRODUCT_8,
            LoadProductData::PRODUCT_9,
        ];

        $this->assertCount(count($expected), $actual);
        foreach ($expected as $product) {
            /** @var Product $product */
            $product = $this->getReference($product);
            $this->assertContains($product->getId(), $actual);
        }
    }

    public function testRestrictQueryBuilderAllStatusesDisallowed(): void
    {
        /** @var QueryBuilder $qb */
        $qb = self::getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->createQueryBuilder(UrlItemsProvider::ENTITY_ALIAS);

        $qb->select(UrlItemsProvider::ENTITY_ALIAS. '.id');

        $configManager = self::getConfigManager();
        $initialProductVisibility = $configManager->get('oro_product.general_frontend_product_visibility');
        $configManager->set('oro_product.general_frontend_product_visibility', []);
        $configManager->flush();
        try {
            $listener = new RestrictSitemapProductByInventoryStatusListener(self::getConfigManager(null));
            $listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($qb, time()));
            $actual = array_map('current', $qb->getQuery()->getResult());
        } finally {
            $configManager->set('oro_product.general_frontend_product_visibility', $initialProductVisibility);
            $configManager->flush();
        }

        $this->assertEmpty($actual);
    }
}
