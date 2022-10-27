<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\TypeHandler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ProductVariant\TypeHandler\EnumTypeHandler;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;

class EnumTypeHandlerTest extends \PHPUnit\Framework\TestCase
{
    const PRODUCT_CLASS = Product::class;

    /** @var FormFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /** @var EnumTypeHandler */
    protected $handler;

    protected function setUp(): void
    {
        $this->formFactory = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->handler = new EnumTypeHandler($this->formFactory, self::PRODUCT_CLASS, $this->configManager);
    }

    public function testCreateForm()
    {
        $fieldName = 'field1';
        $availability = [
            'red' => false,
            'green' => true,
            'yellow' => false,
            'black' => true,
            '10' => false,
            '10 mm' => true,
            '10mm' => true,
        ];

        $fieldConfig = $this->createMock(FieldConfigModel::class);
        $fieldConfig->expects($this->once())
            ->method('toArray')
            ->with('extend')
            ->willReturn(['target_entity' => '\stdClass']);
        $this->configManager->expects($this->once())
            ->method('getConfigFieldModel')
            ->with(Product::class, $fieldName)
            ->willReturn($fieldConfig);

        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formFactory->expects($this->once())
            ->method('createNamed')
            ->with($fieldName, EnumSelectType::class, null, $this->callback(function (array $options) use ($fieldName) {
                $disabledValues = ['red', 'yellow', '10'];

                $this->assertEquals([
                    'class' => '\stdClass',
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
