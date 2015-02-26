<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\CategoryTitle;

class CategoryTest extends EntityTestCase
{
    public function testAccessors()
    {
        $date = new \DateTime();

        $properties = [
            ['id', 1],
            ['left', 2],
            ['level', 3],
            ['right', 4],
            ['root', 1],
            ['parentCategory', new Category()],
            ['parentCategory', null],
            ['createdAt', $date, false],
            ['updatedAt', $date, false],
        ];

        $this->assertPropertyAccessors(new Category(), $properties);
    }

    public function testConstruct()
    {
        $category = new Category();

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $category->getTitles());
        $this->assertEmpty($category->getTitles()->toArray());

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $category->getChildCategories());
        $this->assertEmpty($category->getChildCategories()->toArray());

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $category->getCreatedAt());
        $this->assertLessThanOrEqual($now, $category->getCreatedAt());

        $this->assertInstanceOf('DateTime', $category->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $category->getUpdatedAt());
    }

    public function testTitleAccessors()
    {
        $category = new Category();
        $this->assertEmpty($category->getTitles()->toArray());

        $firstTitle = new CategoryTitle();
        $firstTitle->setValue('first');

        $secondTitle = new CategoryTitle();
        $secondTitle->setValue('second');

        $category->addTitle($firstTitle)
            ->addTitle($secondTitle)
            ->addTitle($secondTitle);
        $this->assertEquals([$firstTitle, $secondTitle], array_values($category->getTitles()->toArray()));

        $category->removeTitle($firstTitle)
            ->removeTitle($firstTitle);
        $this->assertEquals([$secondTitle], array_values($category->getTitles()->toArray()));
    }

    public function testChildCategoryAccessors()
    {
        $category = new Category();
        $this->assertEmpty($category->getChildCategories()->toArray());

        $firstCategory = new Category();
        $firstCategory->setLevel(1);

        $secondCategory = new Category();
        $secondCategory->setLevel(2);

        $category->addChildCategory($firstCategory)
            ->addChildCategory($secondCategory)
            ->addChildCategory($secondCategory);
        $this->assertEquals(
            [$firstCategory, $secondCategory],
            array_values($category->getChildCategories()->toArray())
        );

        $category->removeChildCategory($firstCategory)
            ->removeChildCategory($firstCategory);
        $this->assertEquals(
            [$secondCategory],
            array_values($category->getChildCategories()->toArray())
        );
    }

    public function testPreUpdate()
    {
        $category = new Category();
        $category->preUpdate();

        $this->assertInstanceOf('DateTime', $category->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $category->getUpdatedAt());
    }
}
