<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitsTypeTest extends FormIntegrationTestCase
{
    /** @var  ProductUnitsType $productUnitsType */
    protected $productUnitsType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitsProvider $productUnitsProvider */
    protected $productUnitsProvider;

    public function setup()
    {
        parent::setUp();
        $this->productUnitsProvider =
            $this->getMockBuilder('Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->productUnitsProvider
            ->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn([
                'each' => 'each',
                'kg' => 'kilogram'
            ]);

        $this->productUnitsType = new ProductUnitsType($this->productUnitsProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(ProductUnitsType::NAME, $this->productUnitsType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->productUnitsType->getParent());
    }

    public function testChoices()
    {
        $form = $this->factory->create($this->productUnitsType);
        $availableUnits = $this->productUnitsProvider->getAvailableProductUnits();
        $choices = [];

        foreach ($availableUnits as $key => $value) {
            $choices[] = new ChoiceView($key, $key, $value);
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
        $form = $this->factory->create($this->productUnitsType);
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
                'submittedData' => [
                    'productUnit' => 'kg',
                ],
                'expectedData' => [
                    'productUnit' => 'kg',
                ],
            ]
        ];
    }
}
