<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentWidget;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetProvider;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;

class ContentWidgetProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ContentWidgetProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new ContentWidgetProvider($this->doctrine);
    }

    public function testGetContentWidget(): void
    {
        $widgetName = 'test';
        $widget = $this->createMock(ContentWidget::class);

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ContentWidget::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => $widgetName])
            ->willReturn($widget);

        self::assertSame($widget, $this->provider->getContentWidget($widgetName));
    }

    public function testGetContentWidgetWhenWidgetDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The context widget "test" does not exist.');

        $widgetName = 'test';

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(ContentWidget::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['name' => $widgetName])
            ->willReturn(null);

        $this->provider->getContentWidget($widgetName);
    }
}
