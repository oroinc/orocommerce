<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Repository\AccountGroupProductVisibilityResolvedRepository;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\AccountGroupProductResolvedCacheBuilder;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class AccountGroupProductResolvedCacheBuilderTest extends WebTestCase
{
    /**
     * @var AccountGroupProductResolvedCacheBuilder
     */
    protected $cacheBuilder;

    protected function setUp()
    {
        $this->initClient();

        $this->cacheBuilder = $this->client->getContainer()
            ->get('orob2b_account.visibility.cache.account_group_product_resolved_cache_builder');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
                'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            ]
        );
    }

    public function testBuildCache()
    {
        $agpv = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->findOneBy(
                [
                    'product' => $this->getReference(LoadProductData::PRODUCT_1),
                    'accountGroup' => $this->getReference(LoadGroups::GROUP1),
                ]
            );
        $agpv->setVisibility(AccountGroupProductVisibility::CATEGORY);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->cacheBuilder->buildCache();

        $this->assertCount(4, $this->getRepository()->findAll());

        $this->assertCount(
            3,
            $this->getRepository()->findBy(['source' => BaseProductVisibilityResolved::SOURCE_STATIC])
        );
        $this->assertCount(
            1,
            $this->getRepository()->findBy(['source' => BaseProductVisibilityResolved::SOURCE_CATEGORY])
        );
    }

    /**
     * @return EntityRepository
     */
    protected function getSourceRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility'
        );
    }

    /**
     * @return AccountGroupProductVisibilityResolvedRepository|EntityRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved'
        );
    }
}
