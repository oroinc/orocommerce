<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ShippingBundle\Form\Type\DimensionsValueType;
use OroB2B\Bundle\ShippingBundle\Model\DimensionsValue;

class DimensionsValueTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ShippingBundle\Model\DimensionsValue';

    /** @var DimensionsValueType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new DimensionsValueType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    public function testGetName()
    {
        $this->assertEquals(DimensionsValueType::NAME, $this->formType->getName());
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty default data' => [
                'submittedData' => [
                    'length' => '42',
                    'width' => '42',
                    'height' => '42'
                ],
                'expectedData' => $this->getDimensionsValue(42, 42, 42)
            ],
            'full data' => [
                'submittedData' => [
                    'length' => '2',
                    'width' => '4',
                    'height' => '6'
                ],
                'expectedData' => $this->getDimensionsValue(2, 4, 6),
                'defaultData' => $this->getDimensionsValue(1, 3, 5),
            ],
        ];
    }

    /**
     * @param float $length
     * @param float $width
     * @param float $height
     *
     * @return DimensionsValue
     */
    protected function getDimensionsValue($length, $width, $height)
    {
        return DimensionsValue::create($length, $width, $height);
    }
}
