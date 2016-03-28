<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

use OroB2B\Bundle\TaxBundle\Layout\Block\Type\TaxType;
use OroB2B\Bundle\TaxBundle\Model\Result;

class TaxTypeTest extends BlockTypeTestCase
{
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "result" is missing.
     */
    public function testBuildViewWithoutResult()
    {
        $this->getBlockView(TaxType::NAME, []);
    }

    /** {@inheritdoc} */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $layoutFactoryBuilder->addType(new TaxType());
    }

    public function testBuildView()
    {
        $result = new Result();
        $view = $this->getBlockView(TaxType::NAME, ['result' => $result]);

        $this->assertEquals($result, $view->vars['result']);
    }

    public function testFinishView()
    {
        $result = new Result();
        $view = $this->getBlockView(TaxType::NAME, ['result' => $result]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $type = $this->getBlockType(TaxType::NAME);
        $type->finishView($view, $block, ['result' => $result]);

        $this->assertArrayHasKey('result', $view->vars);
        $this->assertSame($view->vars['result'], $result);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(TaxType::NAME);

        $this->assertSame(TaxType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(TaxType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
