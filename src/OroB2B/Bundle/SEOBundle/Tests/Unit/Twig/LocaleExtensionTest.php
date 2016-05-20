<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\SEOBundle\Twig\LocaleExtension;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocaleExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFallbackLocaleValueShouldThrowLogicError()
    {
        $extension = new LocaleExtension();
        $mainLocale = new Locale();

        $locales = new ArrayCollection();
        $locale1 = new LocalizedFallbackValue();
        $locale1->setLocale($mainLocale);
        $locale1->setString('test1');
        $locale2 = new LocalizedFallbackValue();
        $locale2->setLocale($mainLocale);
        $locale2->setString('test2');
        $locales->add($locale1);
        $locales->add($locale2);

        $this->setExpectedException('\LogicException');
        $extension->getFallbackLocaleValue($mainLocale, $locales);
    }

    public function testGetFallbackLocaleValueShouldReturnLocleProvided()
    {
        $extension = new LocaleExtension();
        $mainLocale = new Locale();

        $locales = new ArrayCollection();
        $locale1 = new LocalizedFallbackValue();
        $locale1->setLocale($mainLocale);
        $locale1->setString('test1');
        $locale2 = new LocalizedFallbackValue();
        $locale2->setLocale(new Locale());
        $locale2->setString('test2');
        $locales->add($locale1);
        $locales->add($locale2);

        $returnedLocale = $extension->getFallbackLocaleValue($mainLocale, $locales);

        $this->assertEquals(
            $locale1,
            $returnedLocale
        );
    }

    public function testGetFallbackLocaleReturnsFallbackWithNoLocale()
    {
        $extension = new LocaleExtension();
        $mainLocale = new Locale();

        $locales = new ArrayCollection();
        $locale1 = new LocalizedFallbackValue();
        $locale1->setString('test1');
        $locales->add($locale1);

        $returnedLocale = $extension->getFallbackLocaleValue($mainLocale, $locales);

        $this->assertEquals(
            $locale1,
            $returnedLocale
        );
    }
}
