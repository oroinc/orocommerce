<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\TabbedContentItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TabbedContentItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(
            new TabbedContentItem(),
            [
                ['id', 42],
                ['title', 'sample title'],
                ['itemOrder', 1],
                ['content', 'sample content'],
                ['contentStyle', 'sample content style'],
                ['contentProperties', ['sample content properties']],
                ['contentWidget', new ContentWidget()],
                ['organization', new Organization()],
                ['createdAt', new \DateTime()],
                ['updatedAt', new \DateTime()],
            ]
        );
    }
}
