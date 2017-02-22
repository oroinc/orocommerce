<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CMSBundle\Tests\Unit\Entity\Stub\Page;

class PageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new Page(), [
            ['id', 1],
            ['content', 'test_content'],
            ['organization', new Organization()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['slugPrototypesWithRedirect', new SlugPrototypesWithRedirect(new ArrayCollection(), false), false],
        ]);

        $this->assertPropertyCollections(new Page(), [
            ['slugPrototypes', new LocalizedFallbackValue()],
            ['slugs', new Slug()],
        ]);
    }

    public function testToString()
    {
        $value = 'test';

        $page = new Page();
        $page->addTitle((new LocalizedFallbackValue())->setString($value));

        $this->assertEquals($value, (string)$page);
    }
}
