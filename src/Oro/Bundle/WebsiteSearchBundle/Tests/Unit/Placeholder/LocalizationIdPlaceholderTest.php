<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class LocalizationIdPlaceholderTest extends \PHPUnit_Framework_TestCase
{
    /** @var LocalizationIdPlaceholder */
    private $placeholder;

    /** @var UserLocalizationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $localizationManager;

    protected function setUp()
    {
        $this->localizationManager = $this->getMockBuilder(UserLocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeholder = new LocalizationIdPlaceholder($this->localizationManager);
    }

    protected function tearDown()
    {
        unset($this->placeholder, $this->localizationManager);
    }

    public function testGetPlaceholder()
    {
        $this->assertInternalType('string', $this->placeholder->getPlaceholder());
        $this->assertEquals('LOCALIZATION_ID', $this->placeholder->getPlaceholder());
    }

    public function testReplaceDefault()
    {
        $localization = $this->getMockBuilder(Localization::class)->getMock();

        $this->localizationManager->expects($this->once())
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

        $this->localizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->assertEquals(
            'string_LOCALIZATION_ID',
            $this->placeholder->replaceDefault('string_LOCALIZATION_ID')
        );
    }

    public function testReplace()
    {
        $this->localizationManager->expects($this->never())->method($this->anything());

        $this->assertEquals(
            'string_1',
            $this->placeholder->replace('string_LOCALIZATION_ID', ['LOCALIZATION_ID' => '1'])
        );
    }

    public function testReplaceWithoutValue()
    {
        $this->localizationManager->expects($this->never())->method($this->anything());

        $this->assertEquals(
            'string_LOCALIZATION_ID',
            $this->placeholder->replace('string_LOCALIZATION_ID', ['NOT_LOCALIZATION_ID' => '1'])
        );
    }
}
