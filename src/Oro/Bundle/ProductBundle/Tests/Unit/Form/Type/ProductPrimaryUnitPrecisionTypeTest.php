<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductPrimaryUnitPrecisionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class ProductPrimaryUnitPrecisionTypeTest extends FormIntegrationTestCase
{
    private array $units = ['item', 'kg'];

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitLabelFormatter;

    /** @var ProductPrimaryUnitPrecisionType */
    private $formType;

    protected function setUp(): void
    {
        $this->productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->formType = new ProductPrimaryUnitPrecisionType();
        $this->formType->setDataClass(ProductUnitPrecision::class);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    new ProductUnitSelectType($this->productUnitLabelFormatter),
                    EntityType::class => new EntityTypeStub($this->prepareChoices())
                ],
                [
                    FormType::class => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductUnitPrecision $defaultData,
        array $expectedOptions,
        array $submittedData,
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

    private function assertFormConfig(array $expectedConfig, FormConfigInterface $actualConfig)
    {
        foreach ($expectedConfig as $key => $value) {
            $this->assertTrue($actualConfig->hasOption($key));
            $this->assertEquals($value, $actualConfig->getOption($key));
        }
    }

    public function submitProvider(): array
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

    private function prepareChoices(): array
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
