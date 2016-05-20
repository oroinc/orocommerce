<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Form\Type\WeightType;
use OroB2B\Bundle\ShippingBundle\Form\Type\WeightUnitSelectType;
use OroB2B\Bundle\ShippingBundle\Model\Weight;

class WeightTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ShippingBundle\Model\Weight';

    /** @var WeightType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new WeightType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    public function testGetName()
    {
        $this->assertEquals(WeightType::NAME, $this->formType->getName());
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
                    'value' => '42',
                    'unit' => 'lbs',
                ],
                'expectedData' => $this->getWeight($this->getWeightUnit('lbs'), 42)
            ],
            'full data' => [
                'submittedData' => [
                    'value' => '2',
                    'unit' => 'kg',
                ],
                'expectedData' => $this->getWeight($this->getWeightUnit('kg'), 2),
                'defaultData' => $this->getWeight($this->getWeightUnit('lf'), 1),
            ],
        ];
    }

    /**
     * @param WeightUnit $weightUnit
     * @param float $value
     *
     * @return Weight
     */
    protected function getWeight(WeightUnit $weightUnit, $value)
    {
        return Weight::create($value, $weightUnit);
    }

    /**
     * @param string $code
     *
     * @return WeightUnit
     */
    protected function getWeightUnit($code)
    {
        $weightUnit = new WeightUnit();
        $weightUnit->setCode($code);

        return $weightUnit;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    WeightUnitSelectType::NAME => new EntityType(
                        [
                            'kg' => $this->getWeightUnit('kg'),
                            'lbs' => $this->getWeightUnit('lbs')
                        ],
                        WeightUnitSelectType::NAME
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
