<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentWidgetTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(
            new ContentWidget(),
            [
                ['id', 42],
                ['name', 'test_name'],
                ['description', 'this is test description'],
                ['createdAt', new \DateTime('now')],
                ['updatedAt', new \DateTime('now')],
                ['organization', new Organization()],
                ['widgetType', 'test_type'],
                ['layout', 'test_layout'],
                ['settings', ['param' => 'value']],
            ]
        );
    }
}
