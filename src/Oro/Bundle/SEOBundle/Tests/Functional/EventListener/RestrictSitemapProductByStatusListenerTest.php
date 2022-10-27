<?php

namespace Oro\Bundle\SEOBundle\Tests\Functional\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\EventListener\RestrictSitemapProductByStatusListener;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestrictSitemapProductByStatusListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductData::class,
        ]);
    }

    public function testRestrictQueryBuilder()
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getContainer()->get('doctrine')
            ->getManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->createQueryBuilder(UrlItemsProvider::ENTITY_ALIAS);

        $qb->select(UrlItemsProvider::ENTITY_ALIAS. '.id');

        $listener = new RestrictSitemapProductByStatusListener();
        $listener->restrictQueryBuilder(new RestrictSitemapEntitiesEvent($qb, time()));

        $actual = array_map('current', $qb->getQuery()->getResult());
        $expected = [
            LoadProductData::PRODUCT_1,
            LoadProductData::PRODUCT_2,
            LoadProductData::PRODUCT_3,
            LoadProductData::PRODUCT_4,
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
}
