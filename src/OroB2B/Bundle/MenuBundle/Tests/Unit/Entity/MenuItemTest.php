<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class MenuItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @var MenuItem
     */
    protected $entity;

    public function setUp()
    {
        $this->entity = new MenuItem();
    }

    public function testGettersAndSetters()
    {
        $properties = [
            ['id', 1],
            ['parentMenuItem', new MenuItem()],
            ['uri', 'http://localhost'],
            ['route', 'route_name'],
            ['routeParameters', ['first' => 1, 'second' => 2]],
            ['display', false],
            ['displayChildren', false],
            ['left', 1],
            ['level', 2],
            ['right', 3],
            ['root', 4],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }

    public function testConstruct()
    {
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $this->entity->getTitles());
        $this->assertEmpty($this->entity->getTitles()->toArray());
    }

    public function testTitleAccessors()
    {
        $menuItem = $this->entity;
        $this->assertEmpty($menuItem->getTitles()->toArray());

        $firstTitle = $this->createLocalizedValue();

        $secondTitle = $this->createLocalizedValue();

        $menuItem->addTitle($firstTitle)
            ->addTitle($secondTitle)
            ->addTitle($secondTitle);

        $this->assertEquals(
            2,
            count($menuItem->getTitles()->toArray())
        );

        $this->assertEquals([$firstTitle, $secondTitle], array_values($menuItem->getTitles()->toArray()));

        $menuItem->removeTitle($firstTitle)
            ->removeTitle($firstTitle);

        $this->assertEquals([$secondTitle], array_values($menuItem->getTitles()->toArray()));
    }


    public function testGetDefaultTitle()
    {
        $defaultTitle = $this->createLocalizedValue(true);
        $localizedTitle = $this->createLocalizedValue();

        $menuItem = $this->entity;
        $menuItem->addTitle($defaultTitle)
            ->addTitle($localizedTitle);

        $this->assertEquals($defaultTitle, $menuItem->getDefaultTitle());
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
        $menuItem = $this->entity;

        foreach ($titles as $title) {
            $menuItem->addTitle($title);
        }

        $menuItem->getDefaultTitle();
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
