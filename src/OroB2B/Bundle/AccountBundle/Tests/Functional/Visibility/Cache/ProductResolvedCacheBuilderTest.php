<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\ProductResolvedCacheBuilder;

/**
 * @dbIsolation
 */
class ProductResolvedCacheBuilderTest extends WebTestCase
{
    /**
     * @var ProductResolvedCacheBuilder
     */
    protected $cacheBuilder;

    protected function setUp()
    {
        $this->initClient();

        $this->cacheBuilder = $this->client->getContainer()
            ->get('orob2b_account.visibility.cache.product.product_resolved_cache_builder');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            ]
        );
    }

    public function testBuildCache()
    {
        $this->cacheBuilder->buildCache();
        $actual = $this->getContainer()->get('doctrine')
            ->getManager()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findAll()
        ;

        $this->assertSame(18, count($actual));
    }
}
