<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryUnitPrecisionType;
use OroB2B\Bundle\CatalogBundle\Entity\CategoryUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class CategoryUnitPrecisionTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\CatalogBundle\Entity\CategoryUnitPrecision';

    /**
     * @var CategoryUnitPrecisionType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $units = ['item', 'kg'];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($id, array $params) {
                    return isset($params['{title}']) ? $id . ':' . $params['{title}'] : $id;
                }
            );
        $this->productUnitLabelFormatter = new ProductUnitLabelFormatter($this->translator);
        $this->formType = new CategoryUnitPrecisionType();
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType($this->prepareChoices());
        return [
            new PreloadedExtension(
                [
                    ProductUnitSelectionType::NAME => new ProductUnitSelectionType(
                        $this->productUnitLabelFormatter,
                        $this->translator
                    ),
                    'entity' => $entityType
                ],
                [
                    'form' => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param CategoryUnitPrecision $defaultData
     * @param array $expectedOptions
     * @param array|CategoryUnitPrecision $submittedData
     * @param CategoryUnitPrecision $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        CategoryUnitPrecision $defaultData,
        array $expectedOptions,
        $submittedData,
        CategoryUnitPrecision $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertFormConfig($expectedOptions['unit'], $form->get('unit')->getConfig());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @param array $expectedConfig
     * @param FormConfigInterface $actualConfig
     */
    protected function assertFormConfig(array $expectedConfig, FormConfigInterface $actualConfig)
    {
        foreach ($expectedConfig as $key => $value) {
            $this->assertTrue($actualConfig->hasOption($key));
            $this->assertEquals($value, $actualConfig->getOption($key));
        }
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'unit precision without value' => [
                'defaultData'   => new CategoryUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                    'precision' => [],
                ],
                'submittedData' => [],
                'expectedData'  => new CategoryUnitPrecision()
            ],
            'unit precision with value' => [
                'defaultData'   => new CategoryUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                    'precision' => [],
                ],
                'submittedData' => [
                    'unit' => 'kg',
                    'precision' => 5,
                ],
                'expectedData'  => (new CategoryUnitPrecision())
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
        $this->assertEquals(CategoryUnitPrecisionType::NAME, $this->formType->getName());
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
