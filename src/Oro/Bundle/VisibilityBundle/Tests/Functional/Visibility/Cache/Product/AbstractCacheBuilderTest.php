<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AbstractVisibilityRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CacheBuilderInterface;

abstract class AbstractCacheBuilderTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductVisibilityData::class]);
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('doctrine')->getManager()->clear();
        parent::tearDown();
    }

    /**
     * @dataProvider buildCacheDataProvider
     */
    public function testBuildCache(int $expectedStaticCount, int $expectedCategoryCount)
    {
        $repository = $this->getRepository();
        $repository->clearTable();
        $this->getCacheBuilder()->buildCache();

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

    abstract protected function getRepository(): AbstractVisibilityRepository;

    abstract public function buildCacheDataProvider(): array;

    abstract protected function getCacheBuilder(): CacheBuilderInterface;
}
