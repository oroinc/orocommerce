<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\TypeHandler;

use Oro\Bundle\ProductBundle\ProductVariant\TypeHandler\BooleanTypeHandler;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;

class BooleanTypeHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactory|\PHPUnit\Framework\MockObject\MockObject */
    protected $formFactory;

    /** @var BooleanTypeHandler */
    protected $handler;

    protected function setUp(): void
    {
        $this->formFactory = $this->getMockBuilder(FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new BooleanTypeHandler($this->formFactory);
    }

    public function testCreateForm()
    {
        $fieldName = 'field1';
        $availability = [
            false => false,
            true => true,
        ];

        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formFactory->expects($this->once())
            ->method('createNamed')
            ->with($fieldName, ChoiceType::class, null, $this->callback(function (array $options) {

                // will check choice_attr separately
                $this->assertSame([
                    'No' => false,
                    'Yes' => true,
                ], $options['choices']);
                $this->assertSame(false, $options['auto_initialize']);

                $this->assertArrayHasKey('choice_attr', $options);

                $callback = $options['choice_attr'];
                $this->assertIsCallable($callback);

                $this->assertEquals(['disabled' => 'disabled'], $callback(false, 'No', 0));
                $this->assertEquals([], $callback(true, 'Yes', 1));

                return true;
            }))
            ->willReturn($form);

        $actualForm = $this->handler->createForm($fieldName, $availability);
        $this->assertSame($form, $actualForm);
    }

    public function testGetType()
    {
        $this->assertEquals('boolean', $this->handler->getType());
    }
}
