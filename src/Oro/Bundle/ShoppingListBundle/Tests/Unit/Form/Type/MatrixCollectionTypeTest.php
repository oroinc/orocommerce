<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixColumnType;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub\ProductWithSizeAndColor;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatrixCollectionTypeTest extends AbstractFormIntegrationTestCase
{
    private ProductRFPAvailabilityProvider&MockObject $rfpProvider;
    private ConfigManager&MockObject $configManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->rfpProvider = $this->createMock(ProductRFPAvailabilityProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    MatrixColumnType::class => new MatrixColumnType(
                        $this->rfpProvider,
                        $this->configManager
                    ),
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        MatrixCollection $defaultData,
        array $submittedData,
        MatrixCollection $expectedData
    ): void {
        $form = $this->factory->create(MatrixCollectionType::class, $defaultData);
        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
    {
        return [
            'with quantities' => [
                'defaultData' => $this->createCollection(),
                'submittedData' => [
                    'rows' => [
                        [
                            'columns' => [
                                ['quantity' => 3],
                                ['quantity' => 7]
                            ]
                        ],
                        [
                            'columns' => [
                                [],
                                ['quantity' => 5]
                            ]
                        ]
                    ]
                ],
                'expectedData' => $this->createCollection(true)
            ],
            'empty data' => [
                'defaultData' => $this->createCollection(),
                'submittedData' => [],
                'expectedData' => $this->createCollection()
            ]
        ];
    }

    public function testBuildView(): void
    {
        $collection = $this->createCollection(true);
        $form = $this->factory->create(MatrixCollectionType::class, $collection);
        $view = $form->createView();

        self::assertEquals([3, 12], $view->vars['columnsQty']);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(self::isType('array'))
            ->willReturnCallback(function (array $options) use ($resolver) {
                self::assertArrayHasKey('data_class', $options);
                self::assertEquals(MatrixCollection::class, $options['data_class']);

                return $resolver;
            });

        $type = new MatrixCollectionType();
        $type->configureOptions($resolver);
    }

    private function createCollection(bool $withQuantities = false): MatrixCollection
    {
        $simpleProductSmallRed = $this->getProductWithSizeAndColor('s', 'red');
        $simpleProductSmallGreen = $this->getProductWithSizeAndColor('s', 'green');
        $simpleProductMediumGreen = $this->getProductWithSizeAndColor('m', 'green');

        $columnSmallRed = new MatrixCollectionColumn();
        $columnSmallGreen = new MatrixCollectionColumn();
        $columnMediumRed = new MatrixCollectionColumn();
        $columnMediumGreen = new MatrixCollectionColumn();

        $columnSmallRed->product = $simpleProductSmallRed;
        $columnSmallGreen->product = $simpleProductSmallGreen;
        $columnMediumGreen->product = $simpleProductMediumGreen;

        if ($withQuantities) {
            $columnSmallRed->quantity = 3;
            $columnSmallGreen->quantity = 7;
            $columnMediumGreen->quantity = 5;
        }

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->label = 'Small';
        $rowSmall->columns = [$columnSmallRed, $columnSmallGreen];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->label = 'Medium';
        $rowMedium->columns = [$columnMediumRed, $columnMediumGreen];

        $collection = new MatrixCollection();

        $unit = new ProductUnit();
        $unit->setCode('item');

        $collection->unit = $unit;
        $collection->rows = [$rowSmall, $rowMedium];

        return $collection;
    }

    private function getProductWithSizeAndColor(string $size, string $color): ProductWithSizeAndColor
    {
        $productWithSizeAndColor = new ProductWithSizeAndColor();
        $productWithSizeAndColor->setSize($size);
        $productWithSizeAndColor->setColor($color);

        return $productWithSizeAndColor;
    }
}
