<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class WebsiteRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWebsiteData::class]);
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
     * @dataProvider getAllWebsitesProvider
     */
    public function testGetWebsiteIdentifiers(array $websites)
    {
        $websites = array_map(
            function ($websiteReference) {
                if ($websiteReference === 'Default') {
                    return $this->getRepository()->getDefaultWebsite()->getId();
                } else {
                    return $this->getReference($websiteReference)->getId();
                }
            },
            $websites
        );
        $this->assertEquals($websites, $this->getRepository()->getWebsiteIdentifiers());
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
