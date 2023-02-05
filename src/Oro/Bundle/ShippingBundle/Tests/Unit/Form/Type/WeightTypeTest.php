<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Form\Type\WeightType;
use Oro\Bundle\ShippingBundle\Form\Type\WeightUnitSelectType;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class WeightTypeTest extends FormIntegrationTestCase
{
    private WeightType $formType;

    protected function setUp(): void
    {
        $this->formType = new WeightType();
        $this->formType->setDataClass(Weight::class);
        parent::setUp();
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(WeightType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, mixed $expectedData, mixed $defaultData = null)
    {
        $form = $this->factory->create(WeightType::class, $defaultData);

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

    private function getWeight(WeightUnit $weightUnit, int $value): Weight
    {
        return Weight::create($value, $weightUnit);
    }

    private function getWeightUnit(string $code): WeightUnit
    {
        $weightUnit = new WeightUnit();
        $weightUnit->setCode($code);

        return $weightUnit;
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
                    WeightUnitSelectType::class => new EntityTypeStub([
                        'kg' => $this->getWeightUnit('kg'),
                        'lbs' => $this->getWeightUnit('lbs')
                    ])
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
