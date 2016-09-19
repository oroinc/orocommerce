<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AbstractVisibilityRepository;
use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractCacheBuilderTest extends WebTestCase
{
    /** @var  Registry */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
        ]);

        $this->registry = $this->client->getContainer()->get('doctrine');
    }


    public function tearDown()
    {
        $this->getContainer()->get('doctrine')->getManager()->clear();
        parent::tearDown();
    }

    /**
     * @dataProvider buildCacheDataProvider
     *
     * @param $expectedStaticCount
     * @param $expectedCategoryCount
     * @param string|null $websiteReference
     */
    public function testBuildCache($expectedStaticCount, $expectedCategoryCount, $websiteReference = null)
    {
        $repository = $this->getRepository();
        $website = $this->getWebsite($websiteReference);
        $repository->clearTable();
        $this->getCacheBuilder()->buildCache($website);

        $actualTotalCount = (int)$repository->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
        $this->assertEquals($expectedStaticCount + $expectedCategoryCount, $actualTotalCount);

        $sourceCountQb = $repository->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->where('entity.source = :source');
        $actualStaticCount = (int)$sourceCountQb
            ->setParameter('source', BaseProductVisibilityResolved::SOURCE_STATIC)
            ->getQuery()
            ->getSingleScalarResult();
        $actualCategoryCount = (int)$sourceCountQb
            ->setParameter('source', BaseProductVisibilityResolved::SOURCE_CATEGORY)
            ->getQuery()
            ->getSingleScalarResult();
        $this->assertEquals($expectedStaticCount, $actualStaticCount);
        $this->assertEquals($expectedCategoryCount, $actualCategoryCount);
    }

    /**
     * @return AbstractVisibilityRepository
     */
    abstract protected function getRepository();

    /**
     * @return array
     */
    abstract public function buildCacheDataProvider();

    /**
     * @return CacheBuilderInterface
     */
    abstract protected function getCacheBuilder();

    /**
     * @param string $websiteReference
     * @return Website
     */
    protected function getWebsite($websiteReference)
    {
        if ($websiteReference === 'default') {
            return $this->registry->getManagerForClass('OroWebsiteBundle:Website')
                ->getRepository('OroWebsiteBundle:Website')
                ->getDefaultWebsite();
        }
        return $websiteReference ? $this->getReference($websiteReference) : null;
    }
}
