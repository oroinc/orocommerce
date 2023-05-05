<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductAutocompleteType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitsType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddRowType;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductAutocompleteType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class QuickAddRowTypeTest extends FormIntegrationTestCase
{
    private ProductUnitsProvider|\PHPUnit\Framework\MockObject\MockObject $productUnitsProvider;

    protected function setUp(): void
    {
        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->productUnitsProvider
            ->expects($this->any())
            ->method('getAvailableProductUnits')
            ->willReturn(['Item' => 'item']);

        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(?array $defaultData, array $submittedData, array $expectedData): void
    {
        $form = $this->factory->create(QuickAddRowType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    ProductAutocompleteType::class => new StubProductAutocompleteType(),
                    ProductUnitsType::class => new ProductUnitsType($this->productUnitsProvider),
                    QuickAddRowType::class => new QuickAddRowType($this->productUnitsProvider),
                ],
                [
                    FormType::class => [new AdditionalAttrExtension()],
                ]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function submitDataProvider(): array
    {
        return [
            'without default data' => [
                'defaultData' => null,
                'submittedData' => [
                    QuickAddRow::SKU => 'SKU_001',
                    QuickAddRow::QUANTITY => '10',
                ],
                'expectedData' => [
                    QuickAddRow::SKU => 'SKU_001',
                    QuickAddRow::QUANTITY => 10.0,
                    QuickAddRow::UNIT => '',
                ],
            ],
            'with default data' => [
                'defaultData' => [
                    QuickAddRow::SKU => 'SKU_002',
                    QuickAddRow::QUANTITY => '10',
                ],
                'submittedData' => [
                    QuickAddRow::SKU => 'SKU_002',
                    QuickAddRow::QUANTITY => '20',
                    QuickAddRow::UNIT => 'item',
                ],
                'expectedData' => [
                    QuickAddRow::SKU => 'SKU_002',
                    QuickAddRow::QUANTITY => 20.0,
                    QuickAddRow::UNIT => 'item',
                ],
            ],
        ];
    }

    public function testFinishView(): void
    {
        $form = $this->factory->create(QuickAddRowType::class);
        $formView = $form->createView();

        self::assertEquals(
            [
                'sku' => '[data-name="field__sku"]',
                'displayName' => '[data-name="field__product"]',
            ],
            $formView['product']->vars['componentOptions']['selectors']
        );
    }
}
