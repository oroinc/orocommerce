<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\AccountProductResolvedCacheBuilder;

/**
 * @dbIsolation
 */
class AccountProductResolvedCacheBuilderTest extends WebTestCase
{
    /**
     * @var AccountProductResolvedCacheBuilder
     */
    protected $cacheBuilder;

    protected function setUp()
    {
        $this->initClient();

        $this->cacheBuilder = $this->client->getContainer()
            ->get('orob2b_account.visibility.cache.account_product_resolved_cache_builder');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityResolvedData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            ]
        );
    }

    public function testBuildCache()
    {
        $this->getContainer()->get('doctrine')
            ->getManager()
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->findAll();
        $this->cacheBuilder->buildCache();

        $this->assertCount(288, $this->getRepository()->findAll());

        $this->assertCount(
            2,
            $this->getRepository()->findBy(['source' => BaseProductVisibilityResolved::SOURCE_STATIC])
        );
        $this->assertCount(
            286,
            $this->getRepository()->findBy(['source' => BaseProductVisibilityResolved::SOURCE_CATEGORY])
        );
    }

    /**
     * @return EntityRepository
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:Visibility\AccountProductVisibility'
        );
    }

    /**
     * @return AccountProductVisibilityResolvedRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved'
        );
    }
}
