<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\InventoryBundle\Form\Type\WarehouseSelectType;

class WarehouseSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseSelectType
     */
    protected $warehouseSelectType;

    protected function setUp()
    {
        $this->warehouseSelectType = new WarehouseSelectType();
    }

    public function testSetDefaultOptions()
    {
        /** @var OptionsResolverInterface|\PHPUnit_Framework_MockObject_MockObject $resolver **/
        $resolver = $this->getMock(OptionsResolverInterface::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    $this->assertArrayHasKey('autocomplete_alias', $options);
                    $this->assertEquals('oro_warehouse', $options['autocomplete_alias']);

                    $this->assertArrayHasKey('configs', $options);
                    $this->assertEquals(
                        ['placeholder' => 'oro.warehouse.form.choose_warehouse'],
                        $options['configs']
                    );
                }
            );

        $this->warehouseSelectType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::NAME, $this->warehouseSelectType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(WarehouseSelectType::NAME, $this->warehouseSelectType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(WarehouseSelectType::NAME, $this->warehouseSelectType->getBlockPrefix());
    }
}
