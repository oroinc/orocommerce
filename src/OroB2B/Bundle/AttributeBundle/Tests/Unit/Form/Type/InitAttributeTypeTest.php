<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\AttributeBundle\Form\Type\AttributeTypeType;
use OroB2B\Bundle\AttributeBundle\Form\Type\InitAttributeType;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Alphanumeric;

class InitAttributeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InitAttributeType
     */
    protected $formType;

    protected function setUp()
    {
        $this->formType = new InitAttributeType();
    }

    public function testBuildForm()
    {
        $fields = [
            [
                'code',
                'text',
                [
                    'label' => 'orob2b.attribute.code.label',
                    'constraints' => [new NotBlank(), new Length(['min' => 3, 'max' => 255]), new Alphanumeric()]
                ]
            ],
            [
                'type',
                AttributeTypeType::NAME,
                ['label' => 'orob2b.attribute.type.label', 'constraints' => [new NotBlank()]]
            ],
            [
                'localized',
                'checkbox',
                ['label' => 'orob2b.attribute.localized.label', 'required' => false]
            ]
        ];

        $formBuilder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        foreach ($fields as $index => $field) {
            $formBuilder->expects($this->at($index))
                ->method('add')
                ->with($field[0], $field[1], $field[2])
                ->will($this->returnSelf());
        }

        $this->formType->buildForm($formBuilder, []);
    }

    public function testGetName()
    {
        $this->assertEquals(InitAttributeType::NAME, $this->formType->getName());
    }
}
