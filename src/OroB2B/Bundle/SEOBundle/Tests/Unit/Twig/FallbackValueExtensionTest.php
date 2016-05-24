<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Unit\Twig;

use OroB2B\Bundle\WebsiteBundle\Entity\Locale;
use OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper;
use OroB2B\Bundle\SEOBundle\Twig\FallbackValueExtension;

use Doctrine\Common\Collections\Collection;

class FallbackValueExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LocaleHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeHelper;

    /**
     * @var FallbackValueExtension
     */
    protected $fallbackValueExtension;

    protected function setUp()
    {
        $this->localeHelper = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Locale\LocaleHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fallbackValueExtension = new FallbackValueExtension($this->localeHelper);
    }

    public function testGetDefaultFallbackValueReturnsNullOfEmptyObject()
    {
        $this->assertNull(
            $this->fallbackValueExtension->getDefaultFallbackValue(null, 'test')
        );
    }

    public function testGetFallbackLocaleValueThrowsException()
    {
        $locale = new Locale();
        $localeString = 'test';
        $this->localeHelper->expects($this->once())
            ->method('getLocale')
            ->with($localeString)
            ->willReturn($locale);

        /** @var Collection|\PHPUnit_Framework_MockObject_MockObject $values * */
        $values = $this->getMock('Doctrine\Common\Collections\Collection');
        $collection = $this->getMock('Doctrine\Common\Collections\Collection');

        $values->expects($this->once())
            ->method('filter')
            ->willReturn($collection);

        $collection->expects($this->once())
            ->method('count')
            ->willReturn(2);

        $this->setExpectedException('\LogicException');
        $this->fallbackValueExtension->getFallbackLocaleValue($values, $localeString);
    }

    public function testGetFallbackLocaleValueReturnsDefaultLocale()
    {
        $locale = new Locale();
        $localeString = 'test';
        $this->localeHelper->expects($this->once())
            ->method('getLocale')
            ->with($localeString)
            ->willReturn($locale);

        /** @var Collection|\PHPUnit_Framework_MockObject_MockObject $values * */
        $values = $this->getMock('Doctrine\Common\Collections\Collection');
        $collection = $this->getMock('Doctrine\Common\Collections\Collection');
        $collection2 = $this->getMock('Doctrine\Common\Collections\Collection');

        $values->expects($this->exactly(2))
            ->method('filter')
            ->will($this->onConsecutiveCalls($collection, $collection2));
        $collection->expects($this->once())
            ->method('count')
            ->willReturn(0);
        $collection2->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $fallbackValue = $this->getMock('OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue');
        $fallbackValue->expects($this->any())
            ->method('getFallback')
            ->willReturn(null);

        $collection2->expects($this->once())
            ->method('first')
            ->willReturn($fallbackValue);

        $fallbackLocaleValue = $this->fallbackValueExtension->getFallbackLocaleValue($values, $localeString);
        $this->assertEquals($fallbackValue, $fallbackLocaleValue);
    }
}
