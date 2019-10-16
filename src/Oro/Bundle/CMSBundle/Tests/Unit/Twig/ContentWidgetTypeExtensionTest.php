<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\CMSBundle\Twig\ContentWidgetTypeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentWidgetTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ContentWidgetTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetTypeRegistry;

    /** @var ContentWidgetTypeExtension */
    private $extension;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);

        $this->extension = new ContentWidgetTypeExtension($this->translator, $this->contentWidgetTypeRegistry);
    }

    public function testGetContentWidgetTypeLabel(): void
    {
        $type = ContentWidgetTypeStub::getName();

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturnCallback(
                static function ($message) {
                    return $message;
                }
            );

        $widgetType = new ContentWidgetTypeStub();

        $this->contentWidgetTypeRegistry->expects($this->once())
            ->method('getWidgetType')
            ->with($type)
            ->willReturn($widgetType);

        $this->assertEquals(
            $widgetType->getLabel(),
            self::callTwigFilter($this->extension, 'content_widget_type_label', [$type])
        );
    }

    public function testGetContentWidgetTypeLabelNoWidgetType(): void
    {
        $type = ContentWidgetTypeStub::getName();

        $this->translator->expects($this->never())
            ->method('trans');

        $widgetType = new ContentWidgetTypeStub();

        $this->contentWidgetTypeRegistry->expects($this->once())
            ->method('getWidgetType')
            ->with($type)
            ->willReturn(null);

        $this->assertEquals(
            $type,
            self::callTwigFilter($this->extension, 'content_widget_type_label', [$type])
        );
    }
}
