<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
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

    public function testWebsiteLocalizations()
    {
        // Create localizations
        $localizationOne = new Localization();
        $localizationOne->setFormattingCode('es_MX');

        $localizationTwo = new Localization();
        $localizationTwo->setFormattingCode('en_GB');

        $localizationThree = new Localization();
        $localizationThree->setFormattingCode('en_AU');

        // Create website
        $currentWebsite = new Website();
        $currentWebsite->setName('Current Website');
        $currentWebsite->setUrl('www.current-website.com');

        // reset localizations for current website
        $this->assertSame($currentWebsite, $currentWebsite->resetLocalizations([$localizationOne, $localizationTwo]));
        $actual = $currentWebsite->getLocalizations();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localizationOne, $localizationTwo], $actual->toArray());

        /** @var Localization $localization */
        foreach ($actual as $localization) {
            $this->assertContains($localization, $currentWebsite->getLocalizations());
        }

        // add localizations to current website
        $this->assertSame($currentWebsite, $currentWebsite->addLocalization($localizationTwo));
        $actual = $currentWebsite->getLocalizations();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localizationOne, $localizationTwo], $actual->toArray());

        $this->assertSame($currentWebsite, $currentWebsite->addLocalization($localizationThree));
        $actual = $currentWebsite->getLocalizations();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localizationOne, $localizationTwo, $localizationThree], $actual->toArray());

        /** @var Localization $localization */
        foreach ($actual as $localization) {
            $this->assertContains($localization, $currentWebsite->getLocalizations());
        }

        // remove localizations from current website
        $this->assertSame($currentWebsite, $currentWebsite->removeLocalization($localizationOne));
        $actual = $currentWebsite->getLocalizations();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertContains($localizationTwo, $actual->toArray());
        $this->assertContains($localizationThree, $actual->toArray());
        $this->assertNotContains($localizationOne, $actual->toArray());
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
