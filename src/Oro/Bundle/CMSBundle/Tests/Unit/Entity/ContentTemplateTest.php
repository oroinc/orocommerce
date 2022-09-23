<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\CMSBundle\Entity\ContentTemplate;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentTemplateTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        self::assertPropertyAccessors(new ContentTemplate(), [
            ['id', 1],
            ['name', 'sample_name'],
            ['enabled', true],
            ['content', 'sample_content'],
            ['contentStyle', 'sample_content_style'],
            ['contentProperties', ['sample_key' => 'sample_value']],
            ['organization', new Organization()],
            ['owner', new User()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
    }
}
