<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager\Stub\ProductWithSizeAndColor;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatrixCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(MatrixCollection $defaultData, array $submittedData, MatrixCollection $expectedData)
    {
        $form = $this->factory->create(MatrixCollectionType::class, $defaultData);
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
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
                                [
                                    'quantity' => 3,
                                ],
                                [
                                    'quantity' => 7,
                                ],
                            ]
                        ],
                        [
                            'columns' => [
                                [],
                                [
                                    'quantity' => 5,
                                ],
                            ]
                        ],
                    ],
                ],
                'expectedData' => $this->createCollection(true),
            ],
            'empty data' => [
                'defaultData' => $this->createCollection(),
                'submittedData' => [],
                'expectedData' => $this->createCollection(),
            ],
        ];
    }

    public function testBuildView()
    {
        $expectedQtys = [3, 12];
        $collection = $this->createCollection(true);
        $form = $this->factory->create(MatrixCollectionType::class, $collection);
        $view = $form->createView();

        $this->assertEquals($expectedQtys, $view->vars['columnsQty']);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertEquals(MatrixCollection::class, $options['data_class']);
            });

        $type = new MatrixCollectionType();
        $type->configureOptions($resolver);
    }

    private function createCollection(bool $withQuantities = false): MatrixCollection
    {
        $simpleProductSmallRed = (new ProductWithSizeAndColor())->setSize('s')->setColor('red');
        $simpleProductSmallGreen = (new ProductWithSizeAndColor())->setSize('s')->setColor('green');
        $simpleProductMediumGreen = (new ProductWithSizeAndColor())->setSize('m')->setColor('green');

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
}
