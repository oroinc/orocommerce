<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ShippingBundle\Form\Type\AbstractShippingOptionSelectType;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

abstract class AbstractShippingOptionSelectTypeTest extends FormIntegrationTestCase
{
    /** @var AbstractMeasureUnitProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $unitProvider;

    /** @var AbstractShippingOptionSelectType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->unitProvider = $this->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->formType, $this->repository, $this->formatter, $this->configManager);

        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals(AbstractShippingOptionSelectType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param mixed $submittedData
     * @param mixed $expectedData
     * @param array $options
     */
    public function testSubmit($submittedData, $expectedData, array $options = [])
    {
        $this->unitProvider->expects($this->once())
            ->method('getUnitsCodes')
            ->with(empty($options['full_list']))
            ->willReturn(['kg', 'lbs']);

        $this->unitProvider->expects($this->once())
            ->method('formatUnitsCodes')
            ->with(['kg' => 'kg', 'lbs' => 'lbs'], false)
            ->willReturn([
                'kg' => 'formatted.kg',
                'lbs' => 'formatted.lbs',
            ]);

        $form = $this->factory->create($this->formType, null, $options);

        $this->assertNull($form->getData());

        $formConfig = $form->getConfig();
        $this->assertTrue($formConfig->hasOption('choices'));
        $this->assertEquals(['kg' => 'formatted.kg', 'lbs' => 'formatted.lbs'], $formConfig->getOption('choices'));

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
                'submittedData' => null,
                'expectedData' => null,
            ],
            [
                'submittedData' => 'lbs',
                'expectedData' => 'lbs',
            ],
            [
                'submittedData' => ['lbs', 'kg'],
                'expectedData' => ['lbs', 'kg'],
                'options' => ['multiple' => true],
            ],
            [
                'submittedData' => 'lbs',
                'expectedData' => 'lbs',
                'options' => ['full_list' => true],
            ],
        ];
    }

    /**
     * @param string $code
     * @return MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createUnit($code)
    {
        /** @var MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject $unit */
        $unit = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface');
        $unit->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn($code);

        return $unit;
    }
}
