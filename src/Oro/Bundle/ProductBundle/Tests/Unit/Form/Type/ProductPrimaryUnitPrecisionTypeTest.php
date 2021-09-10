<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductPrimaryUnitPrecisionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class ProductPrimaryUnitPrecisionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductPrimaryUnitPrecisionType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $units = ['item', 'kg'];

    /**
     * @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $productUnitLabelFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->formType = new ProductPrimaryUnitPrecisionType();
        $this->formType->setDataClass(ProductUnitPrecision::class);
        $this->productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityTypeStub($this->prepareChoices());
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    ProductUnitSelectType::class => new ProductUnitSelectType($this->productUnitLabelFormatter),
                    EntityType::class => $entityType
                ],
                [
                    FormType::class => [new IntegerExtension()]
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
        $form = $this->factory->create(ProductPrimaryUnitPrecisionType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertFormConfig($expectedOptions['unit'], $form->get('unit')->getConfig());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

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
            'existing unit precision' => [
                'defaultData'   => (new ProductUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(0),
                'expectedOptions' => [
                    'unit' => [
                        'attr' => [
                            'class' => 'unit'
                        ]
                    ]
                ],
                'submittedData' => [],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setSell(false)
            ],
            'unit precision without value' => [
                'defaultData'   => new ProductUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                ],
                'submittedData' => [],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setSell(false)
            ],
            'unit precision with value' => [
                'defaultData'   => new ProductUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                ],
                'submittedData' => [
                    'unit' => 'kg',
                    'precision' => 5,
                    'sell' => true
                ],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(5)
                    ->setSell(true)
            ]
        ];
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
