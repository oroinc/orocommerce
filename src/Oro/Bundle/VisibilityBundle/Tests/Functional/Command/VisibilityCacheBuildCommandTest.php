<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Command\VisibilityCacheBuildCommand;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;

/**
 * @group CommunityEdition
 */
class VisibilityCacheBuildCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadCategoryProductData::class,
            LoadGroups::class,
            LoadCategoryVisibilityData::class,
            LoadProductVisibilityData::class,
        ]);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $params, array $expectedMessages, array $expectedRecordsCount)
    {
        // Clear all resolved tables and check that all of them are empty
        $this->clearAllResolvedTables();

        $this->assertEquals(0, $this->getCategoryVisibilityResolvedCount());
        $this->assertEquals(0, $this->getCustomerGroupCategoryVisibilityResolvedCount());
        $this->assertEquals(0, $this->getCustomerCategoryVisibilityResolvedCount());

        $this->assertEquals(0, $this->getProductVisibilityResolvedCount());
        $this->assertEquals(0, $this->getCustomerGroupProductVisibilityResolvedCount());
        $this->assertEquals(0, $this->getCustomerProductVisibilityResolvedCount());

        // Run command and check result messages
        $result = $this->runCommand(VisibilityCacheBuildCommand::getDefaultName(), $params);
        foreach ($expectedMessages as $message) {
            self::assertStringContainsString($message, $result);
        }

        // Check that all resolved tables are filled
        $this->assertEquals(
            $expectedRecordsCount['categoryVisibility'],
            $this->getCategoryVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['customerGroupCategoryVisibility'],
            $this->getCustomerGroupCategoryVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['customerCategoryVisibility'],
            $this->getCustomerCategoryVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['productVisibility'],
            $this->getProductVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['customerGroupProductVisibility'],
            $this->getCustomerGroupProductVisibilityResolvedCount()
        );
        $this->assertEquals(
            $expectedRecordsCount['customerProductVisibility'],
            $this->getCustomerProductVisibilityResolvedCount()
        );
    }

    public function executeDataProvider(): array
    {
        return [
            'withoutParam' => [
                'params' => [],
                'expectedMessages' =>
                [
                    'Start the process of building the cache',
                    'The cache is updated successfully',
                ],
                'expectedCounts' => [
                    'categoryVisibility' => 8,
                    'customerGroupCategoryVisibility' => 16,
                    'customerCategoryVisibility' => 35,
                    'productVisibility' => 3,
                    'customerGroupProductVisibility' => 11,
                    'customerProductVisibility' => 6,
                ]
            ],
        ];
    }

    private function getCategoryVisibilityResolvedCount(): int
    {
        return $this->getEntitiesCount($this->getCategoryVisibilityResolvedRepository());
    }

    private function getCustomerGroupCategoryVisibilityResolvedCount(): int
    {
        return $this->getEntitiesCount($this->getCustomerGroupCategoryVisibilityResolvedRepository());
    }

    private function getCustomerCategoryVisibilityResolvedCount(): int
    {
        return $this->getEntitiesCount($this->getCustomerCategoryVisibilityResolvedRepository());
    }

    private function getProductVisibilityResolvedCount(): int
    {
        return $this->getEntitiesCount($this->getProductVisibilityResolvedRepository());
    }

    private function getCustomerGroupProductVisibilityResolvedCount(): int
    {
        return $this->getEntitiesCount($this->getCustomerGroupProductVisibilityResolvedRepository());
    }

    private function getCustomerProductVisibilityResolvedCount(): int
    {
        return $this->getEntitiesCount($this->getCustomerProductVisibilityResolvedRepository());
    }

    private function getCategoryVisibilityResolvedRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CategoryVisibilityResolved::class);
    }

    private function getCustomerGroupCategoryVisibilityResolvedRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerGroupCategoryVisibilityResolved::class);
    }

    private function getCustomerCategoryVisibilityResolvedRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerCategoryVisibilityResolved::class);
    }

    private function getProductVisibilityResolvedRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(ProductVisibilityResolved::class);
    }

    private function getCustomerGroupProductVisibilityResolvedRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerGroupProductVisibilityResolved::class);
    }

    private function getCustomerProductVisibilityResolvedRepository(): EntityRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerProductVisibilityResolved::class);
    }

    private function clearAllResolvedTables()
    {
        $this->deleteAllEntities($this->getCategoryVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getCustomerGroupCategoryVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getCustomerCategoryVisibilityResolvedRepository());

        $this->deleteAllEntities($this->getProductVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getCustomerGroupProductVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getCustomerProductVisibilityResolvedRepository());
    }

    private function getEntitiesCount(EntityRepository $repository): int
    {
        return (int)$repository->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function deleteAllEntities(EntityRepository $repository): void
    {
        $repository->createQueryBuilder('entity')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
