<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class LocalizationIdPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationIdPlaceholder */
    private $placeholder;

    /** @var CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationProvider;

    protected function setUp()
    {
        $this->localizationProvider = $this->getMockBuilder(CurrentLocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeholder = new LocalizationIdPlaceholder($this->localizationProvider);
    }

    protected function tearDown()
    {
        unset($this->placeholder, $this->localizationProvider);
    }

    public function testGetPlaceholder()
    {
        $this->assertInternalType('string', $this->placeholder->getPlaceholder());
        $this->assertEquals('LOCALIZATION_ID', $this->placeholder->getPlaceholder());
    }

    public function testReplaceDefault()
    {
        $localization = $this->getMockBuilder(Localization::class)->getMock();

        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $localization->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $value = $this->placeholder->replaceDefault('string_LOCALIZATION_ID');

        $this->assertInternalType('string', $value);
        $this->assertEquals('string_1', $value);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Can't get current localization
     */
    public function testGetValueWithUnknownLocalization()
    {
        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->assertEquals(
            'string_LOCALIZATION_ID',
            $this->placeholder->replaceDefault('string_LOCALIZATION_ID')
        );
    }

    public function testReplace()
    {
        $this->localizationProvider->expects($this->never())->method($this->anything());

        $this->assertEquals(
            'string_1',
            $this->placeholder->replace('string_LOCALIZATION_ID', ['LOCALIZATION_ID' => 1])
        );
    }

    public function testReplaceWithoutValue()
    {
        $this->localizationProvider->expects($this->never())->method($this->anything());

        $this->assertEquals(
            'string_LOCALIZATION_ID',
            $this->placeholder->replace('string_LOCALIZATION_ID', ['NOT_LOCALIZATION_ID' => 1])
        );
    }
}
