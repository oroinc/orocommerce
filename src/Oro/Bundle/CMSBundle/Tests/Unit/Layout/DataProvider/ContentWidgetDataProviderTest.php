<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Layout\DataProvider\ContentWidgetDataProvider;
use Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget\Stub\ContentWidgetTypeStub;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

final class ContentWidgetDataProviderTest extends TestCase
{
    private ContentWidgetTypeRegistry&MockObject $contentWidgetTypeRegistry;
    private Environment&MockObject $twig;
    private ManagerRegistry&MockObject $doctrine;
    private ThemeConfigurationProvider&MockObject $themeConfigurationProvider;

    private ContentWidgetDataProvider $dataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->contentWidgetTypeRegistry = $this->createMock(ContentWidgetTypeRegistry::class);
        $this->twig = $this->createMock(Environment::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);

        $this->dataProvider = new ContentWidgetDataProvider(
            $this->contentWidgetTypeRegistry,
            $this->twig,
            $this->doctrine,
            $this->themeConfigurationProvider
        );
    }

    public function testGetWidgetData(): void
    {
        $settings = ['param' => 'value'];

        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(ContentWidgetTypeStub::getName());
        $contentWidget->setSettings($settings);

        $this->contentWidgetTypeRegistry->expects(self::once())
            ->method('getWidgetType')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(new ContentWidgetTypeStub());

        self::assertSame(['settings' => $settings], $this->dataProvider->getWidgetData($contentWidget));
    }

    public function testGetWidgetDataWithoutContentWidgetType(): void
    {
        $settings = ['param' => 'value'];

        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(ContentWidgetTypeStub::getName());
        $contentWidget->setSettings($settings);

        $this->contentWidgetTypeRegistry->expects(self::once())
            ->method('getWidgetType')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(null);

        self::assertSame([], $this->dataProvider->getWidgetData($contentWidget));
    }

    public function testGetDefaultTemplate(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(ContentWidgetTypeStub::getName());

        $this->contentWidgetTypeRegistry->expects(self::once())
            ->method('getWidgetType')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(new ContentWidgetTypeStub());

        self::assertSame('<b>default template</b>', $this->dataProvider->getDefaultTemplate($contentWidget));
    }

    public function testGetDefaultTemplateWithoutContentWidgetType(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setWidgetType(ContentWidgetTypeStub::getName());

        $this->contentWidgetTypeRegistry->expects(self::once())
            ->method('getWidgetType')
            ->with(ContentWidgetTypeStub::getName())
            ->willReturn(null);

        self::assertSame('', $this->dataProvider->getDefaultTemplate($contentWidget));
    }

    public function testGetContentWidgetNameByThemeConfigKey(): void
    {
        $contentWidget = (new ContentWidget())->setName('name');
        $key = ThemeConfiguration::buildOptionKey('header', 'promotional_content');

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with($key)
            ->willReturn(1);

        $repo = $this->createMock(ObjectRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ContentWidget::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($contentWidget);

        self::assertSame('name', $this->dataProvider->getContentWidgetNameByThemeConfigKey($key));
    }

    public function testGetContentWidgetNameWhenContentWidgetNoExist(): void
    {
        $key = ThemeConfiguration::buildOptionKey('header', 'promotional_content');

        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with($key)
            ->willReturn(1);

        $repo = $this->createMock(ObjectRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ContentWidget::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        self::assertSame('', $this->dataProvider->getContentWidgetNameByThemeConfigKey($key));
    }

    public function testGetContentWidgetNameWhenNoThemeConfigurationOption(): void
    {
        $key = ThemeConfiguration::buildOptionKey('header', 'promotional_content');
        $this->themeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with($key)
            ->willReturn(null);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        self::assertSame('', $this->dataProvider->getContentWidgetNameByThemeConfigKey($key));
    }

    public function testHasContentWidgetNoWidget(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ContentWidget::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => 'test-widget'])
            ->willReturn(null);

        self::assertFalse($this->dataProvider->hasContentWidget('test-widget'));
    }

    public function testHasContentWidget(): void
    {
        $repo = $this->createMock(ObjectRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ContentWidget::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => 'test-widget'])
            ->willReturn(new ContentWidget());

        self::assertTrue($this->dataProvider->hasContentWidget('test-widget'));
    }
}
