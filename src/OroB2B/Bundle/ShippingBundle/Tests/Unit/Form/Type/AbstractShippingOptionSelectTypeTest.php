<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;

use OroB2B\Bundle\ShippingBundle\Form\Type\AbstractShippingOptionSelectType;
use OroB2B\Bundle\ShippingBundle\Provider\MeasureUnitProvider;

abstract class AbstractShippingOptionSelectTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|MeasureUnitProvider */
    protected $provider;

    /** @var UnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $formatter;

    /** @var AbstractShippingOptionSelectType */
    protected $formType;

    /** @var array */
    protected $units = ['lbs', 'kg', 'custom'];

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\MeasureUnitProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->formType, $this->formatter, $this->provider);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals(AbstractShippingOptionSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
    }

    public function testSetEntityClass()
    {
        $className = 'stdClass';

        $this->assertAttributeEmpty('entityClass', $this->formType);

        $this->formType->setEntityClass($className);

        $this->assertAttributeEquals($className, 'entityClass', $this->formType);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param array $expectedLabels
     * @param array $customChoices
     */
    public function testSubmit(
        array $inputOptions,
        array $expectedOptions,
        $submittedData,
        $expectedData,
        array $expectedLabels,
        array $customChoices = null
    ) {
        $units = ['lbs', 'kg'];

        $this->provider->expects($customChoices ? $this->never() : $this->once())
            ->method('getUnits')
            ->with(!$expectedOptions['full_list'])
            ->willReturn($units);

        if ($customChoices) {
            $inputOptions['choices'] = $customChoices;
            $units = $customChoices;
        }

        $form = $this->factory->create($this->formType, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $this->assertTrue($formConfig->hasOption('choices'));
        $this->assertEquals($units, $formConfig->getOption('choices'));

        foreach ($expectedLabels as $key => $expectedLabel) {
            $this->formatter->expects($this->at($key))
                ->method('format')
                ->with(array_shift($this->units), $formConfig->getOption('compact'), false)
                ->willReturn($expectedLabel);
        }

        $choices = $form->createView()->vars['choices'];
        foreach ($choices as $choice) {
            $this->assertEquals(array_shift($expectedLabels), $choice->label);
        }

        $this->assertNull($form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'inputOptions' => [],
                'expectedOptions' => ['compact' => false, 'full_list' => false, 'multiple' => false],
                'submittedData' => null,
                'expectedData' => null,
                'expectedLabels' => ['formatted.lbs', 'formatted.kg'],
            ],
            [
                'inputOptions' => ['compact' => true, 'full_list' => true],
                'expectedOptions' => ['compact' => true, 'full_list' => true, 'multiple' => false],
                'submittedData' => 'lbs',
                'expectedData' => $this->createUnit('lbs'),
                'expectedLabels' => ['formatted.lbs', 'formatted.kg'],
            ],
            [
                'inputOptions' => ['multiple' => true],
                'expectedOptions' => ['compact' => false, 'full_list' => false, 'multiple' => true],
                'submittedData' => ['lbs', 'kg'],
                'expectedData' => [$this->createUnit('lbs'), $this->createUnit('kg')],
                'expectedLabels' => ['formatted.lbs', 'formatted.kg'],
            ],
            [
                'inputOptions' => [],
                'expectedOptions' => ['compact' => false, 'full_list' => false],
                'submittedData' => 'custom',
                'expectedData' => $this->createUnit('custom'),
                'expectedLabels' => ['formatted.lbs', 'formatted.kg'],
                'choices' => ['custom']
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'entity' => new EntityType($this->prepareChoices())
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * @return array
     */
    protected function prepareChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $choices[$unitCode] = $this->createUnit($unitCode);
        }

        return $choices;
    }

    /**
     * @param string $code
     * @return MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createUnit($code)
    {
        /** @var MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject $unit */
        $unit = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface');
        $unit->expects($this->any())
            ->method('getCode')
            ->willReturn($code);

        return $unit;
    }
}
