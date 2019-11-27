<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ContentWidgetUsage;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentWidgetUsageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(
            new ContentWidgetUsage(),
            [
                ['id', 42],
                ['contentWidget', new ContentWidget()],
                ['entityClass', \stdClass::class],
                ['entityId', 4242],
                ['entityField', 'testFields']
            ]
        );
    }
}
