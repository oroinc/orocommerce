<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Command\ProductVisibilityCacheBuildCommand;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\ProductRepository;

/**
 * @dbIsolation
 */
class ProductVisibilityCacheBuildCommandTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([]);

        $this->loadFixtures([
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData',
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
        ]);
    }

    /**
     * @dataProvider executeDataProvider
     * @param array $params
     * @param array $expectedMessages
     * @param array $expectedRecordsCount
     */
    public function testExecute(array $params, array $expectedMessages, array $expectedRecordsCount)
    {
        // Clear category cache. It should be actual in all test cases
        $this->getContainer()->get('orob2b_account.storage.category_visibility_storage')->flush();

        // Clear all resolved tables and check that all of them are empty
        $this->clearAllResolvedTables();

        $this->assertEmpty($this->getProductVisibilityResolved());
        $this->assertEmpty($this->getAccountGroupProductVisibilityResolved());
        $this->assertEmpty($this->getAccountProductVisibilityResolved());

        // Run command and check result messages
        $result = $this->runCommand(ProductVisibilityCacheBuildCommand::NAME, $params);
        foreach ($expectedMessages as $message) {
            $this->assertContains($message, $result);
        }

        // Check that all resolved tables are filled
        $this->assertCount(
            $expectedRecordsCount['productVisibility'],
            $this->getProductVisibilityResolved()
        );
        $this->assertCount(
            $expectedRecordsCount['accountGroupProductVisibility'],
            $this->getAccountGroupProductVisibilityResolved()
        );
        $this->assertCount(
            $expectedRecordsCount['accountProductVisibility'],
            $this->getAccountProductVisibilityResolved()
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
                    'Start the process of building the cache for all websites',
                    'The cache is updated successfully',
                ],
                'expectedCounts' => [
                    'productVisibility' => 20,
                    'accountGroupProductVisibility' => 8,
                    'accountProductVisibility' => 5,
                ]
            ],
            'withExitsIdParam' => [
                'params' => ['--website_id=1'],
                'expectedMessages' =>
                [
                    'Start the process of building the cache for website "Default"',
                    'The cache is updated successfully',
                ],
                'expectedCounts' => [
                    'productVisibility' => 7,
                    'accountGroupProductVisibility' => 6,
                    'accountProductVisibility' => 4,
                ]
            ],
            'withWrongIdParam' => [
                'params' => ['--website_id=0'],
                'expectedMessages' =>
                [
                    'Website id is not valid',
                ],
                'expectedCounts' => [
                    'productVisibility' => 0,
                    'accountGroupProductVisibility' => 0,
                    'accountProductVisibility' => 0,
                ]
            ],
        ];
    }

    /**
     * @return ProductVisibilityResolved[]
     */
    protected function getProductVisibilityResolved()
    {
        return $this->getProductVisibilityResolvedRepository()->findAll();
    }

    /**
     * @return AccountGroupProductVisibilityResolved[]
     */
    protected function getAccountGroupProductVisibilityResolved()
    {
        return $this->getAccountGroupProductVisibilityResolvedRepository()->findAll();
    }

    /**
     * @return AccountGroupProductRepository[]
     */
    protected function getAccountProductVisibilityResolved()
    {
        return $this->getAccountProductVisibilityResolvedRepository()->findAll();
    }

    /**
     * @return ProductRepository
     */
    protected function getProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved');
    }

    /**
     * @return AccountGroupProductRepository
     */
    protected function getAccountGroupProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
    }

    /**
     * @return AccountProductRepository
     */
    protected function getAccountProductVisibilityResolvedRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
    }

    protected function clearAllResolvedTables()
    {
        $this->getProductVisibilityResolvedRepository()
            ->createQueryBuilder('productVisibilityResolved')
            ->delete()
            ->getQuery()
            ->execute();

        $this->getAccountGroupProductVisibilityResolvedRepository()
            ->createQueryBuilder('accountGroupProductVisibilityResolved')
            ->delete()
            ->getQuery()
            ->execute();

        $this->getAccountProductVisibilityResolvedRepository()
            ->createQueryBuilder('accountProductVisibilityResolved')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
