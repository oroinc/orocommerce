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
    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductUnitsProvider */
    private $productUnitsProvider;

    /** @var ProductUnitsType */
    private $productUnitsType;

    protected function setUp(): void
    {
        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);

        $this->productUnitsProvider->expects(self::any())
            ->method('getAvailableProductUnits')
            ->willReturn([
                'each' => 'each',
                'kilogram' => 'kg'
            ]);

        $this->productUnitsType = new ProductUnitsType($this->productUnitsProvider);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
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
     */
    public function testSubmit(
        mixed $defaultData,
        string $submittedData,
        string $expectedData
    ) {
        $form = $this->factory->create(ProductUnitsType::class);
        $this->assertEquals($defaultData, $form->getViewData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expectedData, $form->getViewData());
    }

    public function submitDataProvider(): array
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
