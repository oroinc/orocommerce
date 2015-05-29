<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteTest extends EntityTestCase
{

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 1],
            ['name', 'test'],
            ['url', 'www.test.com'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['priceList', new PriceList()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['priceList', $this->createPriceListEntity()]
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

    public function testWebsiteLocales()
    {
        // Create locales
        $localeOne = new Locale();
        $localeOne->setCode('es_MX');

        $localeTwo = new Locale();
        $localeTwo->setCode('en_GB');

        $localeThree = new Locale();
        $localeThree->setCode('en_AU');

        // Create website
        $currentWebsite = new Website();
        $currentWebsite->setName('Current Website');
        $currentWebsite->setUrl('www.current-website.com');

        // reset locales for current website
        $this->assertSame($currentWebsite, $currentWebsite->resetLocales([$localeOne, $localeTwo]));
        $actual = $currentWebsite->getLocales();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localeOne, $localeTwo], $actual->toArray());

        /** @var Locale $locale */
        foreach ($actual as $locale) {
            $this->assertContains($locale, $currentWebsite->getLocales());
        }

        // add locales to current website
        $this->assertSame($currentWebsite, $currentWebsite->addLocale($localeTwo));
        $actual = $currentWebsite->getLocales();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localeOne, $localeTwo], $actual->toArray());

        $this->assertSame($currentWebsite, $currentWebsite->addLocale($localeThree));
        $actual = $currentWebsite->getLocales();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localeOne, $localeTwo, $localeThree], $actual->toArray());

        /** @var Locale $locale */
        foreach ($actual as $locale) {
            $this->assertContains($locale, $currentWebsite->getLocales());
        }

        // remove locales from current website
        $this->assertSame($currentWebsite, $currentWebsite->removeLocale($localeOne));
        $actual = $currentWebsite->getLocales();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertContains($localeTwo, $actual->toArray());
        $this->assertContains($localeThree, $actual->toArray());
        $this->assertNotContains($localeOne, $actual->toArray());
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

    /**
     * @return PriceList
     */
    protected function createPriceListEntity()
    {
        return new PriceList();
    }
}
