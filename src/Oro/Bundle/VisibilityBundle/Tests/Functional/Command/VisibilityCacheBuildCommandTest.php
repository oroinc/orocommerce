<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Command\VisibilityCacheBuildCommand;
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
            static::assertStringContainsString($message, $result);
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

    /**
     * @return array
     */
    public function executeDataProvider()
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

    /**
     * @return int
     */
    protected function getCategoryVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getCategoryVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getCustomerGroupCategoryVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getCustomerGroupCategoryVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getCustomerCategoryVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getCustomerCategoryVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getProductVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getProductVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getCustomerGroupProductVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getCustomerGroupProductVisibilityResolvedRepository());
    }

    /**
     * @return int
     */
    protected function getCustomerProductVisibilityResolvedCount()
    {
        return $this->getEntitiesCount($this->getCustomerProductVisibilityResolvedRepository());
    }

    /**
     * @return EntityRepository
     */
    protected function getCategoryVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getCustomerGroupCategoryVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getCustomerCategoryVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getCustomerGroupProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved');
    }

    /**
     * @return EntityRepository
     */
    protected function getCustomerProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved');
    }

    protected function clearAllResolvedTables()
    {
        $this->deleteAllEntities($this->getCategoryVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getCustomerGroupCategoryVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getCustomerCategoryVisibilityResolvedRepository());

        $this->deleteAllEntities($this->getProductVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getCustomerGroupProductVisibilityResolvedRepository());
        $this->deleteAllEntities($this->getCustomerProductVisibilityResolvedRepository());
    }

    /**
     * @param EntityRepository $repository
     * @return int
     */
    protected function getEntitiesCount(EntityRepository $repository)
    {
        return (int)$repository->createQueryBuilder('entity')
            ->select('COUNT(entity.visibility)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    protected function deleteAllEntities(EntityRepository $repository)
    {
        $repository->createQueryBuilder('entity')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
