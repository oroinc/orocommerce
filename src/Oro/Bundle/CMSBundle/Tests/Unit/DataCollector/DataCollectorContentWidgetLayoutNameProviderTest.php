<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\DataCollector;

use Oro\Bundle\CMSBundle\DataCollector\DataCollectorContentWidgetLayoutNameProvider;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Component\Layout\LayoutContext;

class DataCollectorContentWidgetLayoutNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getNameByContextDataProvider
     */
    public function testGetNameByContext(LayoutContext $context, string $expected): void
    {
        $provider = new DataCollectorContentWidgetLayoutNameProvider();

        self::assertEquals($expected, $provider->getNameByContext($context));
    }

    public function getNameByContextDataProvider(): array
    {
        return [
            ['context' => new LayoutContext(), 'expected' => ''],
            ['context' => new LayoutContext(['content_widget' => new \stdClass()]), 'expected' => ''],
            ['context' => new LayoutContext(['content_widget' => new ContentWidget()]), 'expected' => ''],
            [
                'context' => new LayoutContext(
                    ['content_widget' => (new ContentWidget())->setWidgetType('sample_type')]
                ),
                'expected' => 'Content Widget: sample_type',
            ],
        ];
    }
}
