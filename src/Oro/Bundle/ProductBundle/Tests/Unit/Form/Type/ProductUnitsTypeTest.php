<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ProductUnitsTypeTest extends FormIntegrationTestCase
{
    /** @var  ProductUnitsType $productUnitsType */
    protected $productUnitsType;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductUnitsProvider $productUnitsProvider */
    protected $productUnitsProvider;

    public function setup()
    {
        $this->productUnitsProvider =
            $this->getMockBuilder('Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->productUnitsProvider
            ->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([
                'each' => 'each',
                'kilogram' => 'kg'
            ]);

        $this->productUnitsType = new ProductUnitsType($this->productUnitsProvider);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension([$this->productUnitsType], [])
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->productUnitsType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create(ProductUnitsType::class);
        $availableUnits = $this->productUnitsProvider->getAvailableProductUnits();
        $choices = [];

        foreach ($availableUnits as $label => $value) {
            $choices[] = new ChoiceView($value, $value, $label);
        }

        $this->assertEquals(
            $choices,
            $form->createView()->vars['choices']
        );
    }

    /**
     * @dataProvider submitDataProvider
     * @param mixed $defaultData
     * @param array $submittedData
     * @param array $expectedData
     */
    public function testSubmit(
        $defaultData,
        $submittedData,
        $expectedData
    ) {
        $form = $this->factory->create(ProductUnitsType::class);
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getViewData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'valid' => [
                'defaultData' => null,
                'submittedData' => 'kg',
                'expectedData' => 'kg'
            ]
        ];
    }
}
