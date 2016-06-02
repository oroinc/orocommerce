<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var  Category $category */
    protected $category;

    public function setup()
    {
        $this->category = new Category();
    }

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
        $category = $this->category;

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $category->getTitles());
        $this->assertEmpty($category->getTitles()->toArray());

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $category->getChildCategories());
        $this->assertEmpty($category->getChildCategories()->toArray());

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $category->getProducts());
        $this->assertEmpty($category->getProducts()->toArray());

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $category->getShortDescriptions());
        $this->assertEmpty($category->getShortDescriptions()->toArray());

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $category->getLongDescriptions());
        $this->assertEmpty($category->getLongDescriptions()->toArray());

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $category->getCreatedAt());
        $this->assertLessThanOrEqual($now, $category->getCreatedAt());

        $this->assertInstanceOf('DateTime', $category->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $category->getUpdatedAt());
    }

    public function testTitleAccessors()
    {
        $category = $this->category;
        $this->assertEmpty($category->getTitles()->toArray());

        $firstTitle = $this->createLocalizedValue();

        $secondTitle = $this->createLocalizedValue();

        $category->addTitle($firstTitle)
            ->addTitle($secondTitle)
            ->addTitle($secondTitle);

        $this->assertEquals(
            2,
            count($category->getTitles()->toArray())
        );

        $this->assertEquals([$firstTitle, $secondTitle], array_values($category->getTitles()->toArray()));

        $category->removeTitle($firstTitle)
            ->removeTitle($firstTitle);

        $this->assertEquals([$secondTitle], array_values($category->getTitles()->toArray()));
    }

    public function testShortDescriptionAccessors()
    {
        $category = $this->category;
        $this->assertEmpty($category->getShortDescriptions()->toArray());

        $firstShortDescription = $this->createLocalizedValue();

        $secondShortDescription = $this->createLocalizedValue();

        $category->addShortDescription($firstShortDescription)
            ->addShortDescription($secondShortDescription)
            ->addShortDescription($secondShortDescription);

        $this->assertEquals(
            [$firstShortDescription, $secondShortDescription],
            array_values($category->getShortDescriptions()->toArray())
        );

        $this->assertEquals(
            2,
            count($category->getShortDescriptions()->toArray())
        );

        $category->removeShortDescription($firstShortDescription)
            ->removeShortDescription($firstShortDescription);

        $this->assertEquals([$secondShortDescription], array_values($category->getShortDescriptions()->toArray()));
    }

    public function testLongDescriptionAccessors()
    {
        $category = $this->category;
        $this->assertEmpty($category->getLongDescriptions()->toArray());

        $firstLongDescription = $this->createLocalizedValue();

        $secondLongDescription = $this->createLocalizedValue();

        $category->addLongDescription($firstLongDescription)
            ->addLongDescription($secondLongDescription)
            ->addLongDescription($secondLongDescription);

        $this->assertEquals(
            [$firstLongDescription, $secondLongDescription],
            array_values($category->getLongDescriptions()->toArray())
        );

        $this->assertEquals(
            2,
            count($category->getLongDescriptions()->toArray())
        );

        $category->removeLongDescription($firstLongDescription)
            ->removeLongDescription($firstLongDescription);

        $this->assertEquals([$secondLongDescription], array_values($category->getLongDescriptions()->toArray()));
    }

    public function testChildCategoryAccessors()
    {
        $category = $this->category;
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

        $category = $this->category;
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
        $defaultTitle = $this->createLocalizedValue(true);
        $localizedTitle = $this->createLocalizedValue();

        $category = $this->category;
        $category->addTitle($defaultTitle)
            ->addTitle($localizedTitle);

        $this->assertEquals($defaultTitle, $category->getDefaultTitle());
    }

    public function testGetDefaultShortDescription()
    {
        $defaultShortDescription = $this->createLocalizedValue(true);
        $localizedTShortDescription = $this->createLocalizedValue();

        $category = $this->category;
        $category->addShortDescription($defaultShortDescription)
            ->addShortDescription($localizedTShortDescription);

        $this->assertEquals($defaultShortDescription, $category->getDefaultShortDescription());
    }

    public function testGetDefaultLongDescription()
    {
        $defaultLongDescription = $this->createLocalizedValue(true);
        $localizedLongDescription = $this->createLocalizedValue();

        $category = $this->category;
        $category->addLongDescription($defaultLongDescription)
            ->addLongDescription($localizedLongDescription);

        $this->assertEquals($defaultLongDescription, $category->getDefaultLongDescription());
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
        $category = $this->category;

        foreach ($titles as $title) {
            $category->addTitle($title);
        }

        $category->getDefaultTitle();
    }

    /**
     * @param array $shortDescriptions
     * @dataProvider getDefaultTitleExceptionDataProvider
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage There must be only one default short description
     */
    public function testGetDefaultShortDescriptionException(array $shortDescriptions)
    {
        $category = $this->category;

        foreach ($shortDescriptions as $shortDescription) {
            $category->addShortDescription($shortDescription);
        }

        $category->getDefaultShortDescription();
    }

    /**
     * @param array $longDescriptions
     * @dataProvider getDefaultTitleExceptionDataProvider
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage There must be only one default long description
     */
    public function testGetDefaultLongDescriptionException(array $longDescriptions)
    {
        $category = $this->category;

        foreach ($longDescriptions as $longDescription) {
            $category->addShortDescription($longDescription);
        }

        $category->getDefaultLongDescription();
    }

    /**
     * @return array
     */
    public function getDefaultTitleExceptionDataProvider()
    {
        return [
            'no default localized' => [[]],
            'several default localized' => [[$this->createLocalizedValue(true), $this->createLocalizedValue(true)]],
        ];
    }

    public function testPreUpdate()
    {
        $category = $this->category;
        $category->preUpdate();

        $this->assertInstanceOf('DateTime', $category->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $category->getUpdatedAt());
    }

    public function testToString()
    {
        $value = 'test';

        $title = new LocalizedFallbackValue();
        $title->setString($value);

        $category = $this->category;
        $category->addTitle($title);

        $this->assertEquals($value, (string)$category);
    }

    /**
     * @param bool|false $default
     *
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($default = false)
    {
        $localized = (new LocalizedFallbackValue())->setString('some string');

        if (!$default) {
            $localized->setLocale(new Locale());
        }

        return $localized;
    }
}
