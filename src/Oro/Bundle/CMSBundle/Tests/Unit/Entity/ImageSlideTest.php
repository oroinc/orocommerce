<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ImageSlideTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $this->assertPropertyAccessors(
            new ImageSlide(),
            [
                ['id', 42],
                ['contentWidget', new ContentWidget()],
                ['slideOrder', 1001],
                ['url', 'test/url/path'],
                ['displayInSameWindow', true, false],
                ['title', 'test title'],
                ['text', 'test text'],
                ['textAlignment', 'center'],
                ['organization', new Organization()],
            ]
        );
    }
}
