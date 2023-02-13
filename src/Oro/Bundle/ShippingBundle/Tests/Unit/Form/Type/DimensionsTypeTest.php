<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Form\Type\DimensionsType;
use Oro\Bundle\ShippingBundle\Form\Type\DimensionsValueType;
use Oro\Bundle\ShippingBundle\Form\Type\LengthUnitSelectType;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class DimensionsTypeTest extends FormIntegrationTestCase
{
    private DimensionsType $formType;

    protected function setUp(): void
    {
        $this->formType = new DimensionsType();
        $this->formType->setDataClass(Dimensions::class);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(DimensionsType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, mixed $expectedData, mixed $defaultData = null)
    {
        $form = $this->factory->create(DimensionsType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
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

    private function getLengthUnit(string $code): LengthUnit
    {
        $lengthUnit = new LengthUnit();
        $lengthUnit->setCode($code);

        return $lengthUnit;
    }

    private function getDimensions(LengthUnit $lengthUnit, int $length, int $width, int $height): Dimensions
    {
        return Dimensions::create($length, $width, $height, $lengthUnit);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $valueType = new DimensionsValueType();
        $valueType->setDataClass(DimensionsValue::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    LengthUnitSelectType::class => new EntityTypeStub([
                        'm' => $this->getLengthUnit('m'),
                        'sm' => $this->getLengthUnit('sm'),
                        'foot' => $this->getLengthUnit('foot')
                    ]),
                    $valueType
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
