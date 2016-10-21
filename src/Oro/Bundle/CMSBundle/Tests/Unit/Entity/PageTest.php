<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CMSBundle\Entity\Page;

class PageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new Page(), [
            ['id', 1],
            ['title', 'test_title'],
            ['content', 'test_content'],
            ['organization', new Organization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);

        $this->assertPropertyCollections(new Page(), [
            ['slugs', new LocalizedFallbackValue()],
        ]);
    }

    public function testToString()
    {
        $value = 'test';

        $page = new Page();
        $page->setTitle($value);

        $this->assertEquals($value, (string)$page);
    }
}
