<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\RedirectBundle\Entity\Slug;

class PageTest extends EntityTestCase
{
    public function testAccessors()
    {
        $date = new \DateTime();

        $properties = [
            ['id', 1],
            ['title', 'test_title'],
            ['content', 'test_content'],
            ['left', 2],
            ['level', 3],
            ['right', 4],
            ['root', 1],
            ['parentPage', new Page()],
            ['parentPage', null],
            ['currentSlug', new Slug()],
            ['currentSlug', null],
            ['organization', new Organization()],
            ['createdAt', $date, false],
            ['updatedAt', $date, false],
        ];

        $this->assertPropertyAccessors(new Page(), $properties);
    }

    public function testConstruct()
    {
        $page = new Page();

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $page->getChildPages());
        $this->assertEmpty($page->getChildPages()->toArray());

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $page->getSlugs());
        $this->assertEmpty($page->getSlugs()->toArray());

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $page->getCreatedAt());
        $this->assertLessThanOrEqual($now, $page->getCreatedAt());

        $this->assertInstanceOf('DateTime', $page->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $page->getUpdatedAt());
    }

    public function testChildPageAccessors()
    {
        $page = new Page();
        $this->assertEmpty($page->getChildPages()->toArray());

        $firstPage = new Page();
        $firstPage->setLevel(1);

        $secondPage = new Page();
        $secondPage->setLevel(2);

        $page->addChildPage($firstPage)
            ->addChildPage($secondPage)
            ->addChildPage($secondPage);
        $this->assertEquals(
            [$firstPage, $secondPage],
            array_values($page->getChildPages()->toArray())
        );

        $page->removeChildPage($firstPage)
            ->removeChildPage($firstPage);
        $this->assertEquals(
            [$secondPage],
            array_values($page->getChildPages()->toArray())
        );
    }

    public function testSlugAccessors()
    {
        $page = new Page();
        $this->assertEmpty($page->getSlugs()->toArray());

        $firstSlug = new Slug();
        $secondSlug = new Slug();

        $page->addSlug($firstSlug)
            ->addSlug($secondSlug);

        $this->assertEquals(
            [$firstSlug, $secondSlug],
            array_values($page->getSlugs()->toArray())
        );

        $page->removeSlug($firstSlug)
            ->removeSlug($firstSlug);

        $this->assertEquals(
            [$secondSlug],
            array_values($page->getSlugs()->toArray())
        );
    }

    public function testPreUpdate()
    {
        $page = new Page();
        $page->preUpdate();

        $this->assertInstanceOf('DateTime', $page->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $page->getUpdatedAt());
    }

    public function testToString()
    {
        $value = 'test';

        $page = new Page();
        $page->setTitle($value);

        $this->assertEquals($value, (string)$page);
    }
}
