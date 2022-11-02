<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Layout\DataProvider\ContentWidgetDataProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Twig\Environment;

class ContentWidgetDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /** @var Environment */
    private $twig;

    /** @var ContentWidgetDataProvider */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $this->twig = $this->createMock(Environment::class);

        $this->dataProvider = new ContentWidgetDataProvider($this->contentWidgetTypeRegistry, $this->twig);
    }

    public function testGetWidgetData(): void
    {
        $settings = ['param' => 'value'];

        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(ContentWidgetTypeStub::getName());
        $contentWidget->setSettings($settings);

        $this->contentWidgetTypeRegistry->expects($this->once())
            ->method('getWidgetType')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(new ContentWidgetTypeStub());

        $this->assertSame(['settings' => $settings], $this->dataProvider->getWidgetData($contentWidget));
    }

    public function testGetWidgetDataWithoutContentWidgetType(): void
    {
        $settings = ['param' => 'value'];

        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(ContentWidgetTypeStub::getName());
        $contentWidget->setSettings($settings);

        $this->contentWidgetTypeRegistry->expects($this->once())
            ->method('getWidgetType')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(null);

        $this->assertSame([], $this->dataProvider->getWidgetData($contentWidget));
    }

    public function testGetDefaultTemplate(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(ContentWidgetTypeStub::getName());

        $this->contentWidgetTypeRegistry->expects($this->once())
            ->method('getWidgetType')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(new ContentWidgetTypeStub());

        $this->assertSame('<b>default template</b>', $this->dataProvider->getDefaultTemplate($contentWidget));
    }

    public function testGetDefaultTemplateWithoutContentWidgetType(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(ContentWidgetTypeStub::getName());

        $this->contentWidgetTypeRegistry->expects($this->once())
            ->method('getWidgetType')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(null);

        $this->assertSame('', $this->dataProvider->getDefaultTemplate($contentWidget));
    }
}
