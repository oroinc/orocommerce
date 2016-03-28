<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

use OroB2B\Bundle\OrderBundle\Layout\Block\Type\CurrencyType;

class CurrencyTypeTest extends BlockTypeTestCase
{
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required options "currency", "value" are missing.
     */
    public function testBuildViewWithoutCurrency()
    {
        $this->getBlockView(CurrencyType::NAME, []);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "value" is missing.
     */
    public function testBuildViewWithoutValue()
    {
        $this->getBlockView(CurrencyType::NAME, ['currency' => 'USD']);
    }

    /** {@inheritdoc} */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $layoutFactoryBuilder->addType(new CurrencyType());
    }

    public function testBuildViewWithDefaultOptions()
    {
        $view = $this->getBlockView(CurrencyType::NAME, ['currency' => 'USD', 'value' => '100']);

        $this->assertEquals('USD', $view->vars['currency']);
        $this->assertEquals('100', $view->vars['value']);
        $this->assertEquals([], $view->vars['attributes']);
        $this->assertEquals([], $view->vars['textAttributes']);
        $this->assertEquals([], $view->vars['symbols']);
        $this->assertEquals(null, $view->vars['locale']);
    }

    public function testBuildView()
    {
        $view = $this->getBlockView(
            CurrencyType::NAME,
            [
                'currency' => 'USD',
                'value' => '100',
                'attributes' => ['attr1'],
                'textAttributes' => ['textAttributes1'],
                'symbols' => ['a'],
                'locale' => 'en-US',
            ]
        );

        $this->assertEquals('USD', $view->vars['currency']);
        $this->assertEquals('100', $view->vars['value']);
        $this->assertEquals(['attr1'], $view->vars['attributes']);
        $this->assertEquals(['textAttributes1'], $view->vars['textAttributes']);
        $this->assertEquals(['a'], $view->vars['symbols']);
        $this->assertEquals('en-US', $view->vars['locale']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(CurrencyType::NAME);

        $this->assertSame(CurrencyType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(CurrencyType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
