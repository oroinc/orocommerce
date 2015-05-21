<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\AttributeBundle\Form\Extension\IntegerExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductUnitPrecisionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new ProductUnitPrecisionType();
        parent::setUp();
    }

    /**
     * @var array
     */
    protected $units = ['item', 'kg'];

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType($this->prepareChoices());
        return [
            new PreloadedExtension(
                [
                    ProductUnitSelectionType::NAME => new ProductUnitSelectionType(),
                    'entity' => $entityType,
                ],
                [
                    'form' => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param ProductUnitPrecision $defaultData
     * @param array $expectedOptions
     * @param array|ProductUnitPrecision $submittedData
     * @param ProductUnitPrecision $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductUnitPrecision $defaultData,
        array $expectedOptions,
        $submittedData,
        ProductUnitPrecision $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        $unitConfig = $form->get('unit')->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($unitConfig->hasOption($key));
            $this->assertEquals($value, $unitConfig->getOption($key));
        }

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
            'existing unit precision' => [
                'defaultData'   => (new ProductUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(0),
                '$expectedOptions' => [
                    'disabled' => true
                ],
                'submittedData' => [],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(0)
            ],
            'unit precision without value' => [
                'defaultData'   => new ProductUnitPrecision(),
                '$expectedOptions' => [
                    'disabled' => false
                ],
                'submittedData' => [],
                'expectedData'  => new ProductUnitPrecision()
            ],
            'unit precision with value' => [
                'defaultData'   => new ProductUnitPrecision(),
                '$expectedOptions' => [
                    'disabled' => false
                ],
                'submittedData' => [
                    'unit' => 'kg',
                    'precision' => 5
                ],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(5)
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(ProductUnitPrecisionType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    protected function prepareChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }
}
