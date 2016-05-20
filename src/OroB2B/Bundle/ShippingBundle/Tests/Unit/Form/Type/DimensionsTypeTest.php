<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Form\Type\DimensionsType;
use OroB2B\Bundle\ShippingBundle\Form\Type\DimensionsValueType;
use OroB2B\Bundle\ShippingBundle\Form\Type\LengthUnitSelectType;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;

class DimensionsTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ShippingBundle\Model\Dimensions';

    /** @var DimensionsType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new DimensionsType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    public function testGetName()
    {
        $this->assertEquals(DimensionsType::NAME, $this->formType->getName());
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
                    'value' => [
                        'length' => '42',
                        'width' => '42',
                        'height' => '42'
                    ],
                    'unit' => 'foot',
                ],
                'expectedData' => $this->getDimensions($this->getLengthUnit('foot'), 42, 42, 42)
            ],
            'full data' => [
                'submittedData' => [
                    'value' => [
                        'length' => '2',
                        'width' => '4',
                        'height' => '6'
                    ],
                    'unit' => 'm',
                ],
                'expectedData' => $this->getDimensions($this->getLengthUnit('m'), 2, 4, 6),
                'defaultData' => $this->getDimensions($this->getLengthUnit('sm'), 1, 3, 5),
            ],
        ];
    }

    /**
     * @param string $code
     *
     * @return LengthUnit
     */
    protected function getLengthUnit($code)
    {
        $lengthUnit = new LengthUnit();
        $lengthUnit->setCode($code);

        return $lengthUnit;
    }

    /**
     * @param LengthUnit $lengthUnit
     * @param float $length
     * @param float $width
     * @param float $height
     *
     * @return Dimensions
     */
    protected function getDimensions(LengthUnit $lengthUnit, $length, $width, $height)
    {
        return Dimensions::create($length, $width, $height, $lengthUnit);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        $valueType = new DimensionsValueType();
        $valueType->setDataClass('OroB2B\Bundle\ShippingBundle\Model\DimensionsValue');

        return [
            new PreloadedExtension(
                [
                    LengthUnitSelectType::NAME => new EntityType(
                        [
                            'm' => $this->getLengthUnit('m'),
                            'sm' => $this->getLengthUnit('sm'),
                            'foot' => $this->getLengthUnit('foot')
                        ],
                        LengthUnitSelectType::NAME
                    ),
                    $valueType->getName() => $valueType
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
