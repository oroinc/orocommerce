<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\Block\Type;

use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\OrderBundle\Layout\Block\Type\AddressType;

class AddressTypeTest extends BlockTypeTestCase
{
    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "address" is missing.
     */
    public function testBuildViewWithoutAddress()
    {
        $this->getBlockView(AddressType::NAME, []);
    }

    /** {@inheritdoc} */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);

        $layoutFactoryBuilder->addType(new AddressType());
    }

    public function testBuildView()
    {
        $orderAddress = new OrderAddress();
        $view = $this->getBlockView(AddressType::NAME, ['address' => $orderAddress]);

        $this->assertEquals($orderAddress, $view->vars['address']);
    }

    public function testFinishView()
    {
        $orderAddress = new OrderAddress();
        $view = $this->getBlockView(AddressType::NAME, ['address' => $orderAddress]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $type = $this->getBlockType(AddressType::NAME);
        $type->finishView($view, $block, []);

        $this->assertArrayHasKey('address', $view->vars);
        $this->assertEquals($view->vars['address'], $orderAddress);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(AddressType::NAME);

        $this->assertSame(AddressType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(AddressType::NAME);

        $this->assertSame(BaseType::NAME, $type->getParent());
    }
}
