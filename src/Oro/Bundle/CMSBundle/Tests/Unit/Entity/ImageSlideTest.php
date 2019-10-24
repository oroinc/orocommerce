<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
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
                ['order', 1001],
                ['url', 'test/url/path'],
                ['displayInSameWindow', true, false],
                ['title', 'test title'],
                ['text', 'test text'],
                ['mainImage', new File()],
                ['mediumImage', new File()],
                ['smallImage', new File()],
                ['organization', new Organization()],
            ]
        );
    }
}
