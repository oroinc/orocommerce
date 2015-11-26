<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\FormOptionsAssembler;
use Oro\Bundle\WorkflowBundle\Model\Attribute;

class FormOptionsAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationPass;

    /**
     * @var FormOptionsAssembler
     */
    protected $assembler;

    protected function setUp()
    {
        $this->configurationPass = $this->getMockBuilder(
            'Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface'
        )->getMockForAbstractClass();

        $this->assembler = new FormOptionsAssembler();
        $this->assembler->addConfigurationPass($this->configurationPass);
    }

    public function testAssemble()
    {
        $options = array(
            'attribute_fields' => [
                'attribute_one' => ['form_type' => 'text'],
                'attribute_two' => ['form_type' => 'text'],
            ],
            'attribute_default_values' => [
                'attribute_one' => '$foo',
                'attribute_two' => '$bar',
            ],
        );

        $expectedOptions = array(
            'attribute_fields' => array(
                'attribute_one' => array('form_type' => 'text'),
                'attribute_two' => array('form_type' => 'text'),
            ),
            'attribute_default_values' => array(
                'attribute_one' => new PropertyPath('data.foo'),
                'attribute_two' => new PropertyPath('data.bar'),
            ),
        );

        $attributes = array(
            $this->createAttribute('attribute_one'),
            $this->createAttribute('attribute_two'),
        );

        $this->configurationPass->expects($this->at(0))
            ->method('passConfiguration')
            ->with($options['attribute_fields'])
            ->will($this->returnValue($expectedOptions['attribute_fields']));

        $this->configurationPass->expects($this->at(1))
            ->method('passConfiguration')
            ->with($options['attribute_default_values'])
            ->will($this->returnValue($expectedOptions['attribute_default_values']));

        $this->assertEquals(
            $expectedOptions,
            $this->assembler->assemble(
                $options,
                $attributes
            )
        );
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testAssembleRequiredOptionException(
        $options,
        $attributes,
        $expectedException,
        $expectedExceptionMessage
    ) {
        $this->setExpectedException($expectedException, $expectedExceptionMessage);
        $this->assembler->assemble($options, $attributes);
    }

    public function invalidOptionsDataProvider()
    {
        return array(
            'string_attribute_fields' => array(
                'options' => array(
                    'attribute_fields' => 'string'
                ),
                'attributes' => array(),
                'expectedException' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' => 'Option "form_options.attribute_fields" must be an array.'
            ),
            'string_attribute_default_values' => array(
                'options' => array(
                    'attribute_default_values' => 'string'
                ),
                'attributes' => array(),
                'expectedException' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' =>
                    'Option "form_options.attribute_default_values" must be an array.'
            ),
            'attribute_not_exist_at_attribute_fields' => array(
                'options' => array(
                    'attribute_fields' => array(
                        'attribute_one' => array('form_type' => 'text'),
                    )
                ),
                'attributes' => array(),
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\UnknownAttributeException',
                'expectedExceptionMessage' => 'Unknown attribute "attribute_one".'
            ),
            'attribute_not_exist_at_attribute_default_values' => array(
                'options' => array(
                    'attribute_default_values' => array(
                        'attribute_one' => array('form_type' => 'text'),
                    )
                ),
                'attributes' => array(),
                'expectedException' => 'Oro\Bundle\ActionBundle\Exception\UnknownAttributeException',
                'expectedExceptionMessage' => 'Unknown attribute "attribute_one".'
            ),
            'attribute_default_value_not_in_attribute_fields' => array(
                'options' => array(
                    'attribute_fields' => array(
                        'attribute_one' => array('form_type' => 'text'),
                    ),
                    'attribute_default_values' => array(
                        'attribute_two' => '$attribute_one'
                    )
                ),
                array(
                    $this->createAttribute('attribute_one'),
                    $this->createAttribute('attribute_two'),
                ),
                'expectedException' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedExceptionMessage' =>
                    'Form options doesn\'t have attribute which is referenced in ' .
                    '"attribute_default_values" option.'
            ),
        );
    }

    /**
     * @param string $name
     * @return Attribute
     */
    protected function createAttribute($name)
    {
        $attribute = new Attribute();
        $attribute->setName($name);

        return $attribute;
    }
}
