<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;

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
            ['parent', new MenuItem()],
            ['uri', 'http://localhost'],
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

    public function testTitleAccessor()
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
     * @expectedException \LogicException
     * @expectedExceptionMessage There must be only one default title
     */
    public function testGetDefaultTitleException()
    {
        $titles = [$this->createLocalizedValue(true), $this->createLocalizedValue(true)];
        $menuItem = $this->entity;

        foreach ($titles as $title) {
            $menuItem->addTitle($title);
        }

        $menuItem->getDefaultTitle();
    }

    public function testSetDefaultTitle()
    {
        $menuItem = $this->entity;
        $menuItem->setDefaultTitle('test_title_string1');
        $this->assertEquals('test_title_string1', $menuItem->getDefaultTitle());

        // check second time to make sure we don't have an exception in getter
        $menuItem->setDefaultTitle('test_title_string2');
        $this->assertEquals('test_title_string2', $menuItem->getDefaultTitle());
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
            $localized->setLocalization(new Localization());
        }

        return $localized;
    }

    public function testConditionGetterAndSetter()
    {
        $this->assertNull($this->entity->getCondition());

        $this->assertSame($this->entity, $this->entity->setCondition('logged_in()'));
        $this->assertEquals('logged_in()', $this->entity->getCondition());

        $this->assertSame($this->entity, $this->entity->setCondition(null));
        $this->assertNull($this->entity->getCondition());
        $this->assertArrayNotHasKey('condition', $this->entity->getExtras());

        $this->assertSame($this->entity, $this->entity->setCondition(null));
        $this->assertNull($this->entity->getCondition());
        $this->assertArrayNotHasKey('condition', $this->entity->getExtras());
    }
}
