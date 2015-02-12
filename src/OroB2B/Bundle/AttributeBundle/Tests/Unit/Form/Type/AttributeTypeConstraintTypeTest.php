<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Alphanumeric;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Email;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Letters;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Url;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\UrlSafe;
use OroB2B\Bundle\AttributeBundle\AttributeType\Boolean;
use OroB2B\Bundle\AttributeBundle\AttributeType\Date;
use OroB2B\Bundle\AttributeBundle\AttributeType\DateTime;
use OroB2B\Bundle\AttributeBundle\AttributeType\Float;
use OroB2B\Bundle\AttributeBundle\AttributeType\Integer;
use OroB2B\Bundle\AttributeBundle\AttributeType\Text;
use OroB2B\Bundle\AttributeBundle\AttributeType\String;
use OroB2B\Bundle\AttributeBundle\Form\Type\AttributeTypeConstraintType;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\GreaterThanZero;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Integer as IntegerConstraint;

class AttributeTypeConstraintTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AttributeTypeConstraintType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AttributeTypeRegistry
     */
    protected $registry;

    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMockBuilder('OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry')
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getTypeByName')
            ->will($this->returnValueMap([
                [Integer::NAME, new Integer()],
                [Float::NAME, new Float()],
                [String::NAME, new String()],
                [Boolean::NAME, new Boolean()],
                [Text::NAME, new Text()],
                [Date::NAME, new Date()],
                [DateTime::NAME, new DateTime()],
            ]));

        $this->formType = new AttributeTypeConstraintType($this->registry);
    }

    /**
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param mixed $submittedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, $submittedData)
    {
        $form = $this->factory->create($this->formType, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertNull($form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'submit decimal' => [
                'inputOptions' => [
                    'attribute_type' => new Float()
                ],
                'expectedOptions' => [
                    'empty_value' => 'orob2b.attribute.attribute_type_constraint.none',
                    'choices' => [
                        GreaterThanZero::ALIAS => 'orob2b.attribute.attribute_type_constraint.greater_than_zero',
                        IntegerConstraint::ALIAS => 'orob2b.attribute.attribute_type_constraint.integer'
                    ]
                ],
                'submittedData' => GreaterThanZero::ALIAS,
            ],
            'submit none' => [
                'inputOptions' => [
                    'attribute_type' => new Boolean()
                ],
                'expectedOptions' => [
                    'empty_value' => 'orob2b.attribute.attribute_type_constraint.none',
                    'choices' => []
                ],
                'submittedData' => null,
            ],
            'submit alphanumeric' => [
                'inputOptions' => [
                    'attribute_type' => Text::NAME
                ],
                'expectedOptions' => [
                    'empty_value' => 'orob2b.attribute.attribute_type_constraint.none',
                    'choices' => [
                        Letters::ALIAS => 'orob2b.attribute.attribute_type_constraint.letters',
                        Alphanumeric::ALIAS => 'orob2b.attribute.attribute_type_constraint.alphanumeric',
                        UrlSafe::ALIAS => 'orob2b.attribute.attribute_type_constraint.url_safe',
                        Decimal::ALIAS => 'orob2b.attribute.attribute_type_constraint.decimal',
                        IntegerConstraint::ALIAS => 'orob2b.attribute.attribute_type_constraint.integer',
                        Email::ALIAS => 'orob2b.attribute.attribute_type_constraint.email',
                        Url::ALIAS => 'orob2b.attribute.attribute_type_constraint.url'
                    ]
                ],
                'submittedData' => Alphanumeric::ALIAS,
            ],
            'custom choices' => [
                'inputOptions' => [
                    'attribute_type' => new Integer(),
                    'choices' => [
                        GreaterThanZero::ALIAS => 'orob2b.attribute.attribute_type_constraint.greater_than_zero',
                        Decimal::ALIAS => 'orob2b.attribute.attribute_type_constraint.decimal'
                    ],
                ],
                'expectedOptions' => [
                    'choices' => [
                        GreaterThanZero::ALIAS => 'orob2b.attribute.attribute_type_constraint.greater_than_zero',
                        Decimal::ALIAS => 'orob2b.attribute.attribute_type_constraint.decimal'
                    ],
                ],
                'submittedData' => null,
            ],
        ];
    }

    public function testUnexpectedType()
    {
        $this->setExpectedException(
            '\Symfony\Component\Form\Exception\UnexpectedTypeException',
            'Expected argument of type "OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeInterface or string",'
            . ' "integer" given'
        );

        $form = $this->factory->create($this->formType, null, ['attribute_type' => 10]);
        $form->getData();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Attribute type name "not correct" is not exist in attribute type registry.
     */
    public function testNotExistAttributeType()
    {
        $form = $this->factory->create($this->formType, null, ['attribute_type' => 'not correct']);
        $form->getData();
    }

    public function testGetName()
    {
        $this->assertEquals(AttributeTypeConstraintType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
