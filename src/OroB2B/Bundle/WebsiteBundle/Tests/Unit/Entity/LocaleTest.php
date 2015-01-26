<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Entity;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LocaleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetId()
    {
        $localeId = 1;
        $locale = new Locale();
        $this->assertNull($locale->getId());

        $class = new \ReflectionClass($locale);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($locale, $localeId);

        $this->assertEquals($localeId, $locale->getId());
    }

    /**
     * @dataProvider flatPropertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGetSet($property, $value)
    {
        $locale = new Locale();

        $this->assertNull(call_user_func_array([$locale, 'get' . ucfirst($property)], []));
        call_user_func_array(array($locale, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($value, call_user_func_array([$locale, 'get' . ucfirst($property)], []));
    }

    public function flatPropertiesDataProvider()
    {
        $now = new \DateTime('now');

        return [
            'code'         => ['code', 'test'],
            'createdAt'    => ['createdAt', $now],
            'updatedAt'    => ['updatedAt', $now],
        ];
    }

    public function testChildLocales()
    {
        // Create locales
        $localeOne = new Locale();
        $localeOne->setCode('es_MX');

        $localeTwo = new Locale();
        $localeTwo->setCode('en_GB');

        $localeThree = new Locale();
        $localeThree->setCode('en_AU');

        $parentLocale = new Locale();
        $parentLocale->setCode('en_US');

        // reset children locales
        $this->assertSame($parentLocale, $parentLocale->resetLocales([$localeOne, $localeTwo]));
        $actual = $parentLocale->getChildLocales();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localeOne, $localeTwo], $actual->toArray());

        /** @var Locale $child */
        foreach ($actual as $child) {
            $this->assertEquals($parentLocale->getCode(), $child->getParentLocale()->getCode());
        }

        // add children locales
        $this->assertSame($parentLocale, $parentLocale->addChildLocale($localeTwo));
        $actual = $parentLocale->getChildLocales();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localeOne, $localeTwo], $actual->toArray());

        $this->assertSame($parentLocale, $parentLocale->addChildLocale($localeThree));
        $actual = $parentLocale->getChildLocales();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$localeOne, $localeTwo, $localeThree], $actual->toArray());

        /** @var Locale $child */
        foreach ($actual as $child) {
            $this->assertEquals($parentLocale->getCode(), $child->getParentLocale()->getCode());
        }

        // remove child locale
        $this->assertSame($parentLocale, $parentLocale->removeChildLocale($localeOne));
        $actual = $parentLocale->getChildLocales();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertContains($localeTwo, $actual->toArray());
        $this->assertContains($localeThree, $actual->toArray());
        $this->assertNotContains($localeOne, $actual->toArray());
    }

    public function testLocaleWebsites()
    {
        // Create websites
        $websiteOne = new Website();
        $websiteOne->setName('Website One');
        $websiteOne->setUrl('www.website-one.com');

        $websiteTwo = new Website();
        $websiteTwo->setName('Website Two');
        $websiteTwo->setUrl('www.website-two.com');

        $websiteThree = new Website();
        $websiteThree->setName('Website Three');
        $websiteThree->setUrl('www.website-three.com');

        // Create locale
        $currentLocale = new Locale();
        $currentLocale->setCode('en_US');

        // reset websites for current locale
        $this->assertSame($currentLocale, $currentLocale->resetWebsites([$websiteOne, $websiteTwo]));
        $actual = $currentLocale->getWebsites();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$websiteOne, $websiteTwo], $actual->toArray());

        /** @var Website $website */
        foreach ($actual as $website) {
            $this->assertContains($website, $currentLocale->getWebsites());
        }

        // add websites to current locale
        $this->assertSame($currentLocale, $currentLocale->addWebsite($websiteTwo));
        $actual = $currentLocale->getWebsites();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$websiteOne, $websiteTwo], $actual->toArray());

        $this->assertSame($currentLocale, $currentLocale->addWebsite($websiteThree));
        $actual = $currentLocale->getWebsites();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$websiteOne, $websiteTwo, $websiteThree], $actual->toArray());

        /** @var Website $website */
        foreach ($actual as $website) {
            $this->assertContains($website, $currentLocale->getWebsites());
        }

        // remove websites from current locale
        $this->assertSame($currentLocale, $currentLocale->removeWebsite($websiteOne));
        $actual = $currentLocale->getWebsites();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertContains($websiteTwo, $actual->toArray());
        $this->assertContains($websiteThree, $actual->toArray());
        $this->assertNotContains($websiteOne, $actual->toArray());
    }

    public function testPrePersist()
    {
        $locale = new Locale();

        $this->assertNull($locale->getCreatedAt());
        $this->assertNull($locale->getUpdatedAt());

        $locale->prePersist();
        $this->assertInstanceOf('\DateTime', $locale->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $locale->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $locale = new Locale();

        $this->assertNull($locale->getUpdatedAt());

        $locale->preUpdate();
        $this->assertInstanceOf('\DateTime', $locale->getUpdatedAt());
    }
}
