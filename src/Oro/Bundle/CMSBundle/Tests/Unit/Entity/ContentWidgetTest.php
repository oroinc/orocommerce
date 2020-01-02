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

    public function testToString(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');

        $this->assertEquals('name:test_name', $contentWidget->toString());
    }

    public function testGetHash(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');

        $this->assertEquals('0fe1d212bc31c0eb10b7836616fb8a02', $contentWidget->getHash());
    }
}
