<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\AttributeBundle\AttributeType\Boolean;
use OroB2B\Bundle\AttributeBundle\AttributeType\Float;
use OroB2B\Bundle\AttributeBundle\AttributeType\Integer;
use OroB2B\Bundle\AttributeBundle\AttributeType\String;
use OroB2B\Bundle\AttributeBundle\AttributeType\Text;
use OroB2B\Bundle\AttributeBundle\AttributeType\Date;
use OroB2B\Bundle\AttributeBundle\AttributeType\DateTime;
use OroB2B\Bundle\AttributeBundle\Form\Type\AttributeTypeType;

class AttributeTypeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var AttributeTypeType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $types = [
        Integer::NAME,
        Float::NAME,
        String::NAME,
        Boolean::NAME,
        Text::NAME,
        Date::NAME,
        DateTime::NAME
    ];

    protected function setUp()
    {
        parent::setUp();

        $registry = $this->getMockBuilder('OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry')
            ->getMock();

        $registry->expects($this->any())
            ->method('getTypes')
            ->will($this->returnValue([
                Integer::NAME => new Integer(),
                Float::NAME => new Float(),
                String::NAME => new String(),
                Boolean::NAME => new Boolean(),
                Text::NAME => new Text(),
                Date::NAME => new Date(),
                DateTime::NAME => new DateTime()
            ]));

        /** @var \OroB2B\Bundle\AttributeBundle\AttributeType\AttributeTypeRegistry $registry */

        $this->formType = new AttributeTypeType($registry);
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
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        $choices = [];
        foreach ($this->types as $type) {
            $choices[$type] = 'orob2b.attribute.attribute_type.'. $type;
        }

        return [
            'submit integer' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'required' => true,
                    'empty_value' => 'orob2b.attribute.attribute_type.empty',
                    'choices' => $choices
                ],
                'submittedData' => Integer::NAME,
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(AttributeTypeType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }
}
