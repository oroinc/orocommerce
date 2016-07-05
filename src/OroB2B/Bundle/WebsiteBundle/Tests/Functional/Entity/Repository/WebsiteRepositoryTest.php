<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class WebsiteRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData']);
    }

    /**
     * @param array $expectedData
     *
     * @dataProvider getAllWebsitesProvider
     */
    public function testGetAllWebsites(array $expectedData)
    {
        $websites = $this->getRepository()->getAllWebsites();
        $websites = array_map(
            function (Website $website) {
                return $website->getName();
            },
            $websites
        );
        $this->assertEquals($expectedData, $websites);
    }

    /**
     * @return array
     */
    public function getAllWebsitesProvider()
    {
        return [
            [
                'expected' => [
                    'Default',
                    'US',
                    'Canada',
                    'CA'
                ],
            ],
        ];
    }

    public function testGetDefaultWebsite()
    {
        $defaultWebsite = $this->getRepository()->getDefaultWebsite();
        $this->assertEquals('Default', $defaultWebsite->getName());
    }

    /**
     * @dataProvider getAllWebsitesProvider
     *
     * @param array $expectedWebsiteNames
     */
    public function testBatchIterator(array $expectedWebsiteNames)
    {
        $websitesIterator = $this->getRepository()->getBatchIterator();

        $websiteNames = [];
        foreach ($websitesIterator as $website) {
            $websiteNames[] = $website->getName();
        }

        $this->assertEquals($expectedWebsiteNames, $websiteNames);
    }

    /**
     * @return WebsiteRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_website.entity.website.class')
        );
    }
}
