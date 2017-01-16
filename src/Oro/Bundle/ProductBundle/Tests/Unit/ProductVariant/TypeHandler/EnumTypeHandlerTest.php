<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\TypeHandler;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\TypeHandler\EnumTypeHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;

class EnumTypeHandlerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = Product::class;

    /** @var FormFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var EnumTypeHandler */
    protected $handler;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new EnumTypeHandler($this->formFactory, self::PRODUCT_CLASS);
    }

    public function testCreateForm()
    {
        $fieldName = 'field1';
        $availability = [
            'red' => false,
            'green' => true,
            'yellow' => false,
            'black' => true,
        ];

        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formFactory->expects($this->once())
            ->method('createNamed')
            ->with($fieldName, EnumSelectType::NAME, null, $this->callback(function (array $options) use ($fieldName) {

                $disabledValues = ['red', 'yellow'];

                $this->assertEquals([
                    'enum_code' => ExtendHelper::generateEnumCode(self::PRODUCT_CLASS, $fieldName),
                    'configs' => ['allowClear' => false],
                    'disabled_values' => $disabledValues,
                    'auto_initialize' => false,
                ], $options);

                return true;
            }))
            ->willReturn($form);

        $actualForm = $this->handler->createForm($fieldName, $availability);
        $this->assertSame($form, $actualForm);
    }

    public function testGetType()
    {
        $this->assertEquals('enum', $this->handler->getType());
    }
}
