<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\RedirectBundle\Entity\Slug;

class PageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $date = new \DateTime();

        $propertySlug = new Slug();
        $propertySlug->setUrl('/property');

        $propertyPage = new Page();
        $propertyPage->setCurrentSlug($propertySlug);

        $properties = [
            ['id', 1],
            ['title', 'test_title'],
            ['content', 'test_content'],
            ['currentSlug', $propertySlug, false],
            ['organization', new Organization()],
            ['createdAt', $date, false],
            ['updatedAt', $date, false],
        ];

        $testSlug = new Slug();
        $testSlug->setUrl('/test');

        $propertyPage = new Page();
        $propertyPage->setCurrentSlug($testSlug);

        $this->assertPropertyAccessors($propertyPage, $properties);
    }

    public function testConstruct()
    {
        $page = new Page();

        $slug = new Slug();
        $slug->setUrl('/');
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $page->getSlugs());
        $this->assertEquals([$slug], $page->getSlugs()->toArray());

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $page->getCreatedAt());
        $this->assertLessThanOrEqual($now, $page->getCreatedAt());

        $this->assertInstanceOf('DateTime', $page->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $page->getUpdatedAt());
    }

    public function testSlugAccessors()
    {
        $emptySlug = new Slug();
        $emptySlug->setUrl('/');

        $page = new Page();
        $this->assertEquals([$emptySlug], $page->getSlugs()->toArray());

        $firstSlug = new Slug();
        $secondSlug = new Slug();

        $page->addSlug($firstSlug)
            ->addSlug($secondSlug);

        $this->assertEquals(
            [$emptySlug, $firstSlug, $secondSlug],
            array_values($page->getSlugs()->toArray())
        );

        $page->removeSlug($firstSlug)
            ->removeSlug($firstSlug);

        $this->assertEquals(
            [$emptySlug, $secondSlug],
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

    public function testSetCurrentSlug()
    {
        $emptySlug = new Slug();
        $emptySlug->setUrl('/');

        $page = new Page();

        $this->assertEquals('/', $page->getCurrentSlug()->getUrl());
        $this->assertEquals([$emptySlug], $page->getSlugs()->toArray());

        $slug = new Slug();
        $slug->setUrl('test');
        $page->setCurrentSlug($slug);

        $this->assertEquals($slug, $page->getCurrentSlug());
        $this->assertEquals([$emptySlug, $slug], $page->getSlugs()->toArray());
    }

    public function testSetCurrentSlugUrl()
    {
        $rootSlug = new Slug();
        $rootSlug->setUrl('/root');
        $rootPage = new Page();
        $rootPage->setCurrentSlug($rootSlug);

        $this->assertEquals('/root', $rootPage->getCurrentSlugUrl());

        $rootPage->setCurrentSlugUrl('root-altered');
        $this->assertEquals('/root-altered', $rootPage->getCurrentSlugUrl());
    }
}
