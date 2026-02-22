<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\CMSBundle\Twig\ContentWidgetTypeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentWidgetTypeExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ContentWidgetTypeRegistry&MockObject $contentWidgetTypeRegistry;
    private ContentWidgetLayoutProvider&MockObject $contentWidgetLayoutProvider;
    private TranslatorInterface&MockObject $translator;
    private ContentWidgetTypeExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $this->contentWidgetLayoutProvider = $this->createMock(ContentWidgetLayoutProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add(ContentWidgetTypeRegistry::class, $this->contentWidgetTypeRegistry)
            ->add(ContentWidgetLayoutProvider::class, $this->contentWidgetLayoutProvider)
            ->add(TranslatorInterface::class, $this->translator)
            ->getContainer($this);

        $this->extension = new ContentWidgetTypeExtension($container);
    }

    public function testGetContentWidgetTypeLabel(): void
    {
        $type = ContentWidgetTypeStub::getName();

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnCallback(static function ($message) {
                return 'translated ' . $message;
            });

        $widgetType = new ContentWidgetTypeStub();

        $this->contentWidgetTypeRegistry->expects(self::once())
            ->method('getWidgetType')
            ->with($type)
            ->willReturn($widgetType);

        self::assertEquals(
            'translated ' . $widgetType->getLabel(),
            self::callTwigFilter($this->extension, 'content_widget_type_label', [$type])
        );
    }

    public function testGetContentWidgetTypeLabelNoWidgetType(): void
    {
        $type = ContentWidgetTypeStub::getName();

        $this->translator->expects(self::never())
            ->method('trans');

        $this->contentWidgetTypeRegistry->expects(self::once())
            ->method('getWidgetType')
            ->with($type)
            ->willReturn(null);

        self::assertEquals(
            $type,
            self::callTwigFilter($this->extension, 'content_widget_type_label', [$type])
        );
    }

    public function testGetContentWidgetLayoutLabel(): void
    {
        $type = ContentWidgetTypeStub::getName();
        $layout = 'template1';

        $this->translator->expects(self::once())
            ->method('trans')
            ->willReturnCallback(static function ($message) {
                return 'translated ' . $message;
            });

        $this->contentWidgetLayoutProvider->expects(self::once())
            ->method('getWidgetLayoutLabel')
            ->with($type, $layout)
            ->willReturn('layout label');

        self::assertEquals(
            'translated layout label',
            self::callTwigFilter($this->extension, 'content_widget_layout_label', [$layout, $type])
        );
    }
}
