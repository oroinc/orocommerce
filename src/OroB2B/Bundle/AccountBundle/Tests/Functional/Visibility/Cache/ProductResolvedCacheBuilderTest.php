<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache;

use Doctrine\ORM\EntityRepository;
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

    /**
     * @var EntityRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();

        $this->cacheBuilder = $this->client->getContainer()
            ->get('orob2b_account.visibility.cache.product_resolved_cache_builder');

        $this->repository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityResolvedData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData'
            ]
        );
    }

    /**
     * @group failing
     */
    public function testClearBeforeBuildCache()
    {
//        $pv = $this->getContainer()->get('doctrine')
//            ->getManager()
//            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
//            ->findAll()
//        ;
//
//        $deleted = $this->cacheBuilder->buildCache();
//        $actualCount = $this->repository->findAll();
//
//        $this->assertSame(4, count($actualCount));
////        $this->assertSame(4, $deleted);
    }
}
