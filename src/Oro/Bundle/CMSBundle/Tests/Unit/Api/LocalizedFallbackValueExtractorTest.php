<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Api;

use Oro\Bundle\CMSBundle\Api\LocalizedFallbackValueExtractor;
use Oro\Bundle\CMSBundle\Api\WYSIWYGValueRenderer;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\WYSIWYGLocalizedFallbackValue;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\LocaleBundle\Api\LocalizedFallbackValueExtractorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

class LocalizedFallbackValueExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LocalizedFallbackValueExtractorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerValueExtractor;

    /** @var WYSIWYGValueRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygValueRenderer;

    /** @var LocalizedFallbackValueExtractor */
    private $valueExtractor;

    protected function setUp(): void
    {
        $this->innerValueExtractor = $this->createMock(LocalizedFallbackValueExtractorInterface::class);
        $this->wysiwygValueRenderer = $this->createMock(WYSIWYGValueRenderer::class);

        $this->valueExtractor = new LocalizedFallbackValueExtractor(
            $this->innerValueExtractor,
            $this->wysiwygValueRenderer,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testExtractValueForEmptyLocalizedFallbackValue(): void
    {
        $value = new LocalizedFallbackValue();

        $this->wysiwygValueRenderer->expects(self::never())
            ->method('render');
        $this->innerValueExtractor->expects(self::once())
            ->method('extractValue')
            ->with(self::identicalTo($value))
            ->willReturn(null);

        self::assertNull($this->valueExtractor->extractValue($value));
    }

    public function testExtractValueForLocalizedFallbackValueWithoutWysiwygFields(): void
    {
        $value = new LocalizedFallbackValue();

        $this->wysiwygValueRenderer->expects(self::never())
            ->method('render');
        $this->innerValueExtractor->expects(self::once())
            ->method('extractValue')
            ->with(self::identicalTo($value))
            ->willReturn('test');

        self::assertEquals('test', $this->valueExtractor->extractValue($value));
    }

    public function testExtractValueForLocalizedFallbackValueWithEmptyWysiwygFields(): void
    {
        $value = new WYSIWYGLocalizedFallbackValue();

        $this->wysiwygValueRenderer->expects(self::never())
            ->method('render');
        $this->innerValueExtractor->expects(self::once())
            ->method('extractValue')
            ->with(self::identicalTo($value))
            ->willReturn('test');

        self::assertEquals('test', $this->valueExtractor->extractValue($value));
    }

    public function testExtractValueForLocalizedFallbackValueWithWysiwygField(): void
    {
        $value = new WYSIWYGLocalizedFallbackValue();
        $value->setWysiwyg('value');
        $value->setWysiwygStyle('style');

        $this->wysiwygValueRenderer->expects(self::once())
            ->method('render')
            ->with('value', 'style')
            ->willReturn('rendered');
        $this->innerValueExtractor->expects(self::never())
            ->method('extractValue');

        self::assertEquals('rendered', $this->valueExtractor->extractValue($value));
    }

    public function testExtractValueForLocalizedFallbackValueWithEmptyWysiwygValueField(): void
    {
        $value = new WYSIWYGLocalizedFallbackValue();
        $value->setWysiwygStyle('style');

        $this->wysiwygValueRenderer->expects(self::once())
            ->method('render')
            ->with(self::isNull(), 'style')
            ->willReturn('rendered');
        $this->innerValueExtractor->expects(self::never())
            ->method('extractValue');

        self::assertEquals('rendered', $this->valueExtractor->extractValue($value));
    }

    public function testExtractValueForLocalizedFallbackValueWithEmptyWysiwygStyleField(): void
    {
        $value = new WYSIWYGLocalizedFallbackValue();
        $value->setWysiwyg('value');

        $this->wysiwygValueRenderer->expects(self::once())
            ->method('render')
            ->with('value', self::isNull())
            ->willReturn('rendered');
        $this->innerValueExtractor->expects(self::never())
            ->method('extractValue');

        self::assertEquals('rendered', $this->valueExtractor->extractValue($value));
    }
}
