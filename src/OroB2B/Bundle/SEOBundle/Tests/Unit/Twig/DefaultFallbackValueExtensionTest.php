<?php

namespace OroB2B\Bundle\SEOBundle\Unit\Twig;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\SEOBundle\Tests\Unit\Entity\Stub\CategoryStub;
use OroB2B\Bundle\SEOBundle\Twig\DefaultFallbackValueExtension;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class DefaultFallbackValueExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDefaultFallbackValueReturnsNull()
    {
        $extension = new DefaultFallbackValueExtension();

        $this->assertNull($extension->getDefaultFallbackValue(null, 'test'));
    }

    public function testGetDefaultFallbackValueThrowsException()
    {
        $category = new CategoryStub();
        $fallbackValue1 = new LocalizedFallbackValue();
        $fallbackValue2 = new LocalizedFallbackValue();
        $category->addMetaTitles($fallbackValue1);
        $category->addMetaTitles($fallbackValue2);

        $extension = new DefaultFallbackValueExtension();
        $this->setExpectedException('LogicException');
        $extension->getDefaultFallbackValue($category, 'metaTitles');
    }

    public function testGetDefaultFallbackValueReturnsDefault()
    {
        $category = new CategoryStub();
        $fallbackValue1 = new LocalizedFallbackValue();
        $fallbackValue1->setLocale(new Locale());
        $fallbackValue2 = new LocalizedFallbackValue();
        $category->addMetaTitles($fallbackValue1);
        $category->addMetaTitles($fallbackValue2);

        $extension = new DefaultFallbackValueExtension();
        $result = $extension->getDefaultFallbackValue($category, 'metaTitles');
        $this->assertEquals($fallbackValue2, $result);
    }
}
