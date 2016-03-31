<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

use OroB2B\Bundle\OrderBundle\Layout\Block\Type\DateType;

class DateTypeTest extends BlockTypeTestCase
{
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "date" is missing.
     */
    public function testBuildViewWithoutDate()
    {
        $this->getBlockView(DateType::NAME, []);
    }

    /** {@inheritdoc} */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $layoutFactoryBuilder->addType(new DateType());
    }

    public function testBuildViewWithDefaultOptions()
    {
        $dateTime = new \DateTime();
        $view = $this->getBlockView(DateType::NAME, ['date' => $dateTime]);

        $this->assertEquals($dateTime, $view->vars['date']);
        $this->assertNull($view->vars['dateType']);
        $this->assertNull($view->vars['locale']);
        $this->assertNull($view->vars['timeZone']);
    }

    public function testBuildView()
    {
        $dateTime = new \DateTime();
        $view = $this->getBlockView(
            DateType::NAME,
            [
                'date' => $dateTime,
                'dateType' => 'Y-m-d',
                'locale' => 'en-US',
                'timeZone' => 'UTC',
            ]
        );

        $this->assertEquals($dateTime, $view->vars['date']);

        $this->assertEquals($dateTime, $view->vars['date']);
        $this->assertEquals('Y-m-d', $view->vars['dateType']);
        $this->assertEquals('en-US', $view->vars['locale']);
        $this->assertEquals('UTC', $view->vars['timeZone']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(DateType::NAME);

        $this->assertSame(DateType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(DateType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
