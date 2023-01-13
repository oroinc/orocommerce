<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class LocalizationIdPlaceholderTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationProvider;

    /** @var LocalizationIdPlaceholder */
    private $placeholder;

    protected function setUp(): void
    {
        $this->localizationProvider = $this->createMock(CurrentLocalizationProvider::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->placeholder = new LocalizationIdPlaceholder(
            $this->localizationProvider,
            $this->localizationManager
        );
    }

    public function testGetPlaceholder(): void
    {
        $this->assertIsString($this->placeholder->getPlaceholder());
        $this->assertEquals('LOCALIZATION_ID', $this->placeholder->getPlaceholder());
    }

    public function testReplaceDefaultByCurrentLocalization(): void
    {
        $localization = $this->createMock(Localization::class);

        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $localization->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $value = $this->placeholder->replaceDefault('string_LOCALIZATION_ID');

        $this->assertIsString($value);
        $this->assertEquals('string_1', $value);
    }

    public function testReplaceDefaultByDefaultLocalization(): void
    {
        $localization = $this->createMock(Localization::class);

        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->localizationManager->expects($this->once())
            ->method('getDefaultLocalization')
            ->willReturn($localization);

        $localization->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $value = $this->placeholder->replaceDefault('string_LOCALIZATION_ID');

        $this->assertIsString($value);
        $this->assertEquals('string_1', $value);
    }

    public function testGetValueWithUnknownLocalization(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Can't get current localization");

        $this->localizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->localizationManager->expects($this->once())
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->assertEquals(
            'string_LOCALIZATION_ID',
            $this->placeholder->replaceDefault('string_LOCALIZATION_ID')
        );
    }

    public function testReplace(): void
    {
        $this->localizationProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            'string_1',
            $this->placeholder->replace('string_LOCALIZATION_ID', ['LOCALIZATION_ID' => 1])
        );
    }

    public function testReplaceWithoutValue(): void
    {
        $this->localizationProvider->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
            'string_LOCALIZATION_ID',
            $this->placeholder->replace('string_LOCALIZATION_ID', ['NOT_LOCALIZATION_ID' => 1])
        );
    }
}
