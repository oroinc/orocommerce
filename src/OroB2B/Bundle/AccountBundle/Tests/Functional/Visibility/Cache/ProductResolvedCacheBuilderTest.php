<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductResolvedCacheBuilder;

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
            ->get('orob2b_account.visibility.cache.product_resolved_cache_builder');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityResolvedData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData'
            ]
        );
    }

    public function testClearBeforeBuildCache()
    {
        $this->cacheBuilder->buildCache();
        $actual = $this->getContainer()->get('doctrine')
            ->getManager()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->findAll()
        ;

        $this->assertSame(14, count($actual));
    }
}
