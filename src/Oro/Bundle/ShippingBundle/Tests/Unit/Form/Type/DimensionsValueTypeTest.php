<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\Type\DimensionsValueType;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class DimensionsValueTypeTest extends FormIntegrationTestCase
{
    /** @var DimensionsValueType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new DimensionsValueType();
        $this->formType->setDataClass(DimensionsValue::class);
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
                    DimensionsValueType::class => $this->formType
                ],
                []
            ),
        ];
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(DimensionsValueType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(array $submittedData, mixed $expectedData, mixed $defaultData = null)
    {
        $form = $this->factory->create(DimensionsValueType::class, $defaultData);

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
                    'length' => '42',
                    'width' => '42',
                    'height' => '42'
                ],
                'expectedData' => $this->getDimensionsValue(42, 42, 42)
            ],
            'full data' => [
                'submittedData' => [
                    'length' => '2',
                    'width' => '4',
                    'height' => '6'
                ],
                'expectedData' => $this->getDimensionsValue(2, 4, 6),
                'defaultData' => $this->getDimensionsValue(1, 3, 5),
            ],
        ];
    }

    private function getDimensionsValue(int $length, int $width, int $height): DimensionsValue
    {
        return DimensionsValue::create($length, $width, $height);
    }
}
