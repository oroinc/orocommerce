<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
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

class ProductUnitPrecisionTypeTest extends FormIntegrationTestCase
{
    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitLabelFormatter;

    /** @var ProductUnitPrecisionType */
    private $formType;

    protected function setUp(): void
    {
        $this->productUnitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->formType = new ProductUnitPrecisionType();
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
                    EntityType::class => new EntityTypeStub([
                        'item' => (new ProductUnit())->setCode('item'),
                        'kg' => (new ProductUnit())->setCode('kg')
                    ])
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
        $form = $this->factory->create(ProductUnitPrecisionType::class, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());

        if ($defaultData->getUnit()) {
            $unitDisabled = $form->get('unit_disabled');

            $this->assertNotNull($unitDisabled);
            $this->assertFormConfig($expectedOptions['unit_disabled'], $unitDisabled->getConfig());
        }

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
                            'class' => 'hidden-unit'
                        ]
                    ],
                    'unit_disabled' => [
                        'disabled' => false,
                        'mapped'   => false
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
                    'unit_disabled' => []
                ],
                'submittedData' => [],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setSell(false)
            ],
            'unit precision with value' => [
                'defaultData'   => new ProductUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                    'unit_disabled' => []
                ],
                'submittedData' => [
                    'unit' => 'kg',
                    'precision' => 5,
                    'conversionRate' => 2,
                    'sell' => true,
                ],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(5)
                    ->setConversionRate(2)
                    ->setSell(true)
            ]
        ];
    }
}
