<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Visibility\Cache;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\ProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\ProductResolvedCacheBuilder;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class ProductResolvedCacheBuilderBuildCacheTest extends WebTestCase
{
    /**
     * @var ProductResolvedCacheBuilder
     */
    protected $cacheBuilder;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ]);

        $container = $this->client->getContainer();

        $this->cacheBuilder = new ProductResolvedCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_entity.orm.insert_from_select_query_executor')
        );
        $this->cacheBuilder->setCacheClass(
            $container->getParameter('orob2b_account.entity.product_visibility_resolved.class')
        );
    }

    public function testBuildCache()
    {
        $repository = $this->getRepository();
        $manager = $this->getManager();
        $defaultWebsite = $this->getDefaultWebsite();

        // new entities were generated
        $repository->createQueryBuilder('entity')
            ->delete('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved', 'entity')
            ->getQuery()
            ->execute();
        $this->assertResolvedEntitiesCount(0);
        $this->cacheBuilder->buildCache();
        $this->assertResolvedEntitiesCount(27);

        // config fallback
        /** @var Product $firstProduct */
        $firstProduct = $this->getReference(LoadProductData::PRODUCT_1);
        $this->assertNull($repository->findOneBy(['website' => $defaultWebsite, 'product' => $firstProduct]));

        // category fallback
        /** @var ProductVisibilityResolved $firstProductCustomWebsite */
        $firstProductCustomWebsite = $repository->findOneBy([
            'website' => $this->getReference(LoadWebsiteData::WEBSITE2),
            'product' => $this->getReference(LoadProductData::PRODUCT_1)
        ]);
        $this->assertNotNull($firstProductCustomWebsite);
        $this->assertEquals(
            BaseProductVisibilityResolved::VISIBILITY_VISIBLE,
            $firstProductCustomWebsite->getVisibility()
        );
        $this->assertEquals(
            $this->getReference(LoadCategoryData::FIRST_LEVEL),
            $firstProductCustomWebsite->getCategory()
        );

        // static fallback
        /** @var ProductVisibilityResolved $forthProductDefaultWebsite */
        $forthProductDefaultWebsite = $repository
            ->findOneBy(['website' => $defaultWebsite, 'product' => $this->getReference(LoadProductData::PRODUCT_4)]);
        $this->assertNotNull($forthProductDefaultWebsite);
        $this->assertEquals(
            BaseProductVisibilityResolved::VISIBILITY_HIDDEN,
            $forthProductDefaultWebsite->getVisibility()
        );
        $this->assertNull($forthProductDefaultWebsite->getCategory());

        // invalid entity for first product at default website
        $resolvedVisibility = new ProductVisibilityResolved($defaultWebsite, $firstProduct);
        $resolvedVisibility->setVisibility(BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setSource(BaseProductVisibilityResolved::SOURCE_STATIC);
        $manager->persist($resolvedVisibility);
        $manager->flush();

        // invalid entities were removed
        $this->assertNotNull($repository->findOneBy(['website' => $defaultWebsite, 'product' => $firstProduct]));
        $this->cacheBuilder->buildCache();
        $this->assertNull($repository->findOneBy(['website' => $defaultWebsite, 'product' => $firstProduct]));
    }

    /**
     * @param int $expected
     */
    protected function assertResolvedEntitiesCount($expected)
    {
        $count = $this->getRepository()->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
        $this->assertEquals($expected, $count);
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->getManager()->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }

    /**
     * @return Website
     */
    protected function getDefaultWebsite()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->getDefaultWebsite();
    }
}
