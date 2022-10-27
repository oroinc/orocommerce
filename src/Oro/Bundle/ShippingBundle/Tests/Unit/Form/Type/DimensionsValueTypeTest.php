<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ShippingBundle\Form\Type\DimensionsValueType;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class DimensionsValueTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\ShippingBundle\Model\DimensionsValue';

    /** @var DimensionsValueType */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = new DimensionsValueType();
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
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
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create(DimensionsValueType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
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

    /**
     * @param float $length
     * @param float $width
     * @param float $height
     *
     * @return DimensionsValue
     */
    protected function getDimensionsValue($length, $width, $height)
    {
        return DimensionsValue::create($length, $width, $height);
    }
}
