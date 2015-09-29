<?php

namespace OroB2B\Bundle\CMSBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\CMSBundle\Entity\Page;
use OroB2B\Bundle\RedirectBundle\Entity\Slug;

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
            ['left', 2],
            ['level', 3],
            ['right', 4],
            ['root', 1],
            ['currentSlug', $propertySlug, false],
            ['parentPage', $propertyPage],
            ['parentPage', null],
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

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $page->getChildPages());
        $this->assertEmpty($page->getChildPages()->toArray());

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

    public function testChildPageAccessors()
    {
        $slug = new Slug();
        $slug->setUrl('/root');
        $page = new Page();
        $page->setCurrentSlug($slug);
        $this->assertEmpty($page->getChildPages()->toArray());

        $firstSlug = new Slug();
        $firstSlug->setUrl('/first');
        $firstPage = new Page();
        $firstPage->setLevel(1);
        $firstPage->setCurrentSlug($firstSlug);

        $secondSlug = new Slug();
        $secondSlug->setUrl('/second');
        $secondPage = new Page();
        $secondPage->setLevel(2);
        $secondPage->setCurrentSlug($secondSlug);

        $page->addChildPage($firstPage)
            ->addChildPage($secondPage)
            ->addChildPage($secondPage);
        $this->assertEquals(
            [$firstPage, $secondPage],
            array_values($page->getChildPages()->toArray())
        );
        $this->assertEquals('/root/first', $firstPage->getCurrentSlugUrl());
        $this->assertEquals('/root/second', $secondPage->getCurrentSlugUrl());

        $page->removeChildPage($firstPage)
            ->removeChildPage($firstPage);
        $this->assertEquals(
            [$secondPage],
            array_values($page->getChildPages()->toArray())
        );

        $this->assertEquals('/first', $firstPage->getCurrentSlugUrl());
        $this->assertEquals('/root/second', $secondPage->getCurrentSlugUrl());
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

        $childSlug = new Slug();
        $childSlug->setUrl('/first');
        $childPage = new Page();
        $childPage->setCurrentSlug($childSlug);

        $rootPage->addChildPage($childPage);

        $childPage->setCurrentSlugUrl('first-altered');
        $this->assertEquals('/root', $rootPage->getCurrentSlugUrl());
        $this->assertEquals('/root/first-altered', $childPage->getCurrentSlugUrl());

        $rootPage->setCurrentSlugUrl('root-altered');
        $this->assertEquals('/root-altered', $rootPage->getCurrentSlugUrl());
        $this->assertEquals('/root-altered/first-altered', $childPage->getCurrentSlugUrl());
    }
}
