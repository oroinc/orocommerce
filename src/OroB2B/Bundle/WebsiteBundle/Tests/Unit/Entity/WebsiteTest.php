<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider flatPropertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGetSet($property, $value)
    {
        $website = new Website();

        $this->assertNull(call_user_func_array([$website, 'get' . ucfirst($property)], []));
        call_user_func_array(array($website, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($value, call_user_func_array([$website, 'get' . ucfirst($property)], []));
    }

    public function flatPropertiesDataProvider()
    {
        $now = new \DateTime('now');

        return [
            'name'         => ['name', 'test'],
            'url'          => ['url', 'www.test.com'],
            'createdAt'    => ['createdAt', $now],
            'updatedAt'    => ['updatedAt', $now],
        ];
    }

    public function testGetId()
    {
        $websiteId = 1;
        $website = new Website();
        $this->assertNull($website->getId());

        $class = new \ReflectionClass($website);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($website, $websiteId);

        $this->assertEquals($websiteId, $website->getId());
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
