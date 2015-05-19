<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class ProductUnitSelectionTypeTest extends FormIntegrationTestCase
{
    /** @var ProductUnitSelectionType */
    protected $formType;

    /**
     * @var array
     */
    protected $units = ['test01', 'test02'];

    /**
     * @var array
     */
    protected $labels = [];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->formType = new ProductUnitSelectionType();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType($this->prepareChoices());

        return [
            new PreloadedExtension(['entity' => $entityType], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $inputOptions
     * @param array $expectedOptions
     * @param array $expectedLabels
     * @param string $submittedData
     */
    public function testSubmit(array $inputOptions, array $expectedOptions, array $expectedLabels, $submittedData)
    {
        $form = $this->factory->create($this->formType, null, $inputOptions);

        $formConfig = $form->getConfig();
        foreach ($expectedOptions as $key => $value) {
            $this->assertTrue($formConfig->hasOption($key));
            $this->assertEquals($value, $formConfig->getOption($key));
        }

        $choices = $form->createView()->vars['choices'];
        foreach ($choices as $choice) {
            $label = array_shift($expectedLabels);
            $this->assertEquals($label, $choice->label);
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
            'without compact option' => [
                'inputOptions' => [],
                'expectedOptions' => [
                    'compact' => false
                ],
                'expectedLabels' => [
                    'orob2b.product_unit.test01.label.full',
                    'orob2b.product_unit.test02.label.full',
                ],
                'submittedData' => 'test01'
            ],
            'with compact option' => [
                'inputOptions' => [
                    'compact' => true,
                ],
                'expectedOptions' => [
                    'compact' => true,
                ],
                'expectedLabels' => [
                    'orob2b.product_unit.test01.label.short',
                    'orob2b.product_unit.test02.label.short',
                ],
                'submittedData' => 'test02'
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitSelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->formType->getParent());
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
