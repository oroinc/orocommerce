<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\CMSBundle\Twig\ContentWidgetTypeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentWidgetTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ContentWidgetTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetTypeRegistry;

    /** @var ContentWidgetLayoutProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contentWidgetLayoutProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ContentWidgetTypeExtension */
    private $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $this->contentWidgetLayoutProvider = $this->createMock(ContentWidgetLayoutProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add(TranslatorInterface::class, $this->translator)
            ->add('oro_cms.content_widget.type_registry', $this->contentWidgetTypeRegistry)
            ->add('oro_cms.provider.content_widget_layout', $this->contentWidgetLayoutProvider)
            ->getContainer($this);

        $this->extension = new ContentWidgetTypeExtension($container);
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

    public function testGetContentWidgetLayoutLabel(): void
    {
        $type = ContentWidgetTypeStub::getName();
        $layout = 'template1';

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturnCallback(
                static function ($message) {
                    return 'translated ' . $message;
                }
            );

        $this->contentWidgetLayoutProvider->expects($this->once())
            ->method('getWidgetLayoutLabel')
            ->with($type, $layout)
            ->willReturn('layout label');

        $this->assertEquals(
            'translated layout label',
            self::callTwigFilter($this->extension, 'content_widget_layout_label', [$layout, $type])
        );
    }
}
