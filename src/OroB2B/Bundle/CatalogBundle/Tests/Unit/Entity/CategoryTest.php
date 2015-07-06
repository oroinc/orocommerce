<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

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

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $category->getProducts());
        $this->assertEmpty($category->getProducts()->toArray());

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

        $firstTitle = new LocalizedFallbackValue();
        $firstTitle->setString('first');

        $secondTitle = new LocalizedFallbackValue();
        $secondTitle->setString('second');

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

    public function testProductAccessors()
    {
        $firstProduct = new Product();
        $secondProduct = new Product();

        $category = new Category();
        $category->addProduct($firstProduct)
            ->addProduct($secondProduct);

        $this->assertEquals(
            [0 => $firstProduct, 1 => $secondProduct],
            $category->getProducts()->toArray()
        );

        $category->removeProduct($firstProduct);

        $this->assertEquals(
            [1 => $secondProduct],
            $category->getProducts()->toArray()
        );
    }

    public function testGetDefaultTitle()
    {
        $defaultTitle = new LocalizedFallbackValue();
        $defaultTitle->setString('default');

        $localizedTitle = new LocalizedFallbackValue();
        $localizedTitle->setString('localized')
            ->setLocale(new Locale());

        $category = new Category();
        $category->addTitle($defaultTitle)
            ->addTitle($localizedTitle);

        $this->assertEquals($defaultTitle, $category->getDefaultTitle());
    }

    /**
     * @param array $titles
     * @dataProvider getDefaultTitleExceptionDataProvider
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage There must be only one default title
     */
    public function testGetDefaultTitleException(array $titles)
    {
        $category = new Category();
        foreach ($titles as $title) {
            $category->addTitle($title);
        }
        $category->getDefaultTitle();
    }

    /**
     * @return array
     */
    public function getDefaultTitleExceptionDataProvider()
    {
        return [
            'no default title' => [[]],
            'several default titles' => [[new LocalizedFallbackValue(), new LocalizedFallbackValue()]],
        ];
    }

    public function testPreUpdate()
    {
        $category = new Category();
        $category->preUpdate();

        $this->assertInstanceOf('DateTime', $category->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $category->getUpdatedAt());
    }

    public function testToString()
    {
        $value = 'test';

        $title = new LocalizedFallbackValue();
        $title->setString($value);

        $category = new Category();
        $category->addTitle($title);

        $this->assertEquals($value, (string)$category);
    }
}
