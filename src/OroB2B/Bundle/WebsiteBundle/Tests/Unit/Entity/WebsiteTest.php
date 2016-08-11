<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 1],
            ['name', 'test'],
            ['url', 'www.test.com'],
            ['owner', new BusinessUnit()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $this->assertPropertyAccessors(new Website(), $properties);
    }

    public function testWebsiteRelationships()
    {
        // Create websites
        $firstWebsite = new Website();
        $firstWebsite->setName('First Website');
        $firstWebsite->setUrl('www.first-website.com');

        $secondWebsite = new Website();
        $secondWebsite->setName('Second Website');
        $secondWebsite->setUrl('www.second-website.com');

        $thirdWebsite = new Website();
        $thirdWebsite->setName('Third Website');
        $thirdWebsite->setUrl('www.third-website.com');

        $this->assertEmpty($firstWebsite->getRelatedWebsites()->toArray());
        $this->assertEmpty($secondWebsite->getRelatedWebsites()->toArray());
        $this->assertEmpty($thirdWebsite->getRelatedWebsites()->toArray());

        // Add relationships between sites
        $secondWebsite->addRelatedWebsite($firstWebsite);
        $thirdWebsite->addRelatedWebsite($secondWebsite);

        $firstWebsiteRelatedSites  = $firstWebsite->getRelatedWebsites()->toArray();
        $this->assertCount(2, $firstWebsiteRelatedSites);
        $this->assertContains($secondWebsite, $firstWebsiteRelatedSites);
        $this->assertContains($thirdWebsite, $firstWebsiteRelatedSites);

        $secondWebsiteRelatedSites = $secondWebsite->getRelatedWebsites()->toArray();
        $this->assertCount(2, $secondWebsiteRelatedSites);
        $this->assertContains($firstWebsite, $secondWebsiteRelatedSites);
        $this->assertContains($thirdWebsite, $secondWebsiteRelatedSites);

        $thirdWebsiteRelatedSites  = $thirdWebsite->getRelatedWebsites()->toArray();
        $this->assertCount(2, $thirdWebsiteRelatedSites);
        $this->assertContains($firstWebsite, $thirdWebsiteRelatedSites);
        $this->assertContains($secondWebsite, $thirdWebsiteRelatedSites);

        // Remove relationship
        $secondWebsite->removeRelatedWebsite($thirdWebsite);

        $firstWebsiteRelatedSites  = $firstWebsite->getRelatedWebsites()->toArray();
        $this->assertCount(1, $firstWebsiteRelatedSites);
        $this->assertContains($secondWebsite, $firstWebsiteRelatedSites);

        $secondWebsiteRelatedSites = $secondWebsite->getRelatedWebsites()->toArray();
        $this->assertCount(1, $secondWebsiteRelatedSites);
        $this->assertContains($firstWebsite, $secondWebsiteRelatedSites);

        $this->assertEmpty($thirdWebsite->getRelatedWebsites()->toArray());
    }


    public function testPrePersist()
    {
        $website = new Website();
        $website->prePersist();
        $this->assertInstanceOf('\DateTime', $website->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $website->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $website = new Website();
        $website->preUpdate();
        $this->assertInstanceOf('\DateTime', $website->getUpdatedAt());
    }
}
